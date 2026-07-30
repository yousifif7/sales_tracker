<?php

namespace App\Models;

use App\Enums\ResponseOutcome;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'interaction_id',
        'outcome',
        'sentiment_notes',
        'follow_up_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => ResponseOutcome::class,
            'follow_up_date' => 'date',
        ];
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }
}
