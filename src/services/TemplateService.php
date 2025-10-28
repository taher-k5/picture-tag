<?php

namespace taherkathiriya\craftpicturetag\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\Html;
use Twig\Markup;
use taherkathiriya\craftpicturetag\PictureTag;

/**
 * template service
 */
class TemplateService extends Component
{
    private function getSettingsSafe()
    {
        $plugin = PictureTag::getInstance();
        return $plugin ? $plugin->getSettings() : null;
    }

    private function getImageServiceSafe(): ?ImageService
    {
        $plugin = PictureTag::getInstance();
        return $plugin ? $plugin->imageService : null;
    }

	/**
	 * Render picture tag
	 */
	public function renderPicture(Asset $image, array $options = []): Markup
	{
		if (!$image || $image->kind !== Asset::KIND_IMAGE) {
            Craft::warning('Invalid image in renderPicture', __METHOD__);
			return new Markup('', Craft::$app->charset);
		}

        $settings = $this->getSettingsSafe();
        $imageService = $this->getImageServiceSafe();
        if (!$settings || !$imageService) {
            Craft::warning('Missing settings or image service in renderPicture', __METHOD__);
            return new Markup('', Craft::$app->charset);
        }

        $options = $this->normalizeOptions($options);
        
        // Generate responsive sources
        $sources = $imageService->generateResponsiveSources($image, $options);

        // Generate sizes attribute
        $sizes = $imageService->generateSizes($settings->getDefaultBreakpoints(), $options['sizes'] ?? []);

        // Build picture tag
        $pictureAttributes = $this->buildPictureAttributes($options);
        $sourceTags = $this->buildSourceTags($sources, $options, $sizes);

        // Fallback img using user-defined transform or default if enabled
        $fallbackSource = reset($sources) ?: [];
        $fallbackTransform = $options['transforms']['mobile'] ?? ($settings->enableDefaultTransforms ? $settings->getDefaultTransforms()['mobile'] ?? ['width' => 480, 'height' => 320, 'quality' => 80] : ['width' => 480, 'height' => 320, 'quality' => 80]);
        $fallbackSrcset = $fallbackSource['sources']['default'] ?? '';
        $fallbackSrc = $image->getUrl($fallbackTransform, true) ?: $image->getUrl();

        // $imgAttributes = $this->buildImgAttributes($image, $options, $fallbackSrcset, $sizes);
        // $imgAttributes['src'] = $this->normalizeUrl($fallbackSrc);

		$imgAttributes = $this->buildImgAttributes($image, $options, $fallbackSrcset, $sizes);
		// Handle null fallbackSrc
		if ($fallbackSrc !== null && is_string($fallbackSrc)) {
			$imgAttributes['src'] = $this->normalizeUrl($fallbackSrc);
		} else {
			Craft::warning('Fallback URL is null for image ID: ' . ($image->id ?? 'unknown'), __METHOD__);
			$imgAttributes['src'] = $imageService->generateLazyPlaceholder($fallbackTransform['width'] ?? 480, $fallbackTransform['height'] ?? 320);
		}

        $html = '<picture' . Html::renderTagAttributes($pictureAttributes) . '>' . "\n";
        $html .= $sourceTags;
        $html .= '    <img' . Html::renderTagAttributes($imgAttributes) . '>' . "\n";
        $html .= '</picture>';

		return new Markup($html, Craft::$app->charset);
	}

