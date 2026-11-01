<?php

use App\Http\Controllers\Api\V1\AttemptController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\ProgressController;
use App\Http\Controllers\Api\V1\QuestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('ping', fn () => response()->json(['status' => 'ok']));

    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Needed on the register screen, so public.
    Route::get('levels', [CatalogController::class, 'levels']);
    Route::get('topics', [CatalogController::class, 'topics']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/me', [AuthController::class, 'update']);
        Route::put('auth/password', [AuthController::class, 'changePassword'])->middleware('throttle:password');

        Route::get('questions', [QuestionController::class, 'index']);
        Route::post('questions/{question}/answer', [QuestionController::class, 'answer']);

        Route::get('exams', [ExamController::class, 'index']);
        Route::post('exams/{exam}/start', [ExamController::class, 'start']);

        Route::get('attempts/{attempt}', [AttemptController::class, 'show']);
        Route::put('attempts/{attempt}/answers/{question}', [AttemptController::class, 'saveAnswer']);
        Route::delete('attempts/{attempt}/answers/{question}', [AttemptController::class, 'deleteAnswer']);
        Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit']);
        Route::get('attempts/{attempt}/result', [AttemptController::class, 'result']);

        Route::get('progress', [ProgressController::class, 'show']);
    });
});
