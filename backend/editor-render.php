<?php
declare(strict_types=1);

const EDITOR_SELECTORS = [
    'hero' => ['hero', 'container'],
    'promotions' => ['promo', 'container'],
    'about' => ['section', 'container'],
];

const REQUIRED_PAGE_IDS = [
    'productGrid',
    'cartPanel',
    'cartItems',
    'cartCount',
    'cartTotal',
    'panelCartTotal',
    'closeCart',
    'overlay',
];

function namespace_editor_selector(string $selector, array $ids): string {
    // Consume comments and quoted strings before looking for ID selectors.
    return preg_replace_callback('~(/\*.*?\*/|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')|#((?:\\\\[0-9a-fA-F]{1,6}\s?|\\\\.|[\w-])+)~us', function ($match) use ($ids) {
        if (!isset($match[2]) || $match[2] === '') return $match[0];
        $id = preg_replace_callback('/\\\\(?:([0-9a-fA-F]{1,6})\s?|(.))/us', fn ($escape) => !empty($escape[1]) ? html_entity_decode('&#x' . $escape[1] . ';', ENT_QUOTES, 'UTF-8') : $escape[2], $match[2]);
        return isset($ids[$id]) ? '#' . $ids[$id] : $match[0];
    }, $selector);
}

/** Read a fragment without changing the UTF-8 text or emoji. */
function editor_document(string $html): array {
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    try {
        $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_NONET);
    } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    return [$document, $document->getElementsByTagName('body')->item(0)];
}

/** Validate either the full page ('page') or a single section region. */
function validate_editor_html(string $name, string $html): array {
    if ($name !== 'page' && !isset(EDITOR_SELECTORS[$name])) throw new InvalidArgumentException('Khu vực không hợp lệ.');
    [$document, $body] = editor_document($html);
    if ($body->getElementsByTagName('script')->length > 0) throw new InvalidArgumentException('Không cho phép thẻ script trong nội dung giao diện.');
    $ids = [];
    foreach ($body->getElementsByTagName('*') as $element) {
        if (!$element->hasAttribute('id')) continue;
        $id = $element->getAttribute('id');
        if (isset($ids[$id])) throw new InvalidArgumentException('Giao diện có ID trùng lặp (#' . $id . '). Hãy sửa ID hoặc khôi phục mặc định.');
        $ids[$id] = true;
    }
    if ($name === 'page') {
        foreach (REQUIRED_PAGE_IDS as $requiredId) {
            if (!isset($ids[$requiredId])) throw new InvalidArgumentException('Thiếu thành phần bắt buộc (#' . $requiredId . '). Hãy hoàn tác hoặc khôi phục mặc định.');
        }
        return [$document, $body, $body];
    }
    $roots = [];
    foreach ($body->childNodes as $node) {
        if ($node instanceof DOMElement) $roots[] = $node;
        elseif ($node instanceof DOMText && trim($node->textContent) !== '') throw new InvalidArgumentException('Nội dung phải nằm bên trong khối khu vực.');
    }
    if (count($roots) !== 1 || $roots[0]->tagName !== 'section') throw new InvalidArgumentException('Khu vực phải có đúng một khối section. Hãy xóa khối bị lặp hoặc khôi phục mặc định.');
    $root = $roots[0];
    $classes = preg_split('/\s+/', trim($root->getAttribute('class')));
    if (array_diff(EDITOR_SELECTORS[$name], $classes) || ($name !== 'hero' && $root->getAttribute('id') !== $name)) throw new InvalidArgumentException('Khối gốc không đúng khu vực. Hãy khôi phục mặc định.');
    return [$document, $body, $root];
}

/** Rewrite hashes in selector preludes, never declaration colors or strings.
 * Track strings, comments and block types so nested @media/@supports work too.
 */
