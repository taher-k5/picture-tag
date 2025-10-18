<?php

namespace taherkathiriya\craftpicturetag\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use taherkathiriya\craftpicturetag\PictureTag;

class ImageService extends Component
{
    private function getSettingsSafe()
    {
        $plugin = PictureTag::getInstance();
        return $plugin ? $plugin->getSettings() : null;
    }
    /**
	 * Generate responsive image sources for picture tag
	 */
	public function generateResponsiveSources(Asset $image, array $options = []): array
	{
		$settings = $this->getSettingsSafe();
		$sources = [];

        if (!$image || $image->kind !== Asset::KIND_IMAGE || !$settings) {
            Craft::warning('Invalid image or settings in generateResponsiveSources', __METHOD__);
            return $sources;
        }

        $breakpoints = $settings->ensureArray($options['breakpoints'] ?? $settings->getDefaultBreakpoints(), $settings->getDefaultBreakpoints());
        $defaultTransforms = $settings->getDefaultTransforms();
        $userTransforms = $settings->ensureArray($options['transforms'] ?? [], []);
        $artDirection = $settings->ensureArray($options['artDirection'] ?? [], []);
        $enableWebP = $options['enableWebP'] ?? $settings->enableWebP;
        $enableAvif = $options['enableAvif'] ?? $settings->enableAvif;
        $quality = $options['quality'] ?? $settings->webpQuality;

        // Sort breakpoints ascending for mobile-first order
        $breakpointValues = array_values($breakpoints);
        $breakpointKeys = array_keys($breakpoints);
        array_multisort($breakpointValues, SORT_ASC, $breakpointKeys);
        $sortedBreakpoints = array_combine($breakpointKeys, $breakpointValues);

		foreach ($sortedBreakpoints as $breakpointName => $breakpointWidth) {
			// Prioritize user-defined transforms; use defaults only if enabled and no user transform
			$transform = $userTransforms[$breakpointName] ?? ($settings->enableDefaultTransforms ? $defaultTransforms[$breakpointName] ?? [] : []);

            // Apply art direction if provided
            if (isset($artDirection[$breakpointName]) && is_array($artDirection[$breakpointName])) {
                $transform = array_merge($transform, $artDirection[$breakpointName]);
            }

            // Ensure width and height are set
            if (!isset($transform['width']) || !is_numeric($transform['width']) || $transform['width'] <= 0) {
                $transform['width'] = (int)$breakpointWidth;
            }
            if (!isset($transform['height']) || !is_numeric($transform['height']) || $transform['height'] <= 0) {
                $aspectRatio = $image->getWidth() / ($image->getHeight() ?: 1) ?: 1.5;
                $transform['height'] = (int)round($transform['width'] / $aspectRatio);
            }
            if (!isset($transform['quality']) || !is_numeric($transform['quality']) || $transform['quality'] < 1 || $transform['quality'] > 100) {
                $transform['quality'] = (int)$quality;
            }

			// Generate source for each format
			$sourceSets = [
				'default' => $this->generateSrcSet($image, $transform, (int)$breakpointWidth)
			];

			if ($enableWebP && $this->supportsWebP($image)) {
				$webpTransform = array_merge($transform, ['format' => 'webp', 'quality' => (int)$quality]);
				$sourceSets['webp'] = $this->generateSrcSet($image, $webpTransform, (int)$breakpointWidth);
			}

			if ($enableAvif && $this->supportsAvif($image)) {
				$avifTransform = array_merge($transform, ['format' => 'avif', 'quality' => (int)$settings->avifQuality]);
				$sourceSets['avif'] = $this->generateSrcSet($image, $avifTransform, (int)$breakpointWidth);
			}

			$sources[] = [
				'breakpoint' => $breakpointName,
				'width' => (int)$breakpointWidth,
                'transform' => $transform,
				'sources' => $sourceSets,
				'media' => $this->generateMediaQuery($breakpointName, (int)$breakpointWidth, $breakpoints)
			];
		}

		return $sources;
	}

	/**
	 * Generate srcset string for given transform
	 */
	public function generateSrcSet(Asset $image, array $transform, int $maxWidth): string
	{
		if (!$image || $image->kind !== Asset::KIND_IMAGE) {
            Craft::warning('Invalid image or maxWidth in generateSrcSet', __METHOD__);
			return '';
		}

		$transform = is_array($transform) ? $transform : [];
		$srcset = [];
		$densityMultipliers = [1, 1.5, 2, 3];
		$imageWidth = (int) $image->getWidth();
		$baseWidth = isset($transform['width']) && (int)$transform['width'] > 0
			? (int)$transform['width']
			: ($maxWidth > 0 ? $maxWidth : min(800, $imageWidth));
		if ($baseWidth <= 0) {
			$baseWidth = min(800, $imageWidth);
		}
		
		foreach ($densityMultipliers as $density) {
			$width = (int) round($baseWidth * $density);
			
			// Don't exceed original image width
			if ($width > $imageWidth) {
				break;
			}

			$densityTransform = array_merge($transform, ['width' => $width]);
			
			if (isset($transform['height']) && is_numeric($transform['height'])) {
				$densityTransform['height'] = (int) round(((int)$transform['height']) * $density);
			}

            $url = $image->getUrl($densityTransform, true); // Use true for immediate generation
            if ($url) {
                $srcset[] = $url . ' ' . $width . 'w';
            }
        }

        return implode(', ', $srcset) ?: ($image->getUrl($transform) . ' ' . $baseWidth . 'w');
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
        $count = count($breakpointArray);

        for ($index = 0; $index < $count; $index++) {
            $width = $breakpointArray[$index];
            if ($index === 0) {
                $sizes[] = "(max-width: {$width}px) 100vw";
            } else {
                $prevWidth = $breakpointArray[$index - 1];
                $minWidth = $prevWidth + 1;
                if ($index === $count - 1) {
                    $sizes[] = "(min-width: {$minWidth}px) {$width}px";
                } else {
                    $sizes[] = "(min-width: {$minWidth}px) and (max-width: {$width}px) 100vw";
                }
            }
        }

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
        $minWidth = $prevWidth + 1;
        if ($index === count($breakpointArray) - 1) {
            return "(min-width: {$minWidth}px)";
        } else {
            return "(min-width: {$minWidth}px) and (max-width: {$width}px)";
        }
    }

	/**
	 * Get default transform for breakpoint width
	 */
	public function getDefaultTransform(int $width): array
	{
		$settings = $this->getSettingsSafe();
		$transforms = $settings ? $settings->getDefaultTransforms() : [];
				// Find the closest transform
		foreach ($transforms as $transform) {
			if (is_array($transform) && isset($transform['width']) && $transform['width'] >= $width) {
				return $transform;
			}
		}

		// Return the largest transform if none match
		if (!empty($transforms)) {
			$last = end($transforms);
			return is_array($last) ? $last : ['width' => $width];
		}
		return ['width' => $width];
	}

	/**
	 * Check if image supports WebP
	 */
	public function supportsWebP(Asset $image): bool
	{
		if (!$image || $image->kind !== Asset::KIND_IMAGE) {
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
		if (!$image || $image->kind !== Asset::KIND_IMAGE) {
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
		$settings = $this->getSettingsSafe();
		if (!$settings || !$settings->enableSvgOptimization) {
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

        $path = $asset->getPath();
        if (!file_exists($path)) {
            Craft::warning('SVG file not found: ' . $path, __METHOD__);
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
		$settings = $this->getSettingsSafe();
		if ($settings && $settings->lazyPlaceholder) {
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
		$settings = $this->getSettingsSafe();
		if (!$settings || !$settings->enableDebug) {
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
