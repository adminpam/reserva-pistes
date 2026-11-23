<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Sport;
use App\Models\UserSportLevel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminSportController extends Controller
{
    public function index(): View
    {
        return view('admin.sports.index', ['sports' => Sport::withCount('courts')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:sports,name'],
            'players_per_match' => ['required', 'integer', 'min:2', 'max:30'],
        ]);
        // Dos nombres distintos pueden producir el mismo slug: «Tenis!» y «Tenis».
        $slug = Str::slug($data['name']);
        $validator = Validator::make(['name' => $slug], ['name' => ['required', 'max:60', 'unique:sports,slug']], [
            'name.required' => 'El nombre debe contener letras o números.',
            'name.unique' => 'Ya existe un deporte con un nombre equivalente.',
            'name.max' => 'El identificador del deporte es demasiado largo.',
        ]);
        $validator->validate();

        try {
            Sport::create([...$data, 'slug' => $slug]);
        } catch (UniqueConstraintViolationException $exception) {
            // La restricción única también cubre dos altas simultáneas del mismo slug.
            throw ValidationException::withMessages(['name' => 'Ya existe un deporte con un nombre equivalente.']);
        }

        return back()->with('ok', 'Deporte creado.');
    }

    public function update(Request $request, Sport $sport): RedirectResponse
    {
        $data = $request->validate(['players_per_match' => ['required', 'integer', 'min:2', 'max:30']]);
        DB::transaction(function () use ($sport, $data): void {
            $current = Sport::query()->lockForUpdate()->findOrFail($sport->id);
            // El aforo se consulta desde el deporte al mostrar cualquier partido.
            // Sin una copia del aforo en cada reserva, cambiarlo alteraría su historial.
            if ((int) $data['players_per_match'] !== (int) $current->players_per_match
                && Booking::where('is_open', true)->whereHas('court', fn ($query) => $query->where('sport_id', $current->id))->exists()) {
                throw ValidationException::withMessages(['players_per_match' => 'Este deporte ya tiene partidos. Su aforo se conserva para no alterar las plazas ni el historial.']);
            }
            $current->update($data);
        }, 3);

        return back()->with('ok', 'Deporte actualizado.');
    }

    public function destroy(Sport $sport): RedirectResponse
    {
        DB::transaction(function () use ($sport): void {
            $current = Sport::query()->lockForUpdate()->findOrFail($sport->id);
            if ($current->courts()->exists() || UserSportLevel::where('sport_id', $current->id)->exists()) {
                throw ValidationException::withMessages(['sport' => 'Este deporte tiene pistas o niveles de jugadores asociados y no se puede eliminar.']);
            }
            $current->delete();
        }, 3);

        return back()->with('ok', 'Deporte eliminado.');
    }
}
