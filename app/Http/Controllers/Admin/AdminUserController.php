<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// La baja solo se permite para cuentas sin reservas, participaciones ni clubes.
class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $users = User::query()
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(fn ($query) =>
                $query->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(['player', 'manager', 'admin'])]]);
        DB::transaction(function () use ($request, $user, $data): void {
            $this->lockAdministrators($request);
            $current = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($current->id === $request->user()->id && $data['role'] !== 'admin') {
                throw ValidationException::withMessages(['role' => 'No puedes quitarte tu propio rol de administrador.']);
            }
            if ($data['role'] !== 'manager' && $current->managedClubs()->exists()) {
                throw ValidationException::withMessages(['role' => 'Reasigna o retira sus clubes antes de cambiar el rol del gestor.']);
            }
            $current->update($data);
        }, 3);

        return back()->with('ok', 'Rol actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $this->lockAdministrators($request);
            $current = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($current->id === $request->user()->id) {
                throw ValidationException::withMessages(['user' => 'No puedes eliminar tu propia cuenta.']);
            }
            if ($current->managedClubs()->exists()) {
                throw ValidationException::withMessages(['user' => 'Reasigna o retira sus clubes antes de eliminar este gestor.']);
            }
            if ($current->bookings()->exists() || $current->joinedMatches()->exists()) {
                throw ValidationException::withMessages(['user' => 'Este usuario tiene reservas o participaciones. Debe conservarse para mantener el historial.']);
            }
            $current->delete();
        }, 3);

        return back()->with('ok', 'Usuario eliminado.');
    }

    private function lockAdministrators(Request $request): void
    {
        // Orden común: dos administradores no deben eliminarse mutuamente a la vez.
        // Tras esperar el bloqueo, comprobamos que quien actúa sigue siendo admin.
        $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
        abort_unless($admins->contains('id', $request->user()->id), 403);
    }
}
