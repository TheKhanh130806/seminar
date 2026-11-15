<?php
// CLI regression checks; no database or live-content writes.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../backend/content.php';
require __DIR__ . '/../backend/editor-render.php';
function expect(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    echo 'PASS: ', $label, PHP_EOL;
}
function rejects(callable $action, string $label): void {
    try { $action(); } catch (InvalidArgumentException $e) { expect(true, $label); return; }
    expect(false, $label);
}

$hero = '<section class="hero container"><h2 id="shared">Tươi ngon 🥦</h2><a href="#shared" aria-describedby="shared">See All →</a><a href="#products">Products</a></section>';
$about = '<section class="section container" id="about"><h2 id="shared">Giới thiệu</h2></section>';
$css = '/* #shared */ #shared, :is(#shared, .card) {color:#fff;content:"#shared { literal }";background:url("data:image/svg+xml,{value}")} @media (max-width:600px) { @supports (display:grid) {#shared:hover{display:grid}}}';
$renderedHero = render_editor_section('hero', ['html' => $hero, 'css' => $css]);
$renderedAbout = render_editor_section('about', ['html' => $about, 'css' => '#shared{float:none}']);
$heroId = 'lf-hero-' . bin2hex('shared');
$aboutId = 'lf-about-' . bin2hex('shared');
expect(str_contains($renderedHero['html'], 'id="' . $heroId . '"') && str_contains($renderedAbout['html'], 'id="' . $aboutId . '"'), 'Same editor ID is unique across published regions');
expect(str_contains($renderedHero['css'], '#' . $heroId . ':hover') && str_contains($renderedHero['css'], ':is(#' . $heroId), 'ID selectors rewritten inside responsive and functional selectors');
expect(str_contains($renderedHero['css'], '/* #shared */') && str_contains($renderedHero['css'], 'content:"#shared { literal }"') && str_contains($renderedHero['css'], 'color:#fff'), 'Comments, declaration strings and colors preserved');
expect(str_contains($renderedHero['html'], 'href="#' . $heroId . '"') && str_contains($renderedHero['html'], 'aria-describedby="' . $heroId . '"'), 'Fragment links and accessible references follow renamed IDs');
expect(str_contains($renderedHero['html'], 'href="#products"') && str_contains($renderedAbout['html'], 'id="about"'), 'Public navigation anchors preserved');
expect(str_contains($renderedHero['html'], 'Tươi ngon 🥦') && str_contains($renderedHero['html'], 'See All →'), 'UTF-8 and emoji preserved');
expect(str_starts_with($renderedHero['css'], '@scope ([data-editor-region="hero"])'), 'Published CSS scoped to its region');
expect(namespace_editor_selector('#\\31 foo', ['1foo' => 'namespaced']) === '#namespaced', 'Escaped CSS IDs supported');
rejects(fn () => validate_editor_html('hero', $hero . $hero), 'Duplicate root sections rejected');
rejects(fn () => validate_editor_html('hero', '<section class="hero container"><b id="x"></b><b id="x"></b></section>'), 'Duplicate IDs within a region rejected');
rejects(fn () => validate_editor_html('about', $hero), 'Wrong region root rejected');
rejects(fn () => namespace_editor_css('#shared { color:red', []), 'Unclosed CSS rejected');

$templateHtml = file_get_contents(__DIR__ . '/../frontend/html/index.html');
preg_match('~<body[^>]*>(.*?)<script\b~si', $templateHtml, $bodyMatch);
$fullPageBody = $bodyMatch[1] ?? '';
$renderedPage = render_editor_section('page', ['html' => $fullPageBody, 'css' => '.banner { border-radius: 20px; }']);
expect(str_contains($renderedPage['html'], 'id="productGrid"') && str_contains($renderedPage['html'], 'Fresh Broccoli'), 'Full page renders with product grid and product cards');
rejects(fn () => validate_editor_html('page', str_replace('id="productGrid"', '', $fullPageBody)), 'Full page missing required hook #productGrid is rejected');
rejects(fn () => validate_editor_html('page', $fullPageBody . '<script>alert(1)</script>'), 'Full page with script tag is rejected');

$path = sys_get_temp_dir() . '/leafood-content-' . bin2hex(random_bytes(8)) . '.php';
try {
    expect(read_content($path) === ['draft' => [], 'published' => []], 'Missing storage has empty defaults');
    $first = ['draft' => ['hero' => ['html' => $hero]], 'published' => []];
    save_content($first, $path);
    if (function_exists('opcache_compile_file') && filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN)) opcache_compile_file($path);
    expect(read_content($path) === $first, 'Saved project reloads exactly');
    $second = $first;
    $second['draft']['hero']['html'] = str_replace('Tươi ngon', 'Nội dung mới', $hero);
    save_content($second, $path);
    expect(read_content($path) === $second, 'Immediate read after overwrite sees fresh data, including with OPcache');
    expect(read_content($path)['published'] === [], 'Draft save does not publish');
} finally {
    if (function_exists('opcache_invalidate')) opcache_invalidate($path, true);
    if (is_file($path)) unlink($path);
}

// Check the repaired real content without writing it.
$live = read_content();
foreach (['draft', 'published'] as $state) {
    if (!isset($live[$state]['hero'])) continue;
    $saved = $live[$state]['hero'];
    validate_editor_html('hero', $saved['html']);
    expect(count($saved['project']['pages'][0]['frames'][0]['component']['components']) === 1, $state . ' hero project contains one root');
    expect(!str_contains(json_encode($saved, JSON_UNESCAPED_UNICODE), "See All\u{00e2}"), $state . ' hero has no corrupted label');
}
