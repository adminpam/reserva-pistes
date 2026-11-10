<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// Тестовые пользователи — ВЫМЫШЛЕННЫЕ, только для локальной разработки и демо.
// Пароль берётся из переменной окружения SEED_PASSWORD (по умолчанию 'password'
// для локалки). Это не секрет продакшена: настоящие пользователи регистрируются
// сами на развёрнутом сайте.
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'password');

        $users = [
            ['name' => 'Admin Demo', 'email' => 'admin@reservapistes.test', 'role' => 'admin'],
            ['name' => 'Gestor Demo', 'email' => 'gestor@reservapistes.test', 'role' => 'manager'],
            ['name' => 'Marta Jugadora', 'email' => 'marta@reservapistes.test', 'role' => 'player'],
            ['name' => 'Pau Jugador', 'email' => 'pau@reservapistes.test', 'role' => 'player'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [...$user, 'password' => $password],
            );
        }
    }
}
