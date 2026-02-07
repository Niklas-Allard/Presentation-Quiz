<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    use HasUuids;

    protected $fillable = [
        'presentation_id',
        'display_name',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }
}
