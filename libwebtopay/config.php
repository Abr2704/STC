<?php
// PaySera project credentials (live)
// The password should be provided via the environment to avoid storing secrets in git.
// Set an environment variable named PAYSERA_PROJECT_PASSWORD on the server.
const PROJECT_ID = 254450;
const PROJECT_PASSWORD = getenv('PAYSERA_PROJECT_PASSWORD') ?: 'CHANGE_ME';

// Application fee configuration
const APPLICATION_FEE_CENTS = 25000; // cents
const APPLICATION_CURRENCY = 'EUR';

// File storage configuration
const UPLOAD_DIR = __DIR__ . '/uploads/';
const APPLICATIONS_LOG = __DIR__ . '/applications.csv';
