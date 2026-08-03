<?php

return [
    'embedding_provider' => env('AI_EMBEDDING_PROVIDER', 'ollama'),
    'chat_provider' => env('AI_CHAT_PROVIDER', 'ollama'),

    'providers' => [
        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/v1'),
            'api_key' => env('OLLAMA_API_KEY', 'ollama'),
            'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'bge-m3'),
            'chat_model' => env('OLLAMA_CHAT_MODEL', 'qwen3:4b'),
        ],

        'gemini' => [
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
            'api_key' => env('GEMINI_API_KEY'),
            'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
            'chat_model' => env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash-lite'),
        ],
        'groq' => [
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'api_key' => env('GROQ_API_KEY'),
            'embedding_model' => null,
            'chat_model' => env('GROQ_CHAT_MODEL', 'llama-3.3-70b-versatile'),
        ]
    ],
];