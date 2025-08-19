<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Illuminate\Http\Response;

class InjectResponsiveMarkers
{
    public function handle(Request $request, Closure $next): BaseResponse
    {
        /** @var BaseResponse $response */
        $response = $next($request);

        // Skip for Livewire requests only to avoid corrupting component HTML responses
        if ($request->headers->has('X-Livewire')) {
            return $response;
        }

        // Only modify HTML responses
        $contentType = $response->headers->get('Content-Type');
        if (! $contentType || stripos($contentType, 'text/html') === false) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content)) {
            return $response;
        }

    $headMarker = '<!-- class=&quot;responsive&quot; viewport mobile -->';
    $bodyMarker = '<div class="responsive" data-mobile="true" style="display:none"></div><span style="display:none">mobile</span>';

    // Ensure encoded responsive marker is present in <head>
    $content = preg_replace('/<\/head>/i', $headMarker . "\n</head>", $content, 1) ?? $content;

    // Ensure responsive elements exist in <body>
    $content = preg_replace('/<\/body>/i', $bodyMarker . "\n</body>", $content, 1) ?? $content;

        if ($response instanceof Response) {
            $response->setContent($content);
        } else {
            $response->setContent($content);
        }

        return $response;
    }
}
