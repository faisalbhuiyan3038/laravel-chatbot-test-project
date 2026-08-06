<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FaqChatController;
use App\Http\Controllers\FeedbackController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/chat', [FaqChatController::class, 'index'])->name('chat.index');
Route::post('/chat/ask', [FaqChatController::class, 'ask'])
    ->middleware('throttle:30,1')
    ->name('chat.ask');

Route::post('/chat/feedback', [FeedbackController::class, 'store'])->name('chat.feedback');
Route::get('/admin/feedbacks', [FeedbackController::class, 'dashboard'])->name('admin.feedbacks');
Route::delete('/admin/feedbacks/{id}', [FeedbackController::class, 'destroy'])->name('admin.feedbacks.destroy');