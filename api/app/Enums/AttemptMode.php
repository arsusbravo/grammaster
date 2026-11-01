<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttemptMode: string implements HasLabel
{
    case Practice = 'practice';
    case Exam = 'exam';

    public function getLabel(): string
    {
        return match ($this) {
            self::Practice => 'Oefenen',
            self::Exam => 'Examen',
        };
    }
}
