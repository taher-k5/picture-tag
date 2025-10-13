<?php

namespace taherkathiriya\craftpicturetag\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\models\AssetTransform;
use craft\helpers\ImageTransforms;
use craft\helpers\StringHelper;
use taherkathiriya\craftpicturetag\PictureTag;
use taherkathiriya\craftpicturetag\models\PictureOptions;

/**
 * Image Service
 */
class ImageService extends Component
{
    /**
     * Generate responsive image sources for picture tag
     */
    public function generateResponsiveSources(Asset $image, array $options = []): array
    {
        $settings = PictureTag::getInstance()->getSettings();
        $sources = [];

        if (!$image || !$image->kind === Asset::KIND_IMAGE) {
            return $sources;
        }

        $breakpoints = $options['breakpoints'] ?? $settings->getDefaultBreakpoints();
        $transforms = $options['transforms'] ?? $settings->getDefaultTransforms();
        $artDirection = $options['artDirection'] ?? [];
        $enableWebP = $options['enableWebP'] ?? $settings->enableWebP;
        $enableAvif = $options['enableAvif'] ?? $settings->enableAvif;
        $quality = $options['quality'] ?? $settings->webpQuality;

        foreach ($breakpoints as $breakpointName => $breakpointWidth) {
            $transform = $transforms[$breakpointName] ?? $this->getDefaultTransform($breakpointWidth);
            
            // Apply art direction if provided
            if (isset($artDirection[$breakpointName])) {
                $transform = array_merge($transform, $artDirection[$breakpointName]);
            }

            // Generate source for each format
            $sourceSets = [
                'default' => $this->generateSrcSet($image, $transform, $breakpointWidth)
            ];

            if ($enableWebP) {
                $webpTransform = array_merge($transform, ['format' => 'webp', 'quality' => $quality]);
                $sourceSets['webp'] = $this->generateSrcSet($image, $webpTransform, $breakpointWidth);
            }

            if ($enableAvif) {
                $avifTransform = array_merge($transform, ['format' => 'avif', 'quality' => $settings->avifQuality]);
                $sourceSets['avif'] = $this->generateSrcSet($image, $avifTransform, $breakpointWidth);
            }

            $sources[] = [
                'breakpoint' => $breakpointName,
                'width' => $breakpointWidth,
                'sources' => $sourceSets,
                'media' => $this->generateMediaQuery($breakpointName, $breakpointWidth, $breakpoints)
            ];
        }

        return $sources;
    }

