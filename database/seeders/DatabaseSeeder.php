<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Categorias públicas, sem dados pessoais nem contas padrão.
        $this->call(CatalogCategorySeeder::class);
    }
}

