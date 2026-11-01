<?php

namespace App\Providers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Students only ever see published content; unpublished items behave as if they do not exist.
        Route::bind('question', fn (string $value) => Question::published()->findOrFail($value));
        Route::bind('exam', fn (string $value) => Exam::published()->findOrFail($value));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        // Guessing the current password through this endpoint must not be possible.
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(5)->by((string) $request->user()?->id));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
