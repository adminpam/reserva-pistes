<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Services\ClubAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

// La ficha y su endpoint consultan el mismo servicio para no discrepar al cancelar.
class ClubController extends Controller
{
    public function __construct(private ClubAvailability $availability) {}

    public function show(Request $request, Club $club): View
    {
        $input = ['date' => $request->query('date', now()->toDateString())];
        $validator = Validator::make($input, ['date' => ['required', 'date_format:Y-m-d']]);
        $date = $validator->fails() ? now()->toDateString() : $input['date'];
        $club->load('courts.sport');

        return view('clubs.show', [
            'club' => $club,
            'date' => $date,
            'availability' => $this->availability->forDate($club, $date),
            'hasOutdoor' => $club->courts->contains('is_outdoor', true),
        ]);
    }

    // Lectura pública de horas, sin importes ni datos de quienes han reservado.
    public function availability(Request $request, Club $club): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return response()->json($this->availability->forDate($club, $data['date']))
            ->header('Cache-Control', 'private, no-store')
            ->header('Pragma', 'no-cache');
    }
}
