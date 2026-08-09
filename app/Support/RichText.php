<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Illuminate\Support\Str;

/**
 * Sanitizes and renders admin-authored rich text (from the Quill editor).
 * Only a small allowlist of formatting tags survives; every attribute is
 * dropped except a validated href on links. Plain-text values (legacy course
 * descriptions with no tags) are shown with their line breaks preserved.
 */
class RichText
{
    /** Tags kept during sanitization. Disallowed tags are unwrapped to their text. */
    protected const ALLOWED = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote',
        'a', 'pre', 'code', 'span',
    ];

    /** Tags removed entirely, content and all (never unwrapped). */
    protected const DROP = [
        'script', 'style', 'iframe', 'object', 'embed', 'form',
        'input', 'button', 'textarea', 'select', 'link', 'meta', 'noscript', 'svg',
    ];

    /** True when the value already contains HTML markup. */
    public static function isHtml(?string $value): bool
    {
        $value = (string) $value;

        return $value !== strip_tags($value);
    }

    /** Turn a stored value into safe display HTML. */
    public static function render(?string $value): string
    {
        $value = (string) $value;

        if (trim($value) === '') {
            return '';
        }

        if (! static::isHtml($value)) {
            // Legacy plain-text: escape then keep line breaks.
            return nl2br(e($value));
        }

        return static::clean($value);
    }

    /** Plain-text excerpt for cards/lists. */
    public static function excerpt(?string $value, int $limit = 140): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));

        return Str::limit($text, $limit);
    }

    /** Sanitize an HTML fragment against the allowlist. */
    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="rt-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('rt-root');
        if (! $root) {
            return '';
        }

        static::sanitizeChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    protected static function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                // Comments, processing instructions, etc.
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, static::DROP, true)) {
                // Dangerous/irrelevant element — remove it and everything inside.
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, static::ALLOWED, true)) {
                // Sanitize its subtree, then unwrap (keep the text, drop the tag).
                static::sanitizeChildren($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            static::stripAttributes($child, $tag);
            static::sanitizeChildren($child);
        }
    }

    protected static function stripAttributes(DOMElement $el, string $tag): void
    {
        // Capture href before wiping attributes so links can be re-added safely.
        $href = $tag === 'a' ? $el->getAttribute('href') : '';

        foreach (iterator_to_array($el->attributes) as $attr) {
            $el->removeAttribute($attr->nodeName);
        }

        if ($tag === 'a' && $href !== '' && preg_match('~^(https?://|mailto:)~i', $href)) {
            $el->setAttribute('href', $href);
            $el->setAttribute('target', '_blank');
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }
}
