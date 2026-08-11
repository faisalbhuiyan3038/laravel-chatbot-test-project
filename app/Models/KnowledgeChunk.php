<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'type',
        'language',
        'title',
        'content',
        'embedding',
        'embedding_model',
        'embedding_norm',
        'embedded_at'
    ];

    protected $casts = [
        'embedded_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
