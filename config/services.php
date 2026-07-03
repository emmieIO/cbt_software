<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'deepseek' => [
        'base_uri' => env('DEEPSEEK_BASE_URI'),
        'api_key' => env('DEEPSEEK_API_KEY'),

    ],

    'pdf_ocr' => [
        'enabled' => env('PDF_OCR_ENABLED', true),
        'pdftoppm_binary' => env('PDF_OCR_PDFTOPPM_BINARY', 'pdftoppm'),
        'tesseract_binary' => env('PDF_OCR_TESSERACT_BINARY', 'tesseract'),
        'language' => env('PDF_OCR_LANGUAGE', 'eng'),
        'dpi' => env('PDF_OCR_DPI', 250),
        'max_pages' => env('PDF_OCR_MAX_PAGES', 25),
        'minimum_text_characters' => env('PDF_OCR_MINIMUM_TEXT_CHARACTERS', 10),
        'render_timeout' => env('PDF_OCR_RENDER_TIMEOUT', 180),
        'page_timeout' => env('PDF_OCR_PAGE_TIMEOUT', 60),
    ],

];
