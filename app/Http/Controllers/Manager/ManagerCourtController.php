<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// CRUD de pistas para el gestor de club. Un gestor solo ve y edita
// las pistas de SUS clubes (clubs.manager_id = su id); cualquier intento
// de tocar una pista ajena devuelve 403.
class ManagerCourtController extends Controller
{
    // Lista de pistas agrupadas por club del gestor.
    public function index(Request $request): View
    {
        $clubs = $request->user()->managedClubs()->with('courts.sport')->get();

        return view('manager.courts.index', ['clubs' => $clubs]);
    }

    public function create(Request $request): View
    {
        return view('manager.courts.form', [
            'court' => null,
            'clubs' => $request->user()->managedClubs,
            'sports' => Sport::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // El club elegido tiene que pertenecer al gestor.
        if (! $request->user()->managedClubs->contains('id', (int) $data['club_id'])) {
            abort(403);
        }

        Court::create($data);

        return redirect()->route('manager.courts.index')->with('ok', 'Pista creada.');
    }

    public function edit(Request $request, Court $court): View
    {
        $this->authorizeCourt($request, $court);

        return view('manager.courts.form', [
            'court' => $court,
            'clubs' => $request->user()->managedClubs,
            'sports' => Sport::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Court $court): RedirectResponse
    {
        $this->authorizeCourt($request, $court);
        $data = $this->validated($request);

        if (! $request->user()->managedClubs->contains('id', (int) $data['club_id'])) {
            abort(403);
        }

        $court->update($data);

        return redirect()->route('manager.courts.index')->with('ok', 'Pista actualizada.');
    }

    public function destroy(Request $request, Court $court): RedirectResponse
    {
        $this->authorizeCourt($request, $court);
        $court->delete();

        return back()->with('ok', 'Pista eliminada.');
    }

    // Reglas de validacion compartidas entre crear y editar.
    // El precio llega en euros desde el formulario y se guarda en centimos.
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'club_id' => ['required', 'exists:clubs,id'],
            'sport_id' => ['required', 'exists:sports,id'],
            'name' => ['required', 'string', 'max:100'],
            'price_euros' => ['required', 'numeric', 'min:0', 'max:500'],
            'open_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'close_hour' => ['required', 'integer', 'min:1', 'max:24', 'gt:open_hour'],
            'is_outdoor' => ['sometimes', 'boolean'],
        ]);

        $data['price_per_hour'] = (int) round($data['price_euros'] * 100);
        $data['is_outdoor'] = $request->boolean('is_outdoor');
        unset($data['price_euros']);

        return $data;
    }

    private function authorizeCourt(Request $request, Court $court): void
    {
        if ($court->club->manager_id !== $request->user()->id) {
            abort(403);
        }
    }
}
