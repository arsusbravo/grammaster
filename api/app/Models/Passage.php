<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PassageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A text (story) with questions about it. The body is plain text; a blank line starts a new paragraph. */
#[Fillable(['title', 'body'])]
class Passage extends Model
{
    /** @use HasFactory<PassageFactory> */
    use HasFactory;

    /** @return HasMany<Question, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
