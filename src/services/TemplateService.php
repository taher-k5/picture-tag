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
    private function getSettingsSafe() { return PictureTag::getInstance()?->getSettings(); }
    private function getImageServiceSafe(): ?ImageService { return PictureTag::getInstance()?->imageService; }

    // DEFAULT TRANSFORM
    private function getDefaultTransform(): array
    {
        $settings = $this->getSettingsSafe();
        return [
            'width' => 1440,
            'quality' => $settings->webpQuality ?? 80,
        ];
    }

	/**
	 * Render picture tag
	 */
    public function renderCraftPicture(Asset $image, array $options = []): Markup
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
        $transform = $options['transform'] ?? $this->getDefaultTransform();

        $pictureAttr = $this->buildPictureAttributes($options);
        $sourceTags = $this->buildRasterSourceTags($image, $transform, $options);
        $sizes = $imageService->generateSizes($options['sizes'] ?? []);

        $fallbackSrc = $image->getUrl($transform, true) ?: $image->getUrl();
        $imgAttr = $this->buildImgAttributes($image, $options, '', $sizes);
        $imgAttr['src'] = $this->normalizeUrl($fallbackSrc);

        $html = '<picture' . Html::renderTagAttributes($pictureAttr) . '>'
              . $sourceTags
              . '<img' . Html::renderTagAttributes($imgAttr) . '>'
              . '</picture>';

        return new Markup($html, Craft::$app->charset);
    }

	/**
	 * Render simple img tag with srcset
	 */
    public function renderCraftImg(Asset $image, array $options = []): Markup
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
        $transform = $options['transform'] ?? $this->getDefaultTransform();

        $srcset = [];
        $maxWidth = $transform['width'] ?? 1440;

        // 1. AVIF (highest precedence)
        if ($options['enableAvif'] && $imageService->supportsAvif($image)) {
            $avif = array_merge($transform, ['format' => 'avif']);
            $srcset[] = $imageService->generateSrcSet($image, $avif, $maxWidth);
        }

        // 2. WebP
        if ($options['enableWebP'] && $imageService->supportsWebP($image)) {
            $webp = array_merge($transform, ['format' => 'webp']);
            $srcset[] = $imageService->generateSrcSet($image, $webp, $maxWidth);
        }

        // 3. Original
        $srcset[] = $imageService->generateSrcSet($image, $transform, $maxWidth);

        $fullSrcset = implode(', ', array_filter($srcset));
        $sizes = $imageService->generateSizes($options['sizes'] ?? []);

        $imgAttr = $this->buildImgAttributes($image, $options, $fullSrcset, $sizes);
        $imgAttr['src'] = $this->normalizeUrl($image->getUrl($transform, true) ?: $image->getUrl());

        return new Markup('<img' . Html::renderTagAttributes($imgAttr) . '>', Craft::$app->charset);
    }

	/**
	 * Render SVG inline or as img
	 */
	public function renderCraftSvg(Asset $asset, array $options = []): Markup
	{
		if (!$asset || $asset->kind !== Asset::KIND_IMAGE || $asset->getExtension() !== 'svg') {

			return new Markup('', Craft::$app->charset);
		}

		$settings = $this->getSettingsSafe();
		if (!$settings) {
			return new Markup('', Craft::$app->charset);
		}
        $useInline = $options['inline'] ?? $settings->inlineSvg;
		$options = $this->normalizeOptions($options);

        return $useInline
            ? $this->renderInlineSvg($asset, $options)
            : $this->renderSvgAsImg($asset, $options);
	}

	/**
	 * Render inline SVG
	 */
	public function renderInlineSvg(Asset $asset, array $options = []): Markup
	{
		$imageService = $this->getImageServiceSafe();
		if (!$imageService) {
			return new Markup('', Craft::$app->charset);
		}
		$svgContent = $imageService->getSvgContent($asset);
		
		if (!$svgContent) {
			return new Markup('', Craft::$app->charset);
		}

        $svgAttr = $this->buildSvgAttributes($options);
        if ($svgAttr) {
            $svgContent = preg_replace(
                '/<svg([^>]*)>/',
                '<svg$1' . Html::renderTagAttributes($svgAttr) . '>',
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
        $imgAttr = $this->buildImgAttributes($asset, $options);
        return new Markup('<img' . Html::renderTagAttributes($imgAttr) . '>', Craft::$app->charset);
    }

	/**
	 * Normalize options array
	 */
	protected function normalizeOptions(array $options): array
	{
        $settings = $this->getSettingsSafe() ?? new \taherkathiriya\craftpicturetag\models\Settings();
		
		return array_merge([
			'class' => $settings->defaultPictureClass,
			'imgClass' => $settings->defaultImageClass,
			'loading' => $settings->enableLazyLoading ? 'lazy' : 'eager',
			'enableWebP' => $settings->enableWebP,
			'enableAvif' => $settings->enableAvif,
			'quality' => $settings->webpQuality,
			'sizes' => [],
			'transform' => [],
			'alt' => null,
			'title' => null,
			'width' => null,
			'height' => null,
			'fetchpriority' => null,
			'inline' => false,
            'attributes'   => [],
		], $options);
	}

	/**
	 * Build picture element attributes
	 */
	protected function buildPictureAttributes(array $options): array
	{
        $attr = [];
        if ($options['class'] ?? null) $attr['class'] = $options['class'];
        if ($options['id'] ?? null) $attr['id'] = $options['id'];
        return array_merge($attr, $options['attributes'] ?? []);
    }

	/**
	 * Build source tags
	 */
	protected function buildRasterSourceTags(Asset $image, array $transform, array $options): string
    {
        $imageService = $this->getImageServiceSafe();
        if (!$imageService) return '';

		$html = '';
        $maxWidth = $transform['width'] ?? 1440;
        $sizes = $imageService->generateSizes($options['sizes'] ?? []);

        // AVIF
        if ($options['enableAvif'] && $imageService->supportsAvif($image)) {
            $avif = array_merge($transform, ['format' => 'avif']);
            $srcset = $imageService->generateSrcSet($image, $avif, $maxWidth);
            $html .= '<source type="image/avif" srcset="' . $srcset . '"';
            if ($sizes) $html .= ' sizes="' . $sizes . '"';
            $html .= '>';
        }

        // WebP
        if ($options['enableWebP'] && $imageService->supportsWebP($image)) {
            $webp = array_merge($transform, ['format' => 'webp']);
            $srcset = $imageService->generateSrcSet($image, $webp, $maxWidth);
            $html .= '<source type="image/webp" srcset="' . $srcset . '"';
            if ($sizes) $html .= ' sizes="' . $sizes . '"';
            $html .= '>';
        }

        // Original
        $srcset = $imageService->generateSrcSet($image, $transform, $maxWidth);
        $html .= '<source srcset="' . $srcset . '"';
        if ($sizes) $html .= ' sizes="' . $sizes . '"';
        $html .= '>';

		return $html;
	}

    /**
     * Make URL absolute if root-relative
     */
    private function normalizeUrl(string $url): string
    {
        return $url && str_starts_with($url, '/')
            ? rtrim(Craft::$app->getSites()->getCurrentSite()->getBaseUrl(), '/') . $url
            : $url;
    }

	/**
	 * Build img element attributes
	 */
	protected function buildImgAttributes(Asset $asset, array $options, string $srcset = '', string $sizes = ''): array
	{
		$settings = $this->getSettingsSafe();
        $attr = [];

        $src = $asset->getUrl($options['transform'] ?? []) ?: $asset->getUrl();
        $attr['src'] = $this->normalizeUrl($src);

        if ($srcset) $attr['srcset'] = $srcset;
        if ($sizes) $attr['sizes'] = $sizes;
		
		// Alt text
		$alt = $options['alt'] ?? $asset->alt ?? $asset->title ?? ($settings->defaultAltText ?? 'Image');
        if ($settings->requireAltText && empty($alt)) $alt = $settings->defaultAltText;
        $attr['alt'] = $alt;

		// Title
        if ($options['title'] ?? null) $attr['title'] = $options['title'];

		// Class
		if ($options['imgClass'] ?? null) $attr['class'] = $options['imgClass'];

		// Dimensions
		if ($options['width'] ?? null) $attr['width'] = $options['width'];
        if ($options['height'] ?? null) $attr['height'] = $options['height'];

		// Loading
		if ($options['loading'] ?? null) $attr['loading'] = $options['loading'];

        // Fetch priority
		if ($settings->enableFetchPriority) {
            $attr['fetchpriority'] = $options['fetchpriority'] ?? ($options['loading'] === 'eager' ? 'high' : 'low');
        }

		// Lazy loading: keep real src; add placeholder hint and class
        if ($options['loading'] === 'lazy' && $settings->enableLazyLoading && $settings->lazyPlaceholder) {
            $attr['data-placeholder'] = $settings->lazyPlaceholder;
            $attr['class'] = trim(($attr['class'] ?? '') . ' ' . $settings->lazyLoadingClass);
        }

		// Add any custom attributes
        return array_merge($attr, $options['attributes'] ?? []);
    }
	/**
	 * Build SVG element attributes
	 */
    protected function buildSvgAttributes(array $options): array
    {
        $attr = [];

		// Class
        if ($options['class'] ?? null) $attr['class'] = $options['class'];

		// Width and height
        if ($options['width'] ?? null) $attr['width'] = $options['width'];
        if ($options['height'] ?? null) $attr['height'] = $options['height'];

		// Role for accessibility
        $attr['role'] = $options['role'] ?? 'img';
		
		// Add any custom attributes
        return array_merge($attr, $options['attributes'] ?? []);
    }
}
