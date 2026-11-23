<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// Los clubes se importan de OSM; aquí se asigna su gestor y se retiran los vacíos.
class AdminClubController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $clubs = Club::withCount('courts')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.clubs.index', [
            'clubs' => $clubs,
            'managers' => User::where('role', 'manager')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Club $club): RedirectResponse
    {
        $data = $request->validate([
            'manager_id' => ['present', 'nullable', 'integer', Rule::exists('users', 'id')->where('role', 'manager')],
        ], ['manager_id.exists' => 'El usuario elegido debe tener el rol de gestor.']);

        DB::transaction(function () use ($data, $club): void {
            // El mismo usuario se bloquea al cambiar su rol o darlo de baja.
            $managerId = $data['manager_id'];
            if ($managerId !== null) {
                $manager = User::query()->lockForUpdate()->find($managerId);
                if (! $manager || ! $manager->isManager()) {
                    throw ValidationException::withMessages(['manager_id' => 'El usuario elegido ya no es gestor.']);
                }
            }
            $current = Club::query()->lockForUpdate()->findOrFail($club->id);
            $current->update(['manager_id' => $managerId]);
        }, 3);

        return back()->with('ok', 'Club actualizado.');
    }

    public function destroy(Club $club): RedirectResponse
    {
        DB::transaction(function () use ($club): void {
            $current = Club::query()->lockForUpdate()->findOrFail($club->id);
            if ($current->courts()->exists()) {
                throw ValidationException::withMessages(['club' => 'Este club tiene pistas. Solo se pueden eliminar clubes vacíos; las pistas con reservas deben conservarse.']);
            }
            $current->delete();
        }, 3);

        return back()->with('ok', 'Club eliminado.');
    }
}
