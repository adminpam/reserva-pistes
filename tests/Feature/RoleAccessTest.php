<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Pruebas del middleware de roles: cada panel solo es accesible
// para su rol; el resto recibe 403 (o redireccion al login si es invitado).
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_jugador_no_entra_en_los_paneles(): void
    {
        $player = User::create([
            'name' => 'Pau', 'email' => 'pau@test.test',
            'password' => 'password1234', 'role' => 'player',
        ]);

        $this->actingAs($player)->get('/manager/courts')->assertForbidden();
        $this->actingAs($player)->get('/admin/users')->assertForbidden();
    }

    public function test_cada_rol_entra_en_su_panel(): void
    {
        $manager = User::create([
            'name' => 'Gema', 'email' => 'gema@test.test',
            'password' => 'password1234', 'role' => 'manager',
        ]);
        $admin = User::create([
            'name' => 'Adri', 'email' => 'adri@test.test',
            'password' => 'password1234', 'role' => 'admin',
        ]);

        $this->actingAs($manager)->get('/manager/courts')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_un_invitado_va_al_login(): void
    {
        $this->get('/my/bookings')->assertRedirect('/login');
    }
}