	/**
	 * Render simple img tag with srcset
	 */
	public function renderImg(Asset $image, array $options = []): Markup
	{
		if (!$image || $image->kind !== Asset::KIND_IMAGE) {
            return new Markup('', Craft::$app->charset);
        }

        $settings = $this->getSettingsSafe();
        $imageService = $this->getImageServiceSafe();
        if (!$settings || !$imageService) {
			return new Markup('', Craft::$app->charset);
		}

		$options = $this->normalizeOptions($options);

        $largestBreakpoint = array_key_last($settings->getDefaultBreakpoints());
        $transform = $this->getTransformForBreakpoint($image, $options, $largestBreakpoint);

        $srcsetParts = [];

        // AVIF (highest priority)
        if ($options['enableAvif'] && $imageService->supportsAvif($image)) {
            $avifTransform = array_merge($transform, [
                'format'  => 'avif',
                'quality' => $settings->avifQuality ?? 75,
            ]);
            $avifSrcset = $imageService->generateSrcSet($image, $avifTransform, $transform['width']);
            if ($avifSrcset) {
                $srcsetParts[] = $avifSrcset;
            }
        }

        // WebP (second priority)
        if ($options['enableWebP'] && $imageService->supportsWebP($image)) {
            $webpTransform = array_merge($transform, [
                'format'  => 'webp',
                'quality' => $options['quality'] ?? $settings->webpQuality ?? 80,
            ]);
            $webpSrcset = $imageService->generateSrcSet($image, $webpTransform, $transform['width']);
            if ($webpSrcset) {
                $srcsetParts[] = $webpSrcset;
            }
        }

        // PNG/JPG (fallback only)
        $defaultSrcset = $imageService->generateSrcSet($image, $transform, $transform['width']);
        if ($defaultSrcset) {
            $srcsetParts[] = $defaultSrcset;
        }

        $fullSrcset = implode(', ', $srcsetParts);

        $sizes = $imageService->generateSizes($settings->getDefaultBreakpoints(), $options['sizes'] ?? []);

        $imgAttributes = $this->buildImgAttributes($image, $options, $fullSrcset, $sizes);

        $srcUrl = $image->getUrl($transform, true) ?: $image->getUrl();   // Craft auto-adds .webp/.avif if supported
        $imgAttributes['src'] = $this->normalizeUrl($srcUrl);

		$html = '<img' . Html::renderTagAttributes($imgAttributes) . '>';

		return new Markup($html, Craft::$app->charset);
	}

    /**
     * Get transform for a given breakpoint (user → default → auto)
     */
    private function getTransformForBreakpoint(Asset $image, array $options, string $breakpoint): array
    {
        $settings = $this->getSettingsSafe();
        $userTransforms = $settings->ensureArray($options['transforms'] ?? [], []);
        $defaultTransforms = $settings->getDefaultTransforms();

        $transform = $userTransforms[$breakpoint] ??
                    ($settings->enableDefaultTransforms ? ($defaultTransforms[$breakpoint] ?? []) : []);

        $width = (int)($settings->getDefaultBreakpoints()[$breakpoint] ?? 1440);

        if (empty($transform['width'])) {
            $transform['width'] = $width;
        }
        if (empty($transform['height'])) {
            $aspect = $image->getWidth() / ($image->getHeight() ?: 1) ?: 1.5;
            $transform['height'] = (int)round($transform['width'] / $aspect);
        }
        if (empty($transform['quality'])) {
            $transform['quality'] = $options['quality'] ?? $settings->webpQuality ?? 80;
        }

        return $transform;
	}

	/**
	 * Render SVG inline or as img
	 */
	public function renderSvg(Asset $asset, array $options = []): Markup
	{
		if (!$asset || $asset->kind !== Asset::KIND_IMAGE || $asset->getExtension() !== 'svg') {
            Craft::warning('Invalid SVG asset in renderSvg', __METHOD__);
			return new Markup('', Craft::$app->charset);
		}

		$settings = $this->getSettingsSafe();
		if (!$settings) {
            Craft::warning('Missing settings in renderSvg', __METHOD__);
			return new Markup('', Craft::$app->charset);
		}
		$options = $this->normalizeOptions($options);
		
		// Inline SVG
		if ($options['inline'] ?? $settings->inlineSvg) {
			return $this->renderInlineSvg($asset, $options);
		}
		
		// As img tag
		return $this->renderSvgAsImg($asset, $options);
	}

