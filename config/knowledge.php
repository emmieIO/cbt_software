<?php

return [
    'curriculum' => [
        'model' => env('CURRICULUM_TOPIC_AI_MODEL', 'deepseek-chat'),
        'batch_characters' => (int) env('CURRICULUM_TOPIC_BATCH_CHARACTERS', 16000),
        'requests_per_job' => (int) env('CURRICULUM_TOPIC_REQUESTS_PER_JOB', 3),
        'timeout' => 120,
    ],
    'embeddings' => [
        'provider' => 'ollama',
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_EMBEDDING_MODEL', 'embeddinggemma-2'),
        'dimensions' => (int) env('OLLAMA_EMBEDDING_DIMENSIONS', 768),
        'connect_timeout' => (int) env('OLLAMA_EMBEDDING_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('OLLAMA_EMBEDDING_TIMEOUT', 120),
    ],

    'retrieval' => [
        'candidate_count' => (int) env('KNOWLEDGE_RETRIEVAL_CANDIDATE_COUNT', 10),
        'similarity_threshold' => (float) env('KNOWLEDGE_RETRIEVAL_SIMILARITY_THRESHOLD', 0.65),
    ],

    'generation' => [
        'provider' => 'deepseek',
        'model' => env('QUESTION_GENERATOR_AI_MODEL', 'deepseek-chat'),
        'max_questions' => (int) env('QUESTION_GENERATOR_MAX_QUESTIONS', 30),
        'retrieval_limit' => (int) env('QUESTION_GENERATOR_RETRIEVAL_LIMIT', 12),
        'context_token_budget' => (int) env('QUESTION_GENERATOR_CONTEXT_TOKEN_BUDGET', 6000),
        'minimum_similarity' => (float) env('QUESTION_GENERATOR_MINIMUM_SIMILARITY', 0.65),
        'timeout' => (int) env('QUESTION_GENERATOR_TIMEOUT', 180),
        'max_instruction_length' => (int) env('QUESTION_GENERATOR_MAX_INSTRUCTION_LENGTH', 1000),
    ],
];
