<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Admin SDK
    |
    */

    'credentials' => [
        'type' => env('FIREBASE_TYPE', 'service_account'),
        'project_id' => env('FIREBASE_PROJECT_ID', 'cihuyyy-7eb5ca94'),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'client_id' => env('FIREBASE_CLIENT_ID'),
        'auth_uri' => env('FIREBASE_AUTH_URI', 'https://accounts.google.com/o/oauth2/auth'),
        'token_uri' => env('FIREBASE_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
        'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_CERT_URL', 'https://www.googleapis.com/oauth2/v1/certs'),
        'client_x509_cert_url' => env('FIREBASE_CLIENT_CERT_URL'),
    ],

    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],

    'storage' => [
        'default_bucket' => env('FIREBASE_STORAGE_BUCKET', 'cihuyyy-7eb5ca94.firebasestorage.app'),
    ],

    // Web API Config
    'api_key' => env('FIREBASE_API_KEY', 'AIzaSyAQbCAw5eKmrNKOVsjUrCTYtJ0rSmAhoM8'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'cihuyyy-7eb5ca94.firebaseapp.com'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '132702781263'),
    'app_id' => env('FIREBASE_APP_ID', '1:132702781263:web:c1ffb7374eb2835e490f3a'),
];
