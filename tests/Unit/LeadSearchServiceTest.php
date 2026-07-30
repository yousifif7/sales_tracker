<?php

namespace Tests\Unit;

use App\Services\LeadSearchService;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeadSearchServiceTest extends TestCase
{
    public function test_it_parses_strict_json_leads_from_openrouter(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini:online',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'name' => 'Ada Lovelace',
                                    'role' => 'Founder',
                                    'company' => 'Analytical Engines',
                                    'email' => 'ada@example.com',
                                    'website' => 'analyticalengines.com',
                                    'linkedin_url' => 'https://www.linkedin.com/in/ada',
                                    'social_links' => [
                                        'instagram' => 'https://instagram.com/ada',
                                    ],
                                    'source_url' => 'https://example.com/ada',
                                ],
                                [
                                    'name' => 'Grace Hopper',
                                    'company' => null,
                                    'email' => '',
                                    'website' => null,
                                    'linkedin_url' => null,
                                    'social_links' => [],
                                    'source_url' => null,
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('SaaS founders in Europe');

        $this->assertCount(2, $result['results']);
        $this->assertSame('Ada Lovelace', $result['results'][0]['name']);
        $this->assertSame('ada@example.com', $result['results'][0]['email']);
        $this->assertSame('https://analyticalengines.com', $result['results'][0]['website']);
        $this->assertSame('https://www.linkedin.com/in/ada', $result['results'][0]['linkedin_url']);
        $this->assertSame('https://instagram.com/ada', $result['results'][0]['social_links']['instagram']);
        $this->assertSame('Grace Hopper', $result['results'][1]['name']);
        $this->assertNull($result['results'][1]['email']);
        $this->assertArrayHasKey('raw_response', $result);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && data_get($request->data(), 'model') === 'openai/gpt-4o-mini:online';
        });
    }

    public function test_it_strips_markdown_fences_before_parsing(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini:online',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "```json\n[{\"name\":\"Alan Turing\",\"company\":\"Bombe Labs\",\"email\":null,\"source_url\":null}]\n```",
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('AI researchers');

        $this->assertCount(1, $result['results']);
        $this->assertSame('Alan Turing', $result['results'][0]['name']);
        $this->assertSame('Bombe Labs', $result['results'][0]['company']);
    }

    public function test_it_rejects_invalid_json_payloads(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini:online',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'not-json',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lead search response was not valid JSON.');

        $service = new LeadSearchService(app(Factory::class));
        $service->search('broken response');
    }
}
