<?php

namespace App\Models;

use App\Jobs\RunLeadSearchJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSearchQuery extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'criteria',
        'raw_results',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_results' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $leadSearchQuery): void {
            RunLeadSearchJob::dispatch($leadSearchQuery->id);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
