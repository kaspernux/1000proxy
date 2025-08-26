@php
    $markdown = isset($markdown) ? $markdown : ($exceptionAsMarkdown ?? '');
    try {
        // Convert any invalid UTF-8 to valid using PHP's iconv substitute, then escape for HTML
        $safeMarkdown = iconv('UTF-8', 'UTF-8//IGNORE', (string) $markdown);
    } catch (\Throwable $e) {
        $safeMarkdown = '';
    }
@endphp

<div class="renderer-markdown">
    {!! nl2br(e($safeMarkdown)) !!}
</div>
