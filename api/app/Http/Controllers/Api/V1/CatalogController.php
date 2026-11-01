<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LevelResource;
use App\Http\Resources\TopicResource;
use App\Models\Level;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function levels(): AnonymousResourceCollection
    {
        return LevelResource::collection(Level::orderBy('sort_order')->get());
    }

    /** Optional ?level=slug limits the published question counts to that level. */
    public function topics(Request $request): AnonymousResourceCollection
    {
        $level = $request->query('level');

        return TopicResource::collection(
            Topic::orderBy('sort_order')
                ->withCount(['questions' => fn ($q) => $q->published()
                    ->when($level, fn ($q) => $q->whereHas('level', fn ($l) => $l->where('slug', $level)))])
                ->get()
        );
    }
}
