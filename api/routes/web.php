<?php

use Illuminate\Support\Facades\Route;

// The student app lives at /student/ and the Filament admin at /admin; the API at /api/v1.
// (nginx redirects "/" itself; this covers `php artisan serve`.)
Route::redirect('/', '/student/');