	/**
	 * Render inline SVG
	 */
	public function renderInlineSvg(Asset $asset, array $options = []): Markup
	{
		$imageService = $this->getImageServiceSafe();
		if (!$imageService) {
            Craft::warning('Missing image service in renderInlineSvg', __METHOD__);
			return new Markup('', Craft::$app->charset);
		}
		$svgContent = $imageService->getSvgContent($asset);
		
		if (!$svgContent) {
            Craft::warning('Empty SVG content in renderInlineSvg', __METHOD__);
			return new Markup('', Craft::$app->charset);
		}

		// Apply attributes to SVG
		$svgAttributes = $this->buildSvgAttributes($options);
		
		if (!empty($svgAttributes)) {
			// Find opening svg tag and add attributes
			$svgContent = preg_replace(
				'/<svg([^>]*)>/',
				'<svg$1' . Html::renderTagAttributes($svgAttributes) . '>',
				$svgContent,
				1
			);
		}

		return new Markup($svgContent, Craft::$app->charset);
	}

	/**
	 * Render SVG as img tag
	 */
	public function renderSvgAsImg(Asset $asset, array $options = []): Markup
	{
		$options = $this->normalizeOptions($options);
		$imgAttributes = $this->buildImgAttributes($asset, $options);
		
		$html = '<img' . Html::renderTagAttributes($imgAttributes) . '>';
		
		return new Markup($html, Craft::$app->charset);
	}

	/**
	 * Normalize options array
	 */
	protected function normalizeOptions(array $options): array
	{
		$settings = $this->getSettingsSafe();
		if (!$settings) {
			$settings = new \taherkathiriya\craftpicturetag\models\Settings();
		}
		
		return array_merge([
			'class' => $settings->defaultPictureClass,
			'imgClass' => $settings->defaultImageClass,
			'loading' => $settings->enableLazyLoading ? 'lazy' : 'eager',
			'enableWebP' => $settings->enableWebP,
			'enableAvif' => $settings->enableAvif,
			'quality' => $settings->webpQuality,
			'breakpoints' => $settings->getDefaultBreakpoints(),
			'transforms' => $options['transforms'] ?? [], // User-defined transforms take precedence
			'artDirection' => [],
			'sizes' => [],
			'transform' => [],
			'alt' => null,
			'title' => null,
			'width' => null,
			'height' => null,
			'fetchpriority' => null,
			'preload' => false,
			'inline' => false,
		], $options);
	}

	/**
	 * Build picture element attributes
	 */
	protected function buildPictureAttributes(array $options): array
	{
		$attributes = [];
		
		if (!empty($options['class'])) {
			$attributes['class'] = $options['class'];
		}
		
		if (!empty($options['id'])) {
			$attributes['id'] = $options['id'];
		}
		
		// Add any custom attributes
		if (!empty($options['attributes'])) {
			$attributes = array_merge($attributes, $options['attributes']);
		}
		
		return $attributes;
	}

	/**
	 * Build source tags
	 */
    protected function buildSourceTags(array $sources, array $options, string $sizes): string
	{
		$html = '';
		$settings = $this->getSettingsSafe();
        if (!$settings || empty($sources)) {
			return $html;
		}
		
		// Order sources by format priority (avif -> webp -> default)
		$formatOrder = ['avif', 'webp', 'default'];

        foreach ($formatOrder as $format) {
            if ($format === 'avif' && !$options['enableAvif']) continue;
            if ($format === 'webp' && !$options['enableWebP']) continue;

            foreach ($sources as $source) {
                if (!isset($source['sources'][$format]) || empty($source['sources'][$format])) {
                    continue;
                }

                $srcset = $source['sources'][$format];
				if (empty($srcset)) continue;
				
				$sourceAttributes = [
					'srcset' => $srcset,
					'sizes' => $sizes,
					'media' => $source['media'],
				];
				
				// Add type attribute
				switch ($format) {
					case 'webp':
						$sourceAttributes['type'] = 'image/webp';
						break;
					case 'avif':
						$sourceAttributes['type'] = 'image/avif';
						break;
				}
				
				// Add any custom source attributes
				if (!empty($options['sourceAttributes'])) {
					$sourceAttributes = array_merge($sourceAttributes, $options['sourceAttributes']);
				}
				
				$html .= '    <source' . Html::renderTagAttributes($sourceAttributes) . '>' . "\n";
			}
		}
		
		return $html;
	}

