<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use App\Models\UserSportLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Perfil del jugador: nombre y nivel (1-5) por cada deporte.
// El nivel se usa al apuntarse a partidos abiertos con rango de nivel.
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'sports' => Sport::orderBy('name')->get(),
        ]);
    }

    // Guarda el nombre y los niveles. Para cada deporte: si llega un nivel
    // valido se hace upsert; si llega vacio se borra (el jugador "quita" ese deporte).
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'levels' => ['array'],
            'levels.*' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $user = $request->user();
        $user->update(['name' => $data['name']]);

        foreach ($data['levels'] ?? [] as $sportId => $level) {
            if ($level === null || $level === '') {
                UserSportLevel::where('user_id', $user->id)->where('sport_id', $sportId)->delete();
                continue;
            }
            UserSportLevel::updateOrCreate(
                ['user_id' => $user->id, 'sport_id' => $sportId],
                ['level' => $level],
            );
        }

        return back()->with('ok', 'Perfil actualizado.');
    }
}
