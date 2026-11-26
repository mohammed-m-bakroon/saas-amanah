<?php

return [

    /*
    | The platform's own domain. Tenants live on {subdomain}.{central_domain};
    | the bare central domain serves marketing, registration and Super Admin.
    */
    'central_domain' => env('TENANCY_CENTRAL_DOMAIN', 'amanah.test'),

    /*
    | Subdomains a charity may never register (FR-01).
    */
    'reserved_subdomains' => [
        'www', 'app', 'admin', 'api', 'mail', 'static', 'assets', 'cdn', 'status', 'help', 'support',
    ],

];
