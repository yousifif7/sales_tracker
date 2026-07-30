<?php

return [
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
    'api_key' => env('OPENROUTER_API_KEY'),
    // Prefer a cheap model. Web search is attached via tools (do not rely on :online).
    'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),

    /*
    |--------------------------------------------------------------------------
    | Web search budget (OpenRouter server tool)
    |--------------------------------------------------------------------------
    |
    | Each Exa/Perplexity search costs ~$0.005 plus the tokens injected into
    | the prompt. Balanced defaults: enough searches for real verification,
    | without the previous high-context / 10-search spend.
    |
    */
    'web_search' => [
        'engine' => env('OPENROUTER_WEB_SEARCH_ENGINE', 'auto'),
        'max_results' => (int) env('OPENROUTER_WEB_SEARCH_MAX_RESULTS', 4),
        'max_uses' => (int) env('OPENROUTER_WEB_SEARCH_MAX_USES', 5),
        'max_total_results' => (int) env('OPENROUTER_WEB_SEARCH_MAX_TOTAL_RESULTS', 16),
        'max_tool_calls' => (int) env('OPENROUTER_WEB_SEARCH_MAX_TOOL_CALLS', 5),
        // medium ≈ 15k chars/result on Exa; max_characters caps actual injected text.
        'search_context_size' => env('OPENROUTER_WEB_SEARCH_CONTEXT_SIZE', 'medium'),
        'max_characters' => (int) env('OPENROUTER_WEB_SEARCH_MAX_CHARACTERS', 2200),
        'max_leads' => (int) env('OPENROUTER_LEAD_SEARCH_MAX_LEADS', 5),
        // Drop leads whose company/person is not evidenced in web-search citations.
        'require_web_evidence' => filter_var(
            env('OPENROUTER_REQUIRE_WEB_EVIDENCE', true),
            FILTER_VALIDATE_BOOL,
        ),
    ],
];
