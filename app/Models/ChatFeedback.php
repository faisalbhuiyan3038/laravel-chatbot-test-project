<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatFeedback extends Model
{
    protected $table = 'chat_feedbacks';

    protected $fillable = [
        'question',
        'answer',
        'rating',
        'feedback_text',
        'sources',
        'grounded',
    ];

    protected $casts = [
        'sources'  => 'array',
        'grounded' => 'boolean',
    ];
}
