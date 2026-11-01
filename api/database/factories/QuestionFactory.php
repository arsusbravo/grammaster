<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Level;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Question> */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'level_id' => Level::factory(),
            'type' => QuestionType::MultipleChoice,
            'prompt' => 'Welke zin is goed gespeld?',
            'data' => [
                'options' => ['Hij word boos.', 'Hij wordt boos.', 'Hij wort boos.'],
                'correct' => [1],
            ],
            'explanation' => 'Bij hij krijgt het werkwoord een -t: hij wordt.',
            'difficulty' => 1,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function multipleChoice(): static
    {
        return $this->state([]);
    }

    public function fillBlank(): static
    {
        return $this->state([
            'type' => QuestionType::FillBlank,
            'prompt' => 'Vul de juiste vorm van het werkwoord in.',
            'data' => [
                'text' => 'Mijn broer {{blank}} in een ziekenhuis.',
                'blanks' => [['accepted' => ['werkt'], 'hint' => 'werken']],
                'case_sensitive' => false,
                'ignore_extra_whitespace' => true,
            ],
        ]);
    }

    public function orderWords(): static
    {
        return $this->state([
            'type' => QuestionType::OrderWords,
            'prompt' => 'Zet de woorden in de goede volgorde.',
            'data' => [
                'words' => ['Gisteren', 'ging', 'ik', 'zwemmen'],
                'alternative_orders' => [['Ik', 'ging', 'gisteren', 'zwemmen']],
                'case_sensitive' => false,
            ],
        ]);
    }

    public function markError(): static
    {
        return $this->state([
            'type' => QuestionType::MarkError,
            'prompt' => 'Tik op het woord dat fout gespeld is.',
            'data' => [
                'tokens' => ['Wij', 'fietst', 'naar', 'school'],
                'error_index' => 1,
                'correction' => 'fietsen',
            ],
        ]);
    }

    public function parseSentence(): static
    {
        return $this->state([
            'type' => QuestionType::ParseSentence,
            'prompt' => 'Wat is de functie van elk zinsdeel?',
            'data' => [
                'tokens' => [
                    ['text' => 'De kat', 'role' => 'onderwerp'],
                    ['text' => 'eet', 'role' => 'persoonsvorm'],
                    ['text' => 'de vis', 'role' => 'lijdend voorwerp'],
                ],
                'roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp', 'bijwoordelijke bepaling'],
            ],
        ]);
    }
}
