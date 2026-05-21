<?php

namespace SlotDemosCascade;

class ContentInjector
{
    public static function register(): void
    {
        // Run AFTER theme's CTA wrapping filter (priority 20) but BEFORE shortcode rendering
        add_filter('the_content', [self::class, 'maybeInject'], 25);
    }

    public static function maybeInject(string $content): string
    {
        // Only on singular demo pages
        if (!is_singular()) {
            return $content;
        }
        if (strpos($content, 'je-demo-container') === false) {
            return $content;
        }

        // 1) Universal cleanup — strip stray </p> tags that wpautop leaves around iframes
        //    inside .je-demo-container, plus remove legacy "Si la demo no carga…" disclaimer
        //    paragraph that was added before the cascade plugin. Cascade does the right thing.
        $content = self::cleanDemoContainer($content);
        $content = self::removeLegacyDisclaimer($content);

        // 2) Inject data-jd-game="<slug>" on ALL demo pages so client-side cascade runs.
        //    If game is unmapped or all sources fail, JS shows graceful "Demo no disponible"
        //    + bono-CTA UI instead of leaving a broken iframe / dead Play button.
        $slug = self::detectGameSlug();
        if (!$slug) {
            return $content;
        }

        $attr = sprintf(' data-jd-game="%s" data-jd-locale="es"', esc_attr($slug));
        $content = preg_replace_callback(
            '#<div([^>]*\bclass="[^"]*\bje-demo-container\b[^"]*"[^>]*)>#i',
            function ($m) use ($attr) {
                if (strpos($m[1], 'data-jd-game') !== false) {
                    return $m[0];
                }
                return '<div' . $m[1] . $attr . '>';
            },
            $content,
            1
        );

        return $content;
    }

    /**
     * Remove stray </p>, <p></p>, &nbsp;, <br/> and similar wpautop debris
     * inside .je-demo-container so the demo box renders cleanly without empty
     * vertical strips below/above the iframe.
     */
    /**
     * Remove legacy "Si la demo no carga, prueba a desactivar el bloqueador de anuncios..."
     * disclaimer paragraphs that were added to demo pages before the cascade plugin existed.
     * Cascade now handles iframe failures with retry — disclaimer is misleading.
     */
    private static function removeLegacyDisclaimer(string $content): string
    {
        $patterns = [
            '#<p[^>]*>\s*Si la demo no carga[^<]*</p>#iu',
            '#<p[^>]*>\s*Si el juego no carga[^<]*</p>#iu',
            '#<p[^>]*>\s*La demo se aloja[^<]*</p>#iu',
        ];
        foreach ($patterns as $pat) {
            $content = preg_replace($pat, '', $content);
        }
        return $content;
    }

    private static function cleanDemoContainer(string $content): string
    {
        return preg_replace_callback(
            '#(<div[^>]*\bclass="[^"]*\bje-demo-container\b[^"]*"[^>]*>)(.*?)(</div>)#is',
            function ($m) {
                $inner = $m[2];
                // Remove orphan </p> not preceded by an opening <p in this slice
                $inner = preg_replace('#</?p[^>]*>#i', '', $inner);
                // Remove empty paragraphs / <br/> / &nbsp; / whitespace runs
                $inner = preg_replace('#&nbsp;#i', ' ', $inner);
                $inner = preg_replace('#<br\s*/?>#i', '', $inner);
                // Collapse whitespace between elements
                $inner = preg_replace('#>\s+<#', '><', $inner);
                $inner = trim($inner);
                return $m[1] . $inner . $m[3];
            },
            $content
        );
    }

    /**
     * Detect game slug from URL.
     *
     * Default behaviour scans URL parts and returns the segment immediately
     * after any configured section. Section list is filterable via
     * `slot_demos_cascade_url_sections` and defaults to ['slots'].
     *
     * Sites with multi-section taxonomies can register live-casino sections
     * via `slot_demos_cascade_live_sections` — slugs are prefixed `live:`
     * to keep them in their own games-map namespace.
     */
    private static function detectGameSlug(): string
    {
        $req = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = trim(parse_url($req, PHP_URL_PATH) ?: '', '/');
        $parts = $path ? explode('/', $path) : [];

        $sections = apply_filters('slot_demos_cascade_url_sections', ['slots']);
        foreach ((array) $sections as $section) {
            $idx = array_search($section, $parts, true);
            if ($idx !== false && isset($parts[$idx + 1])) {
                return sanitize_title($parts[$idx + 1]);
            }
        }

        $liveSections = apply_filters('slot_demos_cascade_live_sections', []);
        foreach ((array) $liveSections as $section) {
            $idx = array_search($section, $parts, true);
            if ($idx !== false && isset($parts[$idx + 1])) {
                return 'live:' . sanitize_title($parts[$idx + 1]);
            }
        }

        return '';
    }
}
