<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Админка клубов: поиск по названию, назначение менеджера и удаление.
// Клубы создаются импортом из OpenStreetMap (clubs:import), поэтому
// здесь только управление уже существующими записями.
class AdminClubController extends Controller
{
    public function index(Request $request): View
    {
        $clubs = Club::withCount('courts')
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.clubs.index', [
            'clubs' => $clubs,
            'managers' => User::where('role', 'manager')->orderBy('name')->get(),
        ]);
    }

    // Назначает/снимает менеджера клуба.
    public function update(Request $request, Club $club): RedirectResponse
    {
        $data = $request->validate([
            'manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $club->update(['manager_id' => $data['manager_id'] ?: null]);

        return back()->with('ok', 'Club actualizado.');
    }

    public function destroy(Club $club): RedirectResponse
    {
        $club->delete();

        return back()->with('ok', 'Club eliminado.');
    }
}
