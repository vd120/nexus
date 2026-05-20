<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::middleware(['auth', 'suspended', 'verified', 'password.set'])->group(function () {
    Route::get('/chat/mini/{slug}', [ChatController::class, 'miniShow'])->name('chat.mini-show');
});
