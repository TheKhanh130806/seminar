<?php
require __DIR__ . '/../../backend/content.php';
require __DIR__ . '/../../backend/editor-render.php';
$page = file_get_contents(__DIR__ . '/index.html');
$content = read_content();
$styles = [];
if (isset($content['published']['page'])) {
    try {
        $rendered = render_editor_section('page', $content['published']['page']);
        $page = preg_replace_callback(
            '~(<body[^>]*>)(.*?)(\s*<script\b[^>]*src="\.\./js/script\.js"[^>]*></script>)~si',
            fn ($m) => $m[1] . "\n" . $rendered['html'] . $m[3],
            $page,
            1
        );
        if ($rendered['css'] !== '') $styles[] = $rendered['css'];
    } catch (InvalidArgumentException $e) {
        error_log('Invalid published page: ' . $e->getMessage());
    }
} else {
    foreach ($content['published'] as $name => $section) {
        $pattern = match ($name) {
            'hero' => '~<section class="hero container">.*?</section>~s',
            'promotions' => '~<section class="promo container" id="promotions">.*?</section>~s',
            'about' => '~<section class="section container" id="about">.*?</section>~s',
            default => null,
        };
        if ($pattern) {
            try {
                $rendered = render_editor_section($name, $section);
                $page = preg_replace_callback($pattern, fn () => $rendered['html'], $page, 1);
                if ($rendered['css'] !== '') $styles[] = $rendered['css'];
            } catch (InvalidArgumentException $e) {
                error_log('Invalid published region ' . $name . ': ' . $e->getMessage());
            }
        }
    }
}
$page = str_replace('</head>', '<style id="published-region-styles">' . implode("\n", $styles) . '</style></head>', $page);
header('Content-Type: text/html; charset=utf-8');
echo $page;
