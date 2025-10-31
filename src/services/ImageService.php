<?php

namespace taherkathiriya\craftpicturetag\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use taherkathiriya\craftpicturetag\PictureTag;

class ImageService extends Component
{
    private function getSettingsSafe() { return PictureTag::getInstance()?->getSettings(); }
    // REMOVED: generateResponsiveSources() – no breakpoints

	/**
	 * Generate srcset string for given transform
	 */
    public function generateSrcSet(Asset $image, array $transform, int $maxWidth): string
    {
        $srcset = [];
        $densities = [1, 1.5, 2, 3];
        $imageWidth = (int)$image->getWidth();
        $baseWidth = min($transform['width'] ?? $maxWidth, $imageWidth);

        foreach ($densities as $d) {
            $w = (int)round($baseWidth * $d);
            if ($w > $imageWidth) break;

            $t = array_merge($transform, ['width' => $w]);
            if (isset($transform['height'])) {
                $t['height'] = (int)round($transform['height'] * $d);
            }

            $url = $image->getUrl($t, true);
            if ($url) $srcset[] = $url . ' ' . $w . 'w';
        }

        return implode(', ', $srcset) ?: $image->getUrl($transform) . ' ' . $baseWidth . 'w';
    }

	/**
	 * Generate sizes attribute
	 */
    public function generateSizes(array $customSizes = []): string
    {
        return !empty($customSizes) ? implode(', ', $customSizes) : '100vw';
    }

	/**
	 * Check if image supports WebP
	 */
    public function supportsWebP(Asset $image): bool
    {
        return in_array($image->getMimeType(), ['image/jpeg', 'image/png']);
    }

	/**
	 * Check if image supports AVIF
	 */
    public function supportsAvif(Asset $image): bool
    {
        return in_array($image->getMimeType(), ['image/jpeg', 'image/png']);
    }

