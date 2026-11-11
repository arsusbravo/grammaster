<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Passage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Passage> */
class PassageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'De verloren sleutel',
            'body' => "Sanne zocht overal naar haar sleutel. Ze keek onder de bank en in haar jaszak.\n\nUiteindelijk vond ze hem in de koelkast, naast de melk.",
        ];
    }
}
