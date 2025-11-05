<?php

namespace SFS\craftpicturetag\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use SFS\craftpicturetag\PictureTag;

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
                return null;
            }

            $content = @file_get_contents($path);
            if ($content === false || trim($content) === '') {
                return null;
            }

            // 1. Optimize
            if ($settings->enableSvgOptimization) {
                $content = $this->optimizeSvg($content);
            }

            return $content;

        } catch (\Throwable $e) {
            Craft::error('SVG read error: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }
}
