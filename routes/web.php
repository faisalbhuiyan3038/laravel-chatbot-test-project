<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FaqChatController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/chat', [FaqChatController::class, 'index'])->name('chat.index');
Route::post('/chat/ask', [FaqChatController::class, 'ask'])
    ->middleware('throttle:30,1')
    ->name('chat.ask');