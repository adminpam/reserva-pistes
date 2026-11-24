<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingCheckout;
use Illuminate\Support\Str;
use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Тесты главного бизнес-правила: один слот — одна бронь.
// Проверяем, что пересекающаяся бронь отклоняется, а после отмены слот свободен.
class BookingOverlapTest extends TestCase
{
    use RefreshDatabase;

    private function approvePending(): void
    {
        $checkout = BookingCheckout::where('status', 'pending')->sole();
        $this->post(route('checkout.pay', $checkout), ['payment' => ['holder' => 'Persona Demo',
            'number' => '4242424242424242', 'expiry' => now()->addYears(2)->format('m/y'), 'cvc' => '123']])
            ->assertSessionHasNoErrors();
    }

    private function makeCourt(): Court
    {
        $sport = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $club = Club::create(['name' => 'Club Test', 'lat' => 41.5, 'lng' => 2.1]);

        return Court::create([
            'club_id' => $club->id,
            'sport_id' => $sport->id,
            'name' => 'Pista 1',
            'price_per_hour' => 1000,
            'open_hour' => 8,
            'close_hour' => 22,
        ]);
    }

    public function test_no_se_puede_reservar_un_slot_ocupado(): void
    {
        $court = $this->makeCourt();
        $ana = User::create(['name' => 'Ana', 'email' => 'ana@test.test', 'password' => 'password1234']);
        $leo = User::create(['name' => 'Leo', 'email' => 'leo@test.test', 'password' => 'password1234']);

        $date = now()->addDay()->toDateString();

        // Первая бронь на 10:00 проходит.
        $this->actingAs($ana)->post('/bookings', [
            'checkout_key' => (string) Str::uuid(), 'court_id' => $court->id, 'date' => $date, 'hour' => 10,
        ])->assertSessionHasNoErrors();

        $this->approvePending();

        // Вторая бронь того же слота — отклоняется с ошибкой валидации.
        $this->actingAs($leo)->post('/bookings', [
            'checkout_key' => (string) Str::uuid(), 'court_id' => $court->id, 'date' => $date, 'hour' => 10,
        ])->assertSessionHasErrors('hour');

        $this->assertSame(1, Booking::where('status', 'confirmed')->count());
    }

    public function test_un_slot_cancelado_se_puede_volver_a_reservar(): void
    {
        $court = $this->makeCourt();
        $ana = User::create(['name' => 'Ana', 'email' => 'ana2@test.test', 'password' => 'password1234']);
        $leo = User::create(['name' => 'Leo', 'email' => 'leo2@test.test', 'password' => 'password1234']);

        $date = now()->addDay()->toDateString();

        $this->actingAs($ana)->post('/bookings', ['checkout_key' => (string) Str::uuid(), 'court_id' => $court->id, 'date' => $date, 'hour' => 11]);
        $this->approvePending();
        $booking = Booking::firstOrFail();

        // Ана отменяет — слот освобождается, и Лео может его занять.
        $this->actingAs($ana)->patch("/bookings/{$booking->id}/cancel");

        $this->actingAs($leo)->post('/bookings', [
            'checkout_key' => (string) Str::uuid(), 'court_id' => $court->id, 'date' => $date, 'hour' => 11,
        ])->assertSessionHasNoErrors();
        $this->approvePending();
        $this->assertSame(1, Booking::where('status', 'confirmed')->count());
    }
}
