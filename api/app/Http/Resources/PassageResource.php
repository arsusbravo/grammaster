<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Passage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A text that questions are about. Public: the text never contains a solution.
 *
 * @mixin Passage
 */
class PassageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
