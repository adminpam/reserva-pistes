<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAvatar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AvatarController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $key = 'avatar-upload:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['avatar' => 'Has cambiado la foto muchas veces. Espera un minuto antes de repetir.']);
        }
        RateLimiter::hit($key, 60);
        $data = $request->validate([
            'avatar' => ['bail', 'required', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:512', 'dimensions:min_width=32,min_height=32,max_width=1600,max_height=1600'],
        ], [
            'avatar.required' => 'Elige una foto para tu perfil.',
            'avatar.image' => 'El archivo debe ser una imagen válida.',
            'avatar.mimetypes' => 'Usa una imagen JPG, PNG o WebP.',
            'avatar.max' => 'La foto enviada supera 512 KB. Elige una versión más pequeña.',
            'avatar.dimensions' => 'La imagen debe medir entre 32 y 1600 píxeles por lado.',
        ]);
        $file = $data['avatar'];
        $bytes = $file->getContent();
        $mime = $file->getMimeType();
        DB::transaction(function () use ($request, $bytes, $mime): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            UserAvatar::updateOrCreate(['user_id' => $user->id], ['mime_type' => $mime, 'image_base64' => base64_encode($bytes)]);
            $user->forceFill(['avatar_version' => hash('sha256', $bytes)])->save();
        }, 3);

        return back()->with('ok', 'Foto de perfil actualizada.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            UserAvatar::whereKey($user->id)->delete();
            $user->forceFill(['avatar_version' => null])->save();
        }, 3);

        return back()->with('ok', 'Foto eliminada. Se muestran tus iniciales.');
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
