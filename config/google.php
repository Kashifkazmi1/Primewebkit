<?php

declare(strict_types=1);

return [
    /**
     * Google Identity Services (Sign in with Google).
     *
     * Only the OAuth *client id* is needed for the ID-token flow used
     * here — it is public information. The client *secret* is not used
     * and must never be committed anywhere.
     */
    'client_id' => (string) env(
        'GOOGLE_CLIENT_ID',
        '1044212666179-nmo21qhhgr7hc4n8sdm34ccsgs5sdo84.apps.googleusercontent.com'
    ),
];
