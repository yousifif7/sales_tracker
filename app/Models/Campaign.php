<?php

namespace App\Models;

use App\Enums\CampaignChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'channel',
        'start_date',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => CampaignChannel::class,
            'start_date' => 'date',
        ];
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function emailThreads(): HasMany
    {
        return $this->hasMany(EmailThread::class);
    }
}
