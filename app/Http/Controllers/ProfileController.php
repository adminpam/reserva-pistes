<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use App\Models\User;
use App\Models\UserSportLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('sportLevels'),
            'sports' => Sport::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'levels' => ['sometimes', 'array', 'max:100'],
            'levels.*' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);
        $levels = $data['levels'] ?? [];
        // Los IDs son las claves del array, no sus valores. No se aceptan alias
        // como "01", claves negativas ni nombres de campos en lugar de deportes.
        foreach (array_keys($levels) as $id) {
            if ((string) (int) $id !== (string) $id || (int) $id < 1) {
                throw ValidationException::withMessages(['levels' => 'Los niveles deben corresponder a deportes válidos. Recarga el perfil.']);
            }
        }

        DB::transaction(function () use ($request, $data, $levels): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $sports = Sport::whereIn('id', array_keys($levels))->orderBy('id')->lockForUpdate()->get();
            if ($sports->count() !== count($levels)) {
                throw ValidationException::withMessages(['levels' => 'Algún deporte ya no está disponible. Recarga el perfil antes de guardar.']);
            }
            // Primero se validan todos los deportes; después se guarda todo junto.
            $user->update(['name' => $data['name']]);
            foreach ($levels as $sportId => $level) {
                if ($level === null) {
                    UserSportLevel::where('user_id', $user->id)->where('sport_id', $sportId)->delete();
                } else {
                    UserSportLevel::updateOrCreate(['user_id' => $user->id, 'sport_id' => $sportId], ['level' => $level]);
                }
            }
        }, 3);

        return back()->with('ok', 'Perfil actualizado.');
    }
}
