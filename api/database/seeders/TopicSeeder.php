<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            ['slug' => 'werkwoordspelling', 'name' => 'Werkwoordspelling (d/t/dt)', 'description' => 'Wanneer schrijf je d, t of dt? Persoonsvormen, voltooid deelwoord en \'t kofschip.'],
            ['slug' => 'lidwoorden', 'name' => 'Lidwoorden (de/het)', 'description' => 'Welk lidwoord hoort bij welk zelfstandig naamwoord?'],
            ['slug' => 'voornaamwoorden', 'name' => 'Voornaamwoorden', 'description' => 'Persoonlijke en bezittelijke voornaamwoorden, zoals hen/hun en zij/ze.'],
            ['slug' => 'zinsontleding', 'name' => 'Zinsontleding', 'description' => 'Onderwerp, persoonsvorm, voorwerpen, bepalingen en de volgorde van woorden in een zin.'],
            ['slug' => 'samengestelde-woorden', 'name' => 'Samengestelde woorden', 'description' => 'Aaneenschrijven, tussenklanken en streepjes.'],
            ['slug' => 'hoofdletters', 'name' => 'Hoofdletters', 'description' => 'Wanneer gebruik je een hoofdletter en wanneer een kleine letter?'],
            ['slug' => 'leestekens', 'name' => 'Leestekens', 'description' => 'Komma, punt, puntkomma, dubbele punt en apostrof.'],
        ];

        foreach ($topics as $index => $topic) {
            Topic::updateOrCreate(['slug' => $topic['slug']], $topic + ['sort_order' => $index + 1]);
        }
    }
}
