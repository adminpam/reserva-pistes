<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAvatar;
use App\Support\Ui;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AvatarController extends Controller
{
    private const VERSION_ERROR = 'La referencia de la foto no es válida. Actualiza el perfil.';

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $key = 'avatar-upload:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['avatar' => 'Has cambiado la foto muchas veces. Espera un minuto antes de repetir.']);
        }
        RateLimiter::hit($key, 60);
        $data = $request->validate([
            'avatar' => ['bail', 'required', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:512', 'dimensions:min_width=32,min_height=32,max_width=1600,max_height=1600'],
            'expected_avatar_version' => $this->versionRules(),
        ], [
            'avatar.required' => 'Elige una foto para tu perfil.',
            'avatar.image' => 'El archivo debe ser una imagen válida.',
            'avatar.mimetypes' => 'Usa una imagen JPG, PNG o WebP.',
            'avatar.max' => 'La foto enviada supera 512 KB. Elige una versión más pequeña.',
            'avatar.dimensions' => 'La imagen debe medir entre 32 y 1600 píxeles por lado.',
            'expected_avatar_version.string' => self::VERSION_ERROR,
            'expected_avatar_version.regex' => self::VERSION_ERROR,
        ]);
        $file = $data['avatar'];
        $bytes = $file->getContent();
        $mime = $file->getMimeType();
        [$user, $conflict] = DB::transaction(function () use ($request, $bytes, $mime, $data): array {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            if ($this->versionConflict($user, $data)) {
                return [$user, true];
            }
            UserAvatar::updateOrCreate(['user_id' => $user->id], ['mime_type' => $mime, 'image_base64' => base64_encode($bytes)]);
            $user->forceFill(['avatar_version' => hash('sha256', $bytes)])->save();

            return [$user, false];
        }, 3);

        return $this->result($request, $user, 'Foto de perfil actualizada.', $conflict);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['expected_avatar_version' => $this->versionRules()], [
            'expected_avatar_version.string' => self::VERSION_ERROR,
            'expected_avatar_version.regex' => self::VERSION_ERROR,
        ]);
        [$user, $conflict] = DB::transaction(function () use ($request, $data): array {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            if ($this->versionConflict($user, $data)) {
                return [$user, true];
            }
            UserAvatar::whereKey($user->id)->delete();
            $user->forceFill(['avatar_version' => null])->save();

            return [$user, false];
        }, 3);

        return $this->result($request, $user, 'Foto eliminada. Se muestran tus iniciales.', $conflict);
    }

    private function versionRules(): array
    {
        // El campo omitido mantiene el contrato del formulario anterior.
        // Un campo vacío se convierte en null: el cliente esperaba no tener foto.
        return ['bail', 'sometimes', 'nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/'];
    }

    private function versionConflict(User $user, array $data): bool
    {
        // Se comprueba dentro del mismo bloqueo que modifica la foto.
        return array_key_exists('expected_avatar_version', $data)
            && $data['expected_avatar_version'] !== $user->avatar_version;
    }

    private function result(Request $request, User $user, string $message, bool $conflict): JsonResponse|RedirectResponse
    {
        if ($conflict) {
            $message = 'La foto ha cambiado. Revisa su estado antes de volver a guardarla.';
        }
        if ($request->expectsJson()) {
            // Solo metadatos de la cuenta autenticada, nunca bytes ni datos de usuario.
            return response()->json([
                'message' => $message,
                'avatar' => [
                    'url' => $user->avatarUrl(),
                    'version' => $user->avatar_version,
                    'initials' => Ui::initials($user->name),
                ],
            ], $conflict ? 409 : 200)->header('Cache-Control', 'private, no-store');
        }
        if ($conflict) {
            throw ValidationException::withMessages(['avatar' => $message]);
        }

        return back()->with('ok', $message);
    }

    public function show(Request $request, User $user): Response
    {
        // Una sola lectura une versión y bytes: no mezcla dos cambios simultáneos.
        $avatar = UserAvatar::join('users', 'users.id', '=', 'user_avatars.user_id')
            ->where('users.id', $user->id)->first(['user_avatars.*', 'users.avatar_version']);
        abort_unless($avatar && in_array($avatar->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        $bytes = base64_decode($avatar->image_base64, true);
        abort_if($bytes === false || hash('sha256', $bytes) !== $avatar->avatar_version, 404);
        $response = response($bytes, 200, [
            'Content-Type' => $avatar->mime_type,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ]);
        $response->setEtag($avatar->avatar_version);
        $response->isNotModified($request);

        return $response;
    }
}
