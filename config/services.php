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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        // Backup keys tried in order if the primary hits its free-tier
        // daily limit (or is otherwise failing) — see App\Services\Ai\AiClient.
        'key_2' => env('GEMINI_API_KEY_2'),
        'key_3' => env('GEMINI_API_KEY_3'),
        'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),
    ],

    // Last resort in the AiClient fallback chain, after every Gemini key.
    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    // Optional standalone TorchXRayVision service (xray-vision-service/) —
    // enriches chest X-ray reports with real classifier output. Purely
    // additive: unset means the app falls back to Gemini-only vision
    // description, exactly as it behaves today.
    'xray_vision' => [
        'url' => env('XRAY_VISION_URL'),
        'token' => env('XRAY_VISION_TOKEN'),
    ],

    // Optional standalone PaddleOCR + scispaCy/medspaCy service
    // (clinical-nlp-service/) — a second-opinion OCR engine tried after
    // Tesseract, plus biomedical entity recognition on report text. Purely
    // additive: unset means the app behaves exactly as it does today.
    'clinical_nlp' => [
        'url' => env('CLINICAL_NLP_URL'),
        'token' => env('CLINICAL_NLP_TOKEN'),
    ],

    // Web push (medication/vaccination reminders via the installed PWA).
    // Unset means the app behaves exactly as it does today — email
    // reminders keep working unchanged, push is purely additive.
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:support@novix.app'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];
