<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'presentation_id',
        'content',
        'time_limit_seconds',
        'order',
        'group_name',
    ];

    protected $casts = [
        'content' => 'array',
        'time_limit_seconds' => 'integer',
        'order' => 'integer',
    ];

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function correctOption(): ?Option
    {
        return $this->options()->where('is_correct', true)->first();
    }

    public function scopeGrouped($query, string $groupName)
    {
        return $query->where('group_name', $groupName);
    }
}
