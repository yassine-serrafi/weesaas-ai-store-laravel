<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Volontairement vide : le compte administrateur et les réglages de base
     * (nom de boutique, langue, devise) sont créés par l'assistant
     * d'installation (public/install.php). Une boutique fraîche démarre donc
     * propre, sans données de démo.
     */
    public function run(): void
    {
        //
    }
}
