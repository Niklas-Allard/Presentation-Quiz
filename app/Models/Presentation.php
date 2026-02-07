<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presentation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'admin_code',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questionsByGroup()
    {
        $questions = $this->questions()->with('options')->get();

        return $questions->groupBy(fn ($q) => $q->group_name ?? 'Questions');
    }
}
