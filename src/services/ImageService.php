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
        if (!$settings || !$settings->enableSvgOptimization) return $content;
        return trim(preg_replace(['/<!--.*?-->/s', '/\s+/'], ['', ' '], $content));
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
    public function getSvgContent(Asset $asset): string
    {
        if (!$this->isSvg($asset)) return '';
        $path = $asset->getPath();
        return file_exists($path) ? $this->optimizeSvg(file_get_contents($path)) : '';
    }
}
