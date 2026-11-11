<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

// Справочник видов спорта: создание, правка вместимости матча и удаление.
class AdminSportController extends Controller
{
    public function index(): View
    {
        return view('admin.sports.index', [
            'sports' => Sport::withCount('courts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:sports,name'],
            'players_per_match' => ['required', 'integer', 'min:2', 'max:30'],
        ]);

        Sport::create([...$data, 'slug' => Str::slug($data['name'])]);

        return back()->with('ok', 'Deporte creado.');
    }

    public function update(Request $request, Sport $sport): RedirectResponse
    {
        $data = $request->validate([
            'players_per_match' => ['required', 'integer', 'min:2', 'max:30'],
        ]);

        $sport->update($data);

        return back()->with('ok', 'Deporte actualizado.');
    }

    public function destroy(Sport $sport): RedirectResponse
    {
        // Нельзя удалить спорт, у которого есть площадки: иначе каскад снесёт их вместе с бронями.
        if ($sport->courts()->exists()) {
            return back()->withErrors(['sport' => 'Este deporte tiene pistas asociadas y no se puede eliminar.']);
        }

        $sport->delete();

        return back()->with('ok', 'Deporte eliminado.');
    }
}
