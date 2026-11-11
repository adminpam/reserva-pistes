<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Админка пользователей: смена роли и удаление.
// Админ не может удалить сам себя или снять с себя роль admin —
// иначе можно случайно остаться без доступа к админке.
class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::when($request->query('q'), fn ($q, $term) =>
                $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:player,manager,admin'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'No puedes quitarte tu propio rol de administrador.']);
        }

        $user->update($data);

        return back()->with('ok', 'Rol actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta.']);
        }

        $user->delete();

        return back()->with('ok', 'Usuario eliminado.');
    }
}
