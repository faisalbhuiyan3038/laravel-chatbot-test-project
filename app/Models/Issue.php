<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    use HasFactory;

    public const STATUS_OPEN = '0';
    public const STATUS_LOCKED = '1';
    public const STATUS_IN_PROGRESS = '2';
    public const STATUS_RESOLVED = '3';
    public const STATUS_CLOSED = '4';

    public const STATUSES = [
        '0' => 'Open',
        '1' => 'Locked',
        '2' => 'In Progress',
        '3' => 'Resolved',
        '4' => 'Closed',
    ];

    protected $fillable = [
        'user_id',
        'issue_category_id',
        'issue_date',
        'details',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IssueCategory::class, 'issue_category_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IssueAttachment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Unknown';
    }
}
