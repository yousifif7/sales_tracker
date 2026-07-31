<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Services\LeadSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeadSearchServiceTest extends TestCase
{
    use RefreshDatabase;

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
                                    'linkedin_url' => 'https://www.linkedin.com/in/ada-lovelace',
                                    'social_links' => [
                                        'instagram' => 'https://instagram.com/ada',
                                    ],
                                    'source_url' => 'https://analyticalengines.com/team',
                                ],
                                [
                                    'name' => 'Grace Hopper',
                                    'company' => 'Navy Computing',
                                    'email' => '',
                                    'website' => 'https://navycomputing.example.org',
                                    'linkedin_url' => null,
                                    'social_links' => [],
                                    'source_url' => 'https://navycomputing.example.org/about',
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
        $this->assertSame('https://www.linkedin.com/in/ada-lovelace', $result['results'][0]['linkedin_url']);
        $this->assertSame('https://instagram.com/ada', $result['results'][0]['social_links']['instagram']);
        $this->assertSame('Grace Hopper', $result['results'][1]['name']);
        $this->assertNull($result['results'][1]['email']);
        $this->assertArrayHasKey('raw_response', $result);

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && data_get($data, 'model') === 'openai/gpt-4o-mini'
                && data_get($data, 'temperature') === 0
                && data_get($data, 'tools.0.type') === 'openrouter:web_search'
                && data_get($data, 'tools.0.parameters.max_uses') === 8
                && data_get($data, 'tools.0.parameters.search_context_size') === 'medium'
                && data_get($data, 'max_tool_calls') === 8;
        });
    }

    public function test_it_keeps_only_leads_backed_by_web_citations(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'openrouter.web_search.require_web_evidence' => true,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'name' => 'Mark Nicklin',
                                    'role' => 'Managing Director',
                                    'company' => 'Secur80 Ltd',
                                    'email' => 'info@secur80.co.uk',
                                    'website' => 'https://secur80.co.uk/',
                                    'linkedin_url' => 'https://www.linkedin.com/in/mark-nicklin-940149357',
                                    'social_links' => [],
                                    'source_url' => 'https://secur80.co.uk/about/',
                                ],
                                [
                                    'name' => 'Fake Person',
                                    'role' => 'Director',
                                    'company' => 'Invented Guards',
                                    'email' => null,
                                    'website' => 'https://inventedguards.co.uk/',
                                    'linkedin_url' => 'https://www.linkedin.com/in/fake-person-abc',
                                    'social_links' => [],
                                    'source_url' => 'https://inventedguards.co.uk/about',
                                ],
                            ]),
                            'annotations' => [
                                [
                                    'type' => 'url_citation',
                                    'url_citation' => [
                                        'url' => 'https://secur80.co.uk/about/',
                                        'title' => 'About - Secur80 Ltd',
                                        'content' => 'Secur80 remains within the family with Mark Nicklin and his wife Kelly at the helm.',
                                    ],
                                ],
                                [
                                    'type' => 'url_citation',
                                    'url_citation' => [
                                        'url' => 'https://www.linkedin.com/in/mark-nicklin-940149357',
                                        'title' => 'Mark Nicklin',
                                        'content' => 'Managing Director at Secur80 Ltd',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('UK security firms');

        $this->assertCount(1, $result['results']);
        $this->assertSame('Mark Nicklin', $result['results'][0]['name']);
        $this->assertSame('https://www.linkedin.com/in/mark-nicklin-940149357', $result['results'][0]['linkedin_url']);
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
                            'content' => "```json\n[{\"name\":\"Alan Turing\",\"company\":\"Bombe Labs\",\"email\":null,\"website\":\"https://bombelabs.co.uk\",\"source_url\":\"https://bombelabs.co.uk/team\"}]\n```",
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

    public function test_it_rejects_null_strings_placeholder_names_and_fake_linkedin_urls(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'openrouter.web_search.min_leads' => 1,
            'openrouter.web_search.require_web_evidence' => false,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'name' => 'Jordan Wiggins',
                                    'role' => 'Business Development Manager',
                                    'company' => 'Milne Security Services',
                                    'email' => 'null',
                                    'website' => 'https://milnesecurityservices.co.uk/',
                                    'linkedin_url' => 'https://www.linkedin.com/in/jordan-wiggins-123456',
                                    'company_linkedin_url' => 'https://www.linkedin.com/company/milne-security-services',
                                    'social_links' => [],
                                    'source_url' => 'https://milnesecurityservices.co.uk/',
                                ],
                                [
                                    'name' => 'Michelle Dunn',
                                    'role' => 'Administration Officer',
                                    'company' => 'Milne Security Services',
                                    'email' => 'null',
                                    'website' => 'https://milnesecurityservices.co.uk/',
                                    'linkedin_url' => 'null',
                                    'company_linkedin_url' => 'https://www.linkedin.com/company/milne-security-services',
                                    'social_links' => [],
                                    'source_url' => 'https://milnesecurityservices.co.uk/',
                                ],
                                [
                                    'name' => 'John Doe',
                                    'role' => 'Owner',
                                    'company' => 'H&D Security UK',
                                    'email' => null,
                                    'website' => 'https://handdsecurity.co.uk/',
                                    'linkedin_url' => 'https://null',
                                    'social_links' => [],
                                    'source_url' => 'https://handdsecurity.co.uk/',
                                ],
                                [
                                    'name' => 'Jane Doe',
                                    'role' => 'Director',
                                    'company' => 'Made Up Security',
                                    'email' => null,
                                    'website' => 'https://madeupsecurity.example',
                                    'linkedin_url' => null,
                                    'social_links' => [],
                                    'source_url' => 'https://madeupsecurity.example',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('UK security firms');

        $this->assertCount(1, $result['results']);
        $this->assertSame('Jordan Wiggins', $result['results'][0]['name']);
        $this->assertNull($result['results'][0]['email']);
        $this->assertNull($result['results'][0]['linkedin_url']);
        $this->assertSame(
            'https://www.linkedin.com/company/milne-security-services',
            $result['results'][0]['social_links']['company_linkedin'],
        );
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

    public function test_it_excludes_leads_already_in_crm(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'openrouter.web_search.require_web_evidence' => false,
        ]);

        Contact::query()->create([
            'name' => 'David Foster',
            'company' => 'Risk Secured Ltd',
            'email' => 'david@risksecured.co.uk',
            'source' => 'manual',
            'status' => 'contacted',
            'website' => 'https://www.risksecured.co.uk',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'name' => 'David Foster',
                                    'role' => 'Director & Co-Owner',
                                    'company' => 'Risk Secured Ltd',
                                    'email' => null,
                                    'website' => 'https://risksecured.co.uk',
                                    'linkedin_url' => null,
                                    'social_links' => [],
                                    'source_url' => 'https://risksecured.co.uk/about',
                                ],
                                [
                                    'name' => 'Sabia Kauser',
                                    'role' => 'CEO',
                                    'company' => 'Eagle Security Protection Ltd',
                                    'email' => 'info@eaglesecurityprotection.co.uk',
                                    'website' => 'https://eaglesecurityprotection.co.uk',
                                    'linkedin_url' => null,
                                    'social_links' => [],
                                    'source_url' => 'https://eaglesecurityprotection.co.uk/about',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('UK security firms');

        $this->assertCount(1, $result['results']);
        $this->assertSame('Sabia Kauser', $result['results'][0]['name']);

        Http::assertSent(function (Request $request): bool {
            $prompt = (string) data_get($request->data(), 'messages.1.content');

            return str_contains($prompt, 'CRM exclude list')
                && str_contains($prompt, 'risk secured')
                && str_contains($prompt, 'risksecured.co.uk');
        });
    }

    public function test_it_honors_max_from_criteria(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'openrouter.web_search.max_leads' => 5,
            'openrouter.web_search.require_web_evidence' => false,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '[]',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $service->search('Find UK security firms. Strict JSON, max 10.');

        Http::assertSent(function (Request $request): bool {
            $prompt = (string) data_get($request->data(), 'messages.1.content');

            return str_contains($prompt, 'Target 10')
                && str_contains($prompt, 'Max 10 leads')
                && str_contains($prompt, 'first AND last');
        });
    }

    public function test_it_rejects_single_names_and_placeholder_people(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'openrouter.web_search.require_web_evidence' => false,
            'openrouter.web_search.min_leads' => 1,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'name' => 'Bobby',
                                    'role' => 'Director',
                                    'company' => 'ALG Security Ltd',
                                    'email' => 'info@algsecurity.co.uk',
                                    'website' => 'https://www.algsecurity.co.uk/',
                                    'source_url' => 'https://www.algsecurity.co.uk/',
                                ],
                                [
                                    'name' => 'John Doe',
                                    'role' => 'Managing Director',
                                    'company' => 'JD Security Solutions Ltd',
                                    'website' => 'https://www.jd-security.co.uk/',
                                    'source_url' => 'https://www.jd-security.co.uk/',
                                ],
                                [
                                    'name' => 'Spencer Martin',
                                    'role' => 'Managing Director',
                                    'company' => 'CSM Security',
                                    'email' => 'info@csmsecurity.co.uk',
                                    'website' => 'https://csmsecurity.co.uk',
                                    'source_url' => 'https://csmsecurity.co.uk/meet-the-team/spencer-martin/',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('North Wales guarding firms max 5');

        $this->assertCount(1, $result['results']);
        $this->assertSame('Spencer Martin', $result['results'][0]['name']);
    }

    public function test_it_runs_refill_pass_when_below_min_leads(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'openrouter.web_search.require_web_evidence' => true,
            'openrouter.web_search.min_leads' => 2,
            'openrouter.web_search.max_leads' => 5,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    [
                                        'name' => 'Spencer Martin',
                                        'role' => 'Managing Director',
                                        'company' => 'CSM Security',
                                        'website' => 'https://csmsecurity.co.uk',
                                        'source_url' => 'https://csmsecurity.co.uk/meet-the-team/spencer-martin/',
                                    ],
                                ]),
                                'annotations' => [
                                    [
                                        'type' => 'url_citation',
                                        'url_citation' => [
                                            'url' => 'https://csmsecurity.co.uk/meet-the-team/spencer-martin/',
                                            'content' => 'Spencer Martin Managing Director of CSM Security',
                                        ],
                                    ],
                                    [
                                        'type' => 'url_citation',
                                        'url_citation' => [
                                            'url' => 'https://castellsecurityltd.com/',
                                            'content' => 'Castell Security Ltd North Wales manned guarding',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    [
                                        'name' => 'Owain Davies',
                                        'role' => 'Director',
                                        'company' => 'Castell Security Ltd',
                                        'website' => 'https://castellsecurityltd.com',
                                        'source_url' => 'https://castellsecurityltd.com/about',
                                    ],
                                ]),
                                'annotations' => [
                                    [
                                        'type' => 'url_citation',
                                        'url_citation' => [
                                            'url' => 'https://castellsecurityltd.com/about',
                                            'content' => 'Owain Davies is Director at Castell Security Ltd',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('North Wales guarding firms');

        $this->assertCount(2, $result['results']);
        $this->assertSame('Spencer Martin', $result['results'][0]['name']);
        $this->assertSame('Owain Davies', $result['results'][1]['name']);
        $this->assertArrayHasKey('refill', $result['raw_response']);
    }

    public function test_it_keeps_one_lead_per_company_preferring_md(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'openrouter.web_search.require_web_evidence' => false,
            'openrouter.web_search.min_leads' => 1,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'name' => 'Kaled Miah',
                                    'role' => 'Operations Director',
                                    'company' => 'Region Security Guarding',
                                    'website' => 'https://regionsecurityguarding.co.uk/',
                                    'source_url' => 'https://regionsecurityguarding.co.uk/meet-the-team/',
                                ],
                                [
                                    'name' => 'Zachariah Islam',
                                    'role' => 'Managing Director',
                                    'company' => 'Region Security Guarding',
                                    'website' => 'https://regionsecurityguarding.co.uk/',
                                    'source_url' => 'https://regionsecurityguarding.co.uk/about-us/',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('North Wales firms max 5');

        $this->assertCount(1, $result['results']);
        $this->assertSame('Zachariah Islam', $result['results'][0]['name']);
    }

    public function test_it_accepts_full_name_alias_and_cited_source_host(): void
    {
        config([
            'openrouter.api_key' => 'test-key',
            'openrouter.model' => 'openai/gpt-4o-mini',
            'openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'openrouter.web_search.require_web_evidence' => true,
            'openrouter.web_search.min_leads' => 1,
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'full_name' => 'Andy Butterfield',
                                    'role' => 'Managing Director',
                                    'company' => 'Corvus Security Ltd',
                                    'public_email' => null,
                                    'website' => 'https://www.corvussecurity.co.uk',
                                    'source_url' => 'https://www.corvus.co.uk/contact-us/',
                                ],
                            ]),
                            'annotations' => [
                                [
                                    'type' => 'url_citation',
                                    'url_citation' => [
                                        'url' => 'https://www.corvus.co.uk/contact-us/',
                                        'content' => 'Managing Director, Andy Butterfield Ba(Hons)',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new LeadSearchService(app(Factory::class));
        $result = $service->search('North Wales firms max 5');

        $this->assertCount(1, $result['results']);
        $this->assertSame('Andy Butterfield', $result['results'][0]['name']);
    }
}
