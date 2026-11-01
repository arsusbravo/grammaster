<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ExamQuestion extends Pivot
{
    public $incrementing = true;

    protected $table = 'exam_questions';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
