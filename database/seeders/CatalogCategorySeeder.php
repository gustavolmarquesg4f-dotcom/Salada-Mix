<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CatalogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Beleza e Cuidados' => 'beleza-e-cuidados',
            'Tecnologia e Informática' => 'tecnologia-e-informatica',
            'Moda e Acessórios' => 'moda-e-acessorios',
            'Casa e Decoração' => 'casa-e-decoracao',
            'Games' => 'games',
            'Infantil e Brinquedos' => 'infantil-e-brinquedos',
            'Eletrodomésticos' => 'eletrodomesticos',
            'Esporte e Lazer' => 'esporte-e-lazer',
            'Papelaria' => 'papelaria',
            'Pet Shop' => 'pet-shop',
        ];

        $position = 1;

        foreach ($names as $name => $slug) {
            Category::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true, 'position' => $position]
            );
            $position++;
        }
    }
}