	/**
	 * Optimize SVG content
	 */
    public function optimizeSvg(string $content): string
    {
        $settings = $this->getSettingsSafe();
        if (!$settings || !$settings->enableSvgOptimization) {
            return $content; // OFF → return original
        }

        // 1. Remove XML declaration & DOCTYPE
        $content = preg_replace('/^<\?xml[^>]*\?>\s*/i', '', $content);
        $content = preg_replace('/<!DOCTYPE[^>]*>\s*/i', '', $content);

        // 2. Remove comments
        $content = preg_replace('/<!--.*?-->/s', '', $content);

        // 3. Remove metadata (Inkscape, Adobe, etc.)
        $content = preg_replace('/<metadata[^>]*>.*?<\/metadata>/is', '', $content);
        $content = preg_replace('/<sodipodi:namedview[^>]*>.*?<\/sodipodi:namedview>/is', '', $content);

        // 4. Remove unnecessary attributes
        $content = preg_replace('/\s+(id|class)="[^"]*"/i', '', $content);
        $content = preg_replace('/\s+version="[^"]*"/i', '', $content);
        $content = preg_replace('/\s+xmlns:xlink="[^"]*"/i', '', $content);
        $content = preg_replace('/\s+enable-background="[^"]*"/i', '', $content);

        // 5. Normalize whitespace
        $content = preg_replace('/>\s+</', '><', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/\s*([<>,\/])\s*/', '$1', $content);

        // 6. Shorten common attributes (safe)
        $content = str_replace(' fill="none"', ' fill="none"', $content); // keep
        $content = preg_replace('/ stroke="none"/i', ' stroke="none"', $content);
        $content = preg_replace('/ fill="#000000"/i', ' fill="#000"', $content);
        $content = preg_replace('/ fill="#ffffff"/i', ' fill="#fff"', $content);
        $content = preg_replace('/ stroke="#000000"/i', ' stroke="#000"', $content);

        // 7. Remove empty <g> groups
        $content = preg_replace('/<g>\s*<\/g>/', '', $content);
        $content = preg_replace('/<g\s*\/>/', '', $content);

        // 8. Trim final output
        return trim($content);
    }

	/**
	 * Check if asset is SVG
	 */
    public function isSvg(Asset $asset): bool
    {
        return $asset && $asset->getExtension() === 'svg';
    }

    /**
     * Sanitize SVG to remove unsafe tags and attributes
     */
    // public function sanitizeSvg(string $content): string
    // {
    //     $settings = $this->getSettingsSafe();
    //      if (!$settings || !$settings->enableSvgSanitization) {
    //         return $content;
    //     }

    //     // 1. Remove XML declaration (DOMDocument can't parse it)

        
    //     // Default whitelist of safe tags & attributes
    //     $allowedTags = $settings->allowedSvgTags ?? [
    //         'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
    //         'text', 'tspan', 'defs', 'use', 'linearGradient', 'radialGradient', 'stop', 'clipPath',
    //         'mask', 'pattern', 'symbol', 'title', 'desc', 'style'
    //     ];

    //     $allowedAttributes = [
    //         'x', 'y', 'width', 'height', 'rx', 'ry', 'r', 'cx', 'cy', 'points',
    //         'd', 'fill', 'stroke', 'stroke-width', 'transform', 'opacity',
    //         'viewBox', 'xmlns', 'xmlns:xlink', 'preserveAspectRatio',
    //         'id', 'class', 'style', 'xlink:href', 'clip-path', 'mask', 'gradientUnits'
    //     ];

    //     // Remove script, iframe, foreignObject, event handlers
    //     $content = preg_replace('/<\s*(script|iframe|foreignObject)[^>]*>.*?<\s*\/\1\s*>/is', '', $content);
    //     $content = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', '', $content);
    //     $content = preg_replace("/on[a-z]+\s*=\s*'[^']*'/i", '', $content);

    //     // Load SVG safely with DOMDocument
    //     libxml_use_internal_errors(true);
    //     $dom = new \DOMDocument();
    //     $dom->loadXML($content, LIBXML_NOENT | LIBXML_NONET | LIBXML_COMPACT);
    //     $xpath = new \DOMXPath($dom);

    //     // Remove disallowed tags
    //     foreach ($xpath->query('//*') as $node) {
    //         if (!in_array($node->nodeName, $allowedTags)) {
    //             $node->parentNode->removeChild($node);
    //             continue;
    //         }

    //         // Remove disallowed attributes
    //         if ($node->hasAttributes()) {
    //             foreach (iterator_to_array($node->attributes) as $attr) {
    //                 if (!in_array($attr->name, $allowedAttributes)) {
    //                     $node->removeAttribute($attr->name);
    //                 }
    //             }
    //         }
    //     }
    //     return $dom->saveXML($dom->documentElement);
    // }

    /**
     * Sanitize SVG – works 100% with XML, CDATA, DOCTYPE
     */
    /**public function sanitizeSvg(string $content): string
    {
        $settings = $this->getSettingsSafe();
        if (!$settings || !$settings->enableSvgSanitization) {
            return $content; // OFF → return original
        }

        // 1. Remove XML declaration & DOCTYPE (DOMDocument can't parse)
        $content = preg_replace('/^<\?xml[^>]?>\si', '', $content);
        $content = preg_replace('/<!DOCTYPE[^>]*>\si', '', $content);

        // 2. Fast regex: remove scripts, iframes, event handlers
        $content = preg_replace('/<\s*(script|iframe|foreignObject)[^>]*>.*?<\s*\/\1\s*>/is', '', $content);
        $content = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', '', $content);
        $content = preg_replace("/on[a-z]+\s*=\s*'[^']*'/i", '', $content);

        // 3. Wrap in <root> + add xmlns to prevent parsing errors
        $wrapped = '<root xmlns="http://www.w3.org/2000/svg">' . trim($content) . '</root>';

        // 4. Use loadHTML to handle broken SVG gracefully
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $wrapped,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($dom);

        $allowedTags = [
            'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
            'text', 'tspan', 'defs', 'use', 'linearGradient', 'radialGradient', 'stop', 'clipPath',
            'mask', 'pattern', 'symbol', 'title', 'desc', 'style'
        ];

        $allowedAttrs = [
            'x', 'y', 'width', 'height', 'rx', 'ry', 'r', 'cx', 'cy', 'points',
            'd', 'fill', 'stroke', 'stroke-width', 'transform', 'opacity',
            'viewBox', 'xmlns', 'xmlns:xlink', 'preserveAspectRatio',
            'id', 'class', 'style', 'xlink:href', 'clip-path', 'mask', 'gradientUnits'
        ];

        // Remove disallowed tags
        foreach ($xpath->query('//*') as $node) {
            $tag = strtolower($node->nodeName);
            if (!in_array($tag, $allowedTags)) {
                $node->parentNode->removeChild($node);
                continue;
            }

            // Remove disallowed attributes
            if ($node->hasAttributes()) {
                foreach (iterator_to_array($node->attributes) as $attr) {
                    if (!in_array($attr->name, $allowedAttrs)) {
                        $node->removeAttribute($attr->name);
                    }
                }
            }
        }

        // Extract <svg> from <root>
        $svgNodes = $dom->getElementsByTagName('svg');
        if ($svgNodes->length === 0) {
            Craft::warning('No <svg> after sanitization', __METHOD__);
            return $content;
        }

        $cleanSvg = $dom->saveHTML($svgNodes->item(0));

        // Remove <svg> wrapper tags added by saveHTML
        $cleanSvg = preg_replace('/^<svg[^>]*>/i', '<svg', $cleanSvg);
        $cleanSvg = preg_replace('/<\/svg>$/i', '</svg>', $cleanSvg);

        return $cleanSvg;
    }*/


	/**
	 * Get SVG content
	 */
    public function getSvgContent(Asset $asset, ?bool $forceSanitize = null): ?string
    {
        if ($asset->getExtension() !== 'svg') {
            return null;
        }

        $settings = $this->getSettingsSafe();
        if (!$settings) {
            return null;
        }

        try {
            // Force local copy for remote volumes (S3, etc.)
            $path = $asset->getCopyOfFile();

            if (!$path || !file_exists($path)) {
                Craft::warning('SVG file not found: ' . $asset->filename, __METHOD__);
                return null;
            }

            $content = @file_get_contents($path);
            if ($content === false || trim($content) === '') {
                Craft::warning('SVG file empty or unreadable: ' . $asset->filename, __METHOD__);
                return null;
            }

            // 1. Optimize
            if ($settings->enableSvgOptimization) {
                $content = $this->optimizeSvg($content);
            }

            // 2. Sanitize: use $forceSanitize if provided, else use setting
            // $shouldSanitize = $forceSanitize ?? $settings->enableSvgSanitization;
            // if ($shouldSanitize) {
            //     $content = $this->sanitizeSvg($content);
            // }

            return $content;

        } catch (\Throwable $e) {
            Craft::error('SVG read error: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }
}
