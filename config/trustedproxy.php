<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Load balancers or reverse proxies whose X-Forwarded-* headers decide the
    | client IP (used by rate limits) and whether a request arrived over HTTPS.
    | Give a comma separated list of IP addresses or CIDR ranges. "*" trusts
    | every proxy and is only safe when the app can be reached solely through
    | the platform's load balancer. Laravel Cloud, Forge and Vapor need nothing
    | here: Laravel trusts their proxies automatically.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
