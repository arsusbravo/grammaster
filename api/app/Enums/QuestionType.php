<?php

declare(strict_types=1);

namespace App\Enums;

use App\Questions\FillBlankHandler;
use App\Questions\MarkErrorHandler;
use App\Questions\MultipleChoiceHandler;
use App\Questions\OrderWordsHandler;
use App\Questions\ParseSentenceHandler;
use App\Questions\QuestionHandler;
use Filament\Support\Contracts\HasLabel;

enum QuestionType: string implements HasLabel
{
    case MultipleChoice = 'multiple_choice';
    case FillBlank = 'fill_blank';
    case OrderWords = 'order_words';
    case MarkError = 'mark_error';
    case ParseSentence = 'parse_sentence';

    /** Dutch name shown in the admin. */
    public function getLabel(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Meerkeuze',
            self::FillBlank => 'Invullen',
            self::OrderWords => 'Woordvolgorde',
            self::MarkError => 'Fout aanwijzen',
            self::ParseSentence => 'Zinsontleding',
        };
    }

    /** Behaviour for hiding, validating, checking and revealing answers of this type. */
    public function handler(): QuestionHandler
    {
        return match ($this) {
            self::MultipleChoice => new MultipleChoiceHandler,
            self::FillBlank => new FillBlankHandler,
            self::OrderWords => new OrderWordsHandler,
            self::MarkError => new MarkErrorHandler,
            self::ParseSentence => new ParseSentenceHandler,
        };
    }

    /**
     * Basic structure of the `data` JSON per type. Cross-field checks live in App\Support\QuestionData.
     *
     * @return array<string, mixed>
     */
    public function dataRules(): array
    {
        return match ($this) {
            self::MultipleChoice => [
                'options' => ['required', 'array', 'min:2'],
                'options.*' => ['required', 'string'],
                'correct' => ['required', 'array', 'min:1'],
                'correct.*' => ['integer', 'min:0'],
            ],
            self::FillBlank => [
                'text' => ['required', 'string'],
                'blanks' => ['required', 'array', 'min:1'],
                'blanks.*.accepted' => ['required', 'array', 'min:1'],
                'blanks.*.accepted.*' => ['required', 'string'],
                'blanks.*.hint' => ['nullable', 'string'],
                'case_sensitive' => ['sometimes', 'boolean'],
                'ignore_extra_whitespace' => ['sometimes', 'boolean'],
            ],
            self::OrderWords => [
                'words' => ['required', 'array', 'min:2'],
                'words.*' => ['required', 'string'],
                'alternative_orders' => ['sometimes', 'array'],
                'alternative_orders.*' => ['array'],
                'alternative_orders.*.*' => ['string'],
                'case_sensitive' => ['sometimes', 'boolean'],
            ],
            self::MarkError => [
                'tokens' => ['required', 'array', 'min:2'],
                'tokens.*' => ['required', 'string'],
                'error_index' => ['required', 'integer', 'min:0'],
                'correction' => ['required', 'string'],
            ],
            self::ParseSentence => [
                'tokens' => ['required', 'array', 'min:2'],
                'tokens.*.text' => ['required', 'string'],
                'tokens.*.role' => ['required', 'string'],
                'roles' => ['required', 'array', 'min:2'],
                'roles.*' => ['required', 'string'],
            ],
        };
    }
}
