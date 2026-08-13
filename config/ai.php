<?php

return [
    'embedding_provider' => env('AI_EMBEDDING_PROVIDER', 'ollama'),
    'chat_provider' => env('AI_CHAT_PROVIDER', 'ollama'),
    'translation_provider' => env('AI_TRANSLATION_PROVIDER', 'ollama'),
    'max_context_messages' => env('AI_MAX_CONTEXT_MESSAGES', 10),
    'enable_llm_banglish_translation' => env('AI_ENABLE_LLM_BANGLISH_TRANSLATION', true),

    'providers' => [
        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/v1'),
            'api_key' => env('OLLAMA_API_KEY', 'ollama'),
            'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'bge-m3'),
            'chat_model' => env('OLLAMA_CHAT_MODEL', 'qwen3:4b'),
            'translation_model' => env('OLLAMA_TRANSLATION_MODEL', 'qwen3:4b'),
        ],

        'gemini' => [
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
            'api_key' => env('GEMINI_API_KEY'),
            'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
            'chat_model' => env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash-lite'),
            'translation_model' => env('GEMINI_TRANSLATION_MODEL', 'gemini-2.5-flash-lite'),
        ],
        'groq' => [
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'api_key' => env('GROQ_API_KEY'),
            'embedding_model' => null,
            'chat_model' => env('GROQ_CHAT_MODEL', 'llama-3.3-70b-versatile'),
            'translation_model' => env('GROQ_TRANSLATION_MODEL', 'llama-3.3-70b-versatile'),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | AI-Driven Issue Actions
    |--------------------------------------------------------------------------
    |
    | Controls the AI-assisted issue creation and retrieval capabilities that
    | are layered on top of the existing RAG chatbot.
    |
    | enabled
    |   Master switch. Set to false to disable all AI issue actions and fall
    |   straight through to the existing RAG answerer for every message.
    |
    | creation_project_slug
    |   The project slug (from the 'projects' table) for which issue creation
    |   through the AI is permitted. Defaults to 'av-crm'. Change this only
    |   if/when the issue system is extended to other projects.
    |
    | max_details_chars
    |   Maximum character length for the 'details' field. Must match the
    |   'max:N' rule in IssueController::store() and IssueAiService.
    |
    */
    'issue_actions' => [
        'enabled'               => env('AI_ISSUE_ACTIONS_ENABLED', true),
        'creation_project_slug' => env('AI_ISSUE_CREATION_PROJECT_SLUG', 'av-crm'),
        'max_details_chars'     => env('AI_ISSUE_MAX_DETAILS_CHARS', 5000),
    ],
];