<?php

namespace App\Providers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The dev server runs MariaDB 5.5, where an index key part may be at most 767 bytes: a varchar(255) in utf8mb4
        // (4 bytes per character) is 1020, so unique/primary keys on strings would fail. 191 x 4 = 764. Validation
        // limits for string columns therefore use 191 as well.
        Schema::defaultStringLength(191);

        // Students only ever see published content; unpublished items behave as if they do not exist.
        Route::bind('question', fn (string $value) => Question::published()->findOrFail($value));
        Route::bind('exam', fn (string $value) => Exam::published()->findOrFail($value));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        // Guessing the current password through this endpoint must not be possible.
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(5)->by((string) $request->user()?->id));

        RateLimiter::for('samples', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
