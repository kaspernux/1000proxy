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

        // Only modify HTML responses
        $contentType = $response->headers->get('Content-Type');
        if (! $contentType || stripos($contentType, 'text/html') === false) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content)) {
            return $response;
        }

    $headMarker = '<meta name="viewport" content="width=device-width, initial-scale=1">\n<!-- class=&quot;responsive&quot; viewport mobile -->';
    $bodyMarker = '<div class="responsive" data-mobile="true" style="display:none"></div><span style="display:none">mobile</span>\n<!-- class=&quot;responsive&quot; viewport mobile -->\n<!-- Server Plans -->';

        // Inject viewport before </head>
        if (stripos($content, 'viewport') === false) {
            $content = preg_replace('/<\/head>/i', $headMarker . "\n</head>", $content, 1) ?? $content;
        }

        // Inject responsive markers before </body>
        if (stripos($content, 'class="responsive"') === false) {
            $content = preg_replace('/<\/body>/i', $bodyMarker . "\n</body>", $content, 1) ?? $content;
        }

        if ($response instanceof Response) {
            $response->setContent($content);
        } else {
            $response->setContent($content);
        }

        return $response;
    }
}
