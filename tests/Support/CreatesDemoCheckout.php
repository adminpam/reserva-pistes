<?php

namespace Tests\Support;

use App\Models\BookingCheckout;
use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

trait CreatesDemoCheckout
{
    private Court $court;
    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $this->player = User::factory()->create(['name' => 'Jugadora ficticia', 'email' => 'checkout@example.test']);
        $sport = Sport::create(['name' => 'Tenis demo', 'slug' => 'tenis-demo', 'players_per_match' => 2]);
        $club = Club::create(['name' => 'Club de prueba', 'lat' => 41.5, 'lng' => 2.1]);
        $this->court = Court::create(['club_id' => $club->id, 'sport_id' => $sport->id, 'name' => 'Pista demo',
            'price_per_hour' => 1450, 'open_hour' => 8, 'close_hour' => 22]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function selection(array $changes = []): array
    {
        return array_replace(['checkout_key' => (string) Str::uuid(), 'court_id' => $this->court->id,
            'date' => '2026-11-25', 'hour' => 10], $changes);
    }

    private function begin(array $changes = []): BookingCheckout
    {
        $data = $this->selection($changes);
        $response = $this->actingAs($this->player)->post('/bookings', $data)->assertSessionHasNoErrors();
        $checkout = BookingCheckout::findOrFail($data['checkout_key']);
        $response->assertRedirect(route('checkout.show', $checkout));

        return $checkout;
    }

    private function card(string $number = '4242424242424242'): array
    {
        return ['payment' => ['holder' => 'Persona Demo', 'number' => $number, 'expiry' => '12/30', 'cvc' => '123']];
    }

    private function approve(BookingCheckout $checkout): BookingCheckout
    {
        $this->actingAs($this->player)->post(route('checkout.pay', $checkout), $this->card())
            ->assertRedirect(route('checkout.show', $checkout))->assertSessionHasNoErrors();

        return $checkout->refresh();
    }
}
