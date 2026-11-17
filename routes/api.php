<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use Illuminate\Support\Facades\Route;

// Book ids are plain integers; anything else (or a huge number) is just "not found".
Route::pattern('book', '[0-9]{1,18}');

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Any valid token (even read-only) may log itself out.
    Route::post('/logout', [AuthController::class, 'logout']);

    // Needs a token with the "books:read" ability.
    Route::middleware('abilities:books:read')->group(function () {
        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/{book}', [BookController::class, 'show']);
    });

    // Needs a token with the "books:write" ability. A read-only token gets 403.
    Route::middleware('abilities:books:write')->group(function () {
        Route::post('/tokens/read-only', [AuthController::class, 'readOnlyToken']);
        Route::post('/books', [BookController::class, 'store']);
        Route::match(['put', 'patch'], '/books/{book}', [BookController::class, 'update']);
        Route::delete('/books/{book}', [BookController::class, 'destroy']);
    });
});
