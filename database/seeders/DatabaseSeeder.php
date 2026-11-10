<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Порядок важен: сначала справочник спорта и пользователи,
// потом демо-клуб, который на них ссылается.
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SportSeeder::class,
            UserSeeder::class,
            DemoClubSeeder::class,
        ]);
    }
}
