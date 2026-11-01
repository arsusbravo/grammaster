<?php

use App\Services\SampleQuestions;
use Illuminate\Support\Facades\Route;

// The landing page is the home page; the student app lives at /student/, the Filament admin at /admin and the API at /api/v1.
Route::get('/', fn (SampleQuestions $samples) => view('landing', ['samples' => $samples->byLevel()]));

// The built student app (api/public/student) is a single-page app: its files are served as static files, and every
// other /student/... address (the page itself, deep links such as /student/examens) gets index.html so the Vue router
// can take over. On a server nginx does this itself; locally (Herd) this route does. Build it with `npm run watch`
// (or `npm run build:herd`) in /app.
Route::get('/student/{path?}', function (?string $path = null) {
    // A missing file (e.g. an old hashed asset) must be a real 404, not the HTML page.
    abort_if($path !== null && pathinfo($path, PATHINFO_EXTENSION) !== '', 404);

    $index = public_path('student/index.html');

    abort_unless(is_file($index), 503, 'The student app is not built yet. Run `npm run watch` in /app.');

    return response()->file($index, ['Cache-Control' => 'no-cache']);
})->where('path', '.*');