    /**
     * Make URL absolute if root-relative
     */
    private function normalizeUrl(string $url): string
    {
        if ($url && str_starts_with($url, '/')) {
            $base = Craft::$app->getSites()->getCurrentSite()->getBaseUrl();
            return rtrim($base, '/') . $url;
        }
        return $url;
    }

	/**
	 * Build img element attributes
	 */
	protected function buildImgAttributes(Asset $asset, array $options, string $srcset = '', string $sizes = ''): array
	{
		$settings = $this->getSettingsSafe();
		$attributes = [];
		
		// Basic attributes (ensure non-empty URL)
		$rawSrc = $asset->getUrl($options['transform'] ?? []) ?: $asset->getUrl();
		$attributes['src'] = $this->normalizeUrl($rawSrc ?: '');
		
		if (!empty($srcset)) {
			$attributes['srcset'] = $srcset;
		}
		
		if (!empty($sizes)) {
			$attributes['sizes'] = $sizes;
		}
		
		// Alt text
		$alt = $options['alt'] ?? $asset->alt ?? $asset->title ?? ($settings ? $settings->defaultAltText : 'Image');
		if ($settings && $settings->requireAltText && empty($alt)) {
			$alt = $settings->defaultAltText;
		}
		$attributes['alt'] = $alt;
		
		// Title
		if (!empty($options['title'])) {
			$attributes['title'] = $options['title'];
		}
		
		// Class
		if (!empty($options['imgClass'])) {
			$attributes['class'] = $options['imgClass'];
		}
		
		// Dimensions
		if (!empty($options['width'])) {
			$attributes['width'] = $options['width'];
		}
		
		if (!empty($options['height'])) {
			$attributes['height'] = $options['height'];
		}
		
		// Loading
		if (!empty($options['loading'])) {
			$attributes['loading'] = $options['loading'];
		}
		
		// Fetch priority
		if ($settings && $settings->enableFetchPriority) {
			$fetchpriority = $options['fetchpriority'] ?? ($options['loading'] === 'eager' ? 'high' : 'low');
			$attributes['fetchpriority'] = $fetchpriority;
		}
		
		// Lazy loading: keep real src; add placeholder hint and class
		if ($settings && $options['loading'] === 'lazy' && $settings->enableLazyLoading && !empty($settings->lazyPlaceholder)) {
			$attributes['data-placeholder'] = $settings->lazyPlaceholder;
			$attributes['class'] = trim(($attributes['class'] ?? '') . ' ' . $settings->lazyLoadingClass);
		}
		
		// Add any custom attributes
		if (!empty($options['attributes'])) {
			$attributes = array_merge($attributes, $options['attributes']);
		}
		
		return $attributes;
	}

	/**
	 * Build SVG element attributes
	 */
	protected function buildSvgAttributes(array $options): array
	{
		$attributes = [];
		
		// Class
		if (!empty($options['class'])) {
			$attributes['class'] = $options['class'];
		}
		
		// Width and height
		if (!empty($options['width'])) {
			$attributes['width'] = $options['width'];
		}
		
		if (!empty($options['height'])) {
			$attributes['height'] = $options['height'];
		}
		
		// Role for accessibility
		$attributes['role'] = $options['role'] ?? 'img';
		
		// Add any custom attributes
		if (!empty($options['attributes'])) {
			$attributes = array_merge($attributes, $options['attributes']);
		}
		
		return $attributes;
	}
}