function namespace_editor_css(string $css, array $ids): string {
    $edits = [];
    $stack = ['rules'];
    $start = 0;
    $quote = null;
    $comment = false;
    $parentheses = 0;
    $brackets = 0;
    $length = strlen($css);
    for ($i = 0; $i < $length; $i++) {
        $char = $css[$i];
        $next = $css[$i + 1] ?? '';
        if ($comment) { if ($char === '*' && $next === '/') { $comment = false; $i++; } continue; }
        if ($quote !== null) { if ($char === '\\') $i++; elseif ($char === $quote) $quote = null; continue; }
        if ($char === '/' && $next === '*') { $comment = true; $i++; continue; }
        if ($char === '"' || $char === "'") { $quote = $char; continue; }
        if ($char === '\\') { $i++; continue; }
        if ($char === '(') $parentheses++;
        if ($char === ')') $parentheses--;
        if ($char === '[') $brackets++;
        if ($char === ']') $brackets--;
        if ($parentheses || $brackets) continue;
        if ($char === '{') {
            $header = substr($css, $start, $i - $start);
            $trimmed = ltrim(preg_replace('~/\*.*?\*/~s', '', $header));
            $atRule = str_starts_with($trimmed, '@');
            if (end($stack) === 'rules' && !$atRule) {
                $rewritten = namespace_editor_selector($header, $ids);
                $edits[] = [$start, $i - $start, $rewritten];
            }
            $stack[] = $atRule && preg_match('/^@(media|supports|container|layer|scope|document)\b/i', $trimmed) ? 'rules' : 'declarations';
            $start = $i + 1;
        } elseif ($char === '}') {
            if (count($stack) === 1) throw new InvalidArgumentException('CSS có dấu ngoặc không hợp lệ.');
            array_pop($stack);
            $start = $i + 1;
        } elseif ($char === ';' && end($stack) === 'rules') $start = $i + 1;
    }
    if (count($stack) !== 1 || $quote !== null || $comment || $parentheses || $brackets) throw new InvalidArgumentException('CSS chưa đóng đầy đủ.');
    foreach (array_reverse($edits) as [$offset, $size, $replacement]) $css = substr_replace($css, $replacement, $offset, $size);
    return $css;
}

/** Publish either the full page ('page') or an isolated region. */
function render_editor_section(string $name, array $section): array {
    [$document, $body, $root] = validate_editor_html($name, $section['html']);
    if ($name === 'page') {
        $css = namespace_editor_css($section['css'], []);
        $innerHtml = '';
        foreach ($body->childNodes as $child) {
            $innerHtml .= $document->saveHTML($child);
        }
        return [
            'html' => $innerHtml,
            'css' => $css,
        ];
    }
    $ids = [];
    foreach ($body->getElementsByTagName('*') as $element) {
        if (!$element->hasAttribute('id')) continue;
        $id = $element->getAttribute('id');
        // Keep public navigation anchors; all other IDs belong to their region.
        if ($element === $root && in_array($name, ['about', 'promotions'], true)) continue;
        $ids[$id] = 'lf-' . $name . '-' . bin2hex($id);
        $element->setAttribute('id', $ids[$id]);
    }
    foreach ($body->getElementsByTagName('*') as $element) {
        foreach (['href', 'xlink:href'] as $attribute) {
            $value = $element->getAttribute($attribute);
            if (str_starts_with($value, '#') && isset($ids[substr($value, 1)])) $element->setAttribute($attribute, '#' . $ids[substr($value, 1)]);
        }
        foreach (['for', 'aria-labelledby', 'aria-describedby', 'aria-controls', 'aria-owns', 'headers'] as $attribute) {
            if (!$element->hasAttribute($attribute)) continue;
            $values = preg_split('/\s+/', trim($element->getAttribute($attribute)));
            $element->setAttribute($attribute, implode(' ', array_map(fn ($id) => $ids[$id] ?? $id, $values)));
        }
    }
    $css = namespace_editor_css($section['css'], $ids);
    return [
        'html' => '<div data-editor-region="' . $name . '" style="display:contents">' . $document->saveHTML($root) . '</div>',
        'css' => $css === '' ? '' : '@scope ([data-editor-region="' . $name . '"]) {' . $css . '}',
    ];
}
