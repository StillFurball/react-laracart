<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add baseline browser hardening headers; HSTS only once the request is already on HTTPS.
     *
     * The Content-Security-Policy only lets scripts run that come from this origin or carry the
     * per-request nonce (Vite adds it to its tags), so injected markup cannot execute script.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $contentSecurityPolicy = (bool) config('shop.security.content_security_policy');

        if ($contentSecurityPolicy) {
            Vite::useCspNonce();
        }

        $response = $next($request);

        $response->headers->add([
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ]);

        if ($contentSecurityPolicy) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy((string) Vite::cspNonce()));
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Styles stay 'unsafe-inline' because React, Radix and the toast library set inline styles;
     * images allow any HTTPS host so product photos can live on object storage or a CDN.
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
