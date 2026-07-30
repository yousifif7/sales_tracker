<?php

namespace App\Models;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'source',
        'status',
        'source_url',
        'website',
        'linkedin_url',
        'social_links',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => ContactSource::class,
            'status' => ContactStatus::class,
            'social_links' => 'array',
        ];
    }

    /**
     * Quick outreach links for UI action buttons.
     *
     * @return array<int, array{label: string, url: string, type: string}>
     */
    public function outreachLinks(): array
    {
        $links = [];

        if (filled($this->email)) {
            $links[] = [
                'label' => 'Email',
                'url' => 'mailto:'.$this->email,
                'type' => 'email',
            ];
        }

        if (filled($this->linkedin_url)) {
            $links[] = [
                'label' => 'LinkedIn',
                'url' => $this->linkedin_url,
                'type' => 'linkedin',
            ];
        }

        if (filled($this->website)) {
            $links[] = [
                'label' => 'Website',
                'url' => $this->website,
                'type' => 'website',
            ];
        }

        foreach (($this->social_links ?? []) as $network => $url) {
            if (! filled($url)) {
                continue;
            }

            $links[] = [
                'label' => str($network)->replace('_', ' ')->title()->toString(),
                'url' => $url,
                'type' => (string) $network,
            ];
        }

        if (filled($this->source_url) && ! collect($links)->contains(fn (array $link) => $link['url'] === $this->source_url)) {
            $links[] = [
                'label' => 'Source',
                'url' => $this->source_url,
                'type' => 'source',
            ];
        }

        return $links;
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function emailThreads(): HasMany
    {
        return $this->hasMany(EmailThread::class)->latest('last_message_at');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function getActivityTimelineAttribute(): array
    {
        $interactions = $this->interactions()
            ->with('response')
            ->latest('sent_at')
            ->latest('created_at')
            ->get()
            ->flatMap(function (Interaction $interaction): array {
                $items = [[
                    'type' => 'interaction',
                    'title' => ucfirst($interaction->direction->value).' '.$interaction->channel->label(),
                    'description' => $interaction->content,
                    'timestamp' => optional($interaction->sent_at ?? $interaction->created_at)->toDateTimeString(),
                ]];

                if ($interaction->response) {
                    $items[] = [
                        'type' => 'response',
                        'title' => 'Response: '.$interaction->response->outcome->label(),
                        'description' => $interaction->response->sentiment_notes,
                        'timestamp' => optional($interaction->response->created_at)->toDateTimeString(),
                    ];
                }

                return $items;
            });

        return $interactions
            ->sortByDesc('timestamp')
            ->values()
            ->all();
    }
}
