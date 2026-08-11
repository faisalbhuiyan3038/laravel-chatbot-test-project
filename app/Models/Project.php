<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'aliases',
        'support_contacts',
        'is_active'
    ];

    protected $casts = [
        'aliases' => 'array',
        'support_contacts' => 'array',
        'is_active' => 'boolean',
    ];

    public function knowledgeChunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}