    /**
     * Generate srcset string for given transform
     */
    public function generateSrcSet(Asset $image, array $transform, int $maxWidth): string
    {
        if (!$image || !$image->kind === Asset::KIND_IMAGE) {
            return '';
        }

        $srcset = [];
        $densityMultipliers = [1, 1.5, 2, 3];
        
        foreach ($densityMultipliers as $density) {
            $width = (int) ($transform['width'] * $density);
            
            // Don't exceed original image width
            if ($width > $image->getWidth()) {
                break;
            }

            $densityTransform = array_merge($transform, ['width' => $width]);
            
            if (isset($transform['height'])) {
                $densityTransform['height'] = (int) ($transform['height'] * $density);
            }

            $url = $image->getUrl($densityTransform);
            if ($url) {
                $srcset[] = $url . ' ' . $density . 'x';
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Generate sizes attribute
     */
    public function generateSizes(array $breakpoints, array $customSizes = []): string
    {
        if (!empty($customSizes)) {
            return implode(', ', $customSizes);
        }

        $sizes = [];
        $breakpointArray = array_values($breakpoints);
        sort($breakpointArray);

        foreach ($breakpointArray as $index => $width) {
            if ($index === 0) {
                $sizes[] = "(max-width: {$width}px) 100vw";
            } else {
                $prevWidth = $breakpointArray[$index - 1];
                $sizes[] = "(min-width: {$prevWidth}px) and (max-width: {$width}px) 100vw";
            }
        }

        // Add final breakpoint
        $lastWidth = end($breakpointArray);
        $sizes[] = "(min-width: {$lastWidth}px) {$lastWidth}px";

        return implode(', ', $sizes);
    }

    /**
     * Generate media query for breakpoint
     */
    public function generateMediaQuery(string $breakpointName, int $width, array $allBreakpoints): string
    {
        $breakpointArray = array_values($allBreakpoints);
        sort($breakpointArray);
        $index = array_search($width, $breakpointArray);

        if ($index === 0) {
            return "(max-width: {$width}px)";
        }

        $prevWidth = $breakpointArray[$index - 1];
        return "(min-width: {$prevWidth}px) and (max-width: {$width}px)";
    }

    /**
     * Get default transform for breakpoint width
     */
    public function getDefaultTransform(int $width): array
    {
        $settings = PictureTag::getInstance()->getSettings();
        $transforms = $settings->getDefaultTransforms();
        
        // Find the closest transform
        foreach ($transforms as $transform) {
            if ($transform['width'] >= $width) {
                return $transform;
            }
        }

        // Return the largest transform if none match
        return end($transforms);
    }

    /**
     * Check if image supports WebP
     */
    public function supportsWebP(Asset $image): bool
    {
        if (!$image || !$image->kind === Asset::KIND_IMAGE) {
            return false;
        }

        $mimeType = $image->getMimeType();
        return in_array($mimeType, ['image/jpeg', 'image/png']);
    }

    /**
     * Check if image supports AVIF
     */
    public function supportsAvif(Asset $image): bool
    {
        if (!$image || !$image->kind === Asset::KIND_IMAGE) {
            return false;
        }

        $mimeType = $image->getMimeType();
        return in_array($mimeType, ['image/jpeg', 'image/png']);
    }

    /**
     * Optimize SVG content
     */
    public function optimizeSvg(string $svgContent): string
    {
        $settings = PictureTag::getInstance()->getSettings();
        
        if (!$settings->enableSvgOptimization) {
            return $svgContent;
        }

        // Basic SVG optimization
        $svgContent = preg_replace('/\s+/', ' ', $svgContent); // Remove extra whitespace
        $svgContent = preg_replace('/<!--.*?-->/s', '', $svgContent); // Remove comments
        $svgContent = trim($svgContent);

        return $svgContent;
    }

    /**
     * Check if asset is SVG
     */
    public function isSvg(Asset $asset): bool
    {
        return $asset && $asset->kind === Asset::KIND_IMAGE && $asset->getExtension() === 'svg';
    }

    /**
     * Get SVG content
     */
    public function getSvgContent(Asset $asset): string
    {
        if (!$this->isSvg($asset)) {
            return '';
        }

        $path = $asset->getTransformSource();
        if (!file_exists($path)) {
            return '';
        }

        $content = file_get_contents($path);
        return $this->optimizeSvg($content);
    }

    /**
     * Generate lazy loading placeholder
     */
    public function generateLazyPlaceholder(int $width, int $height): string
    {
        $settings = PictureTag::getInstance()->getSettings();
        
        if ($settings->lazyPlaceholder) {
            return $settings->lazyPlaceholder;
        }

        // Generate a simple SVG placeholder
        $svg = sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg"><rect width="%d" height="%d" fill="#f5f5f5"/></svg>',
            $width,
            $height,
            $width,
            $height,
            $width,
            $height
        );

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Get transform info for debugging
     */
    public function getTransformInfo(Asset $image, array $transform): array
    {
        $settings = PictureTag::getInstance()->getSettings();
        
        if (!$settings->enableDebug) {
            return [];
        }

        return [
            'original' => [
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'format' => $image->getExtension(),
                'size' => $image->getSize()
            ],
            'transform' => $transform,
            'url' => $image->getUrl($transform),
            'webp_supported' => $this->supportsWebP($image),
            'avif_supported' => $this->supportsAvif($image)
        ];
    }
}
