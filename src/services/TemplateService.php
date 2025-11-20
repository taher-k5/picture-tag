<?php

namespace SFS\craftpicturetag\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\Html;
use Twig\Markup;
use SFS\craftpicturetag\PictureTag;

/**
 * template service
 */
class TemplateService extends Component
{
    private function getSettingsSafe() { return PictureTag::getInstance()?->getSettings(); }
    private function getImageServiceSafe(): ?ImageService { return PictureTag::getInstance()?->imageService; }

    private function getCharset(): string
    {
        return Craft::$app->getView()->getTwig()->getCharset();
    }

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
        if (!$image || $image->kind !== Asset::KIND_IMAGE) return new Markup('', Craft::$app->getView()->getTwig()->getCharset());

        $settings = $this->getSettingsSafe();
        $imageService = $this->getImageServiceSafe();
        if (!$settings || !$imageService) return new Markup('', Craft::$app->getView()->getTwig()->getCharset());

        $options = $this->normalizeOptions($options);
        $transform = $options['transform'] ?? $this->getDefaultTransform();

        $sourceTags = $this->buildRasterSourceTags($image, $transform, $options);
        $fallbackSrc = $image->getUrl($transform, true) ?: $image->getUrl();
        $imgAttr = $this->buildImgAttributes($image, $options, '', '', true);
        $imgAttr['src'] = $this->normalizeUrl($fallbackSrc);
        $pictureAttr = $this->buildPictureAttributes($options);

        $pictureAttrHtml = !empty($pictureAttr) ? Html::renderTagAttributes($pictureAttr) : '';

        $html = '<picture' . $pictureAttrHtml . '>' . $sourceTags .
                '<img' . Html::renderTagAttributes($imgAttr) . '></picture>';

        return new Markup($html, Craft::$app->getView()->getTwig()->getCharset());
    }

	/**
	 * Render simple img tag with srcset
	 */
    public function renderCraftImg(Asset $image, array $options = []): Markup
	{
        if (!$image || $image->kind !== Asset::KIND_IMAGE) return new Markup('', Craft::$app->getView()->getTwig()->getCharset());

        $settings = $this->getSettingsSafe();
        $imageService = $this->getImageServiceSafe();
        if (!$settings || !$imageService) return new Markup('', Craft::$app->getView()->getTwig()->getCharset());

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

        $imgAttr = $this->buildImgAttributes($image, $options, $fullSrcset, '');
        $imgAttr['src'] = $this->normalizeUrl($image->getUrl($transform, true) ?: $image->getUrl());

        return new Markup('<img' . Html::renderTagAttributes($imgAttr) . '>', Craft::$app->getView()->getTwig()->getCharset());
    }

	/**
	 * Render SVG inline or as img
	 */
	public function renderCraftSvg(Asset $asset, array $options = []): Markup
	{
		if (!$asset || $asset->kind !== Asset::KIND_IMAGE || $asset->getExtension() !== 'svg') {

			return new Markup('', Craft::$app->getView()->getTwig()->getCharset());
		}

		$settings = $this->getSettingsSafe();
		if (!$settings) {
			return new Markup('', Craft::$app->getView()->getTwig()->getCharset());;
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
    private function renderInlineSvg(Asset $asset, array $options): Markup
    {
        $imageService = $this->getImageServiceSafe();
        $content = $imageService?->getSvgContent($asset);
        if (!$content) return new Markup('', Craft::$app->getView()->getTwig()->getCharset());

        $svgAttr = $this->buildSvgAttributes($options);
        if ($svgAttr) {
            $content = preg_replace(
                '/<svg([^>]*)>/',
                '<svg$1' . Html::renderTagAttributes($svgAttr) . '>',
                $content,
                1
            );
        }
        return new Markup($content, Craft::$app->getView()->getTwig()->getCharset());
    }

	/**
	 * Render SVG as img tag
	 */
    private function renderSvgAsImg(Asset $asset, array $options): Markup
    {
        $options = $this->normalizeOptions($options);
        $imgAttr = $this->buildImgAttributes($asset, $options);
        return new Markup('<img' . Html::renderTagAttributes($imgAttr) . '>', Craft::$app->getView()->getTwig()->getCharset());
    }

	/**
	 * Normalize options array
	 */
    protected function normalizeOptions(array $options): array
    {
        $settings = $this->getSettingsSafe() ?? new \SFS\craftpicturetag\models\Settings();

        return array_merge([
            'enableWebP' => $settings->enableWebP,
            'enableAvif' => $settings->enableAvif,
            'loading'    => $settings->enableLazyLoading ? 'lazy' : 'eager',
            'transform'  => [],
            'attributes'   => [],
		], $options);
    }

	/**
	 * Build source tags
	 */
    private function buildRasterSourceTags(Asset $image, array $transform, array $options): string
    {
        $imageService = $this->getImageServiceSafe();
        if (!$imageService) return '';

        $html = '';
        $maxWidth = $transform['width'] ?? 1440;
        // AVIF
        if ($options['enableAvif'] && $imageService->supportsAvif($image)) {
            $avif = array_merge($transform, ['format' => 'avif']);
            $srcset = $imageService->generateSrcSet($image, $avif, $maxWidth);
            $html .= '<source type="image/avif" srcset="' . $srcset . '">';
        }

        // WebP
        if ($options['enableWebP'] && $imageService->supportsWebP($image)) {
            $webp = array_merge($transform, ['format' => 'webp']);
            $srcset = $imageService->generateSrcSet($image, $webp, $maxWidth);
            $html .= '<source type="image/webp" srcset="' . $srcset . '">';
        }

        // Original
        $srcset = $imageService->generateSrcSet($image, $transform, $maxWidth);
        $html .= '<source srcset="' . $srcset . '">';

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
	protected function buildImgAttributes(Asset $asset, array $options, string $srcset = '', string $sizes = '', bool $pictureTag = false): array
	{
		$settings = $this->getSettingsSafe();
        $attr = [];

        $src = $asset->getUrl($options['transform'] ?? []) ?: $asset->getUrl();
        $attr['src'] = $this->normalizeUrl($src);

        if ($srcset) $attr['srcset'] = $srcset;
        if ($sizes) $attr['sizes'] = $sizes;

        // === AUTO ALT: user → title → unique fallback ===
        $attr['alt'] = $options['alt'] ?? $asset->title ?? 'Image ' . $asset->id;

		// Loading
		if ($options['loading'] ?? null) $attr['loading'] = $options['loading'];

        // lazy placeholder (only when lazy is on)
        if (($options['loading'] ?? '') === 'lazy' && $settings->enableLazyLoading && $settings->lazyPlaceholder) {
            $attr['data-placeholder'] = $settings->lazyPlaceholder;
        }

        if($pictureTag){
            if (!empty($options['imgClass'])) {
                $attr['class'] = trim($options['imgClass']);
            }
        } else {
            if (!empty($options['class'])) {
                $attr['class'] = trim($options['class']);
            }
        }

        // 🔥 Merge any other custom attributes (id, data-*, aria-*, etc.)
        if (!empty($options['attributes']) && is_array($options['attributes'])) {
            // 'class' may appear in attributes too → merge them safely
            if (!empty($options['attributes']['class'])) {
                $attr['class'] = trim(
                    ($attr['class'] ?? '') . ' ' . $options['attributes']['class']
                );

                unset($options['attributes']['class']);
            }

            $attr = array_merge($attr, $options['attributes']);
        }

        return $attr;
    }

    public function buildPictureAttributes($options){
        $pictureAttr = [];

        if (!empty($options['class'])) {
            $pictureAttr['class'] = $options['class'];
        }

        // Additional picture attributes
        if (!empty($options['attributes']) && is_array($options['attributes'])) {
            // Merge class if defined inside attributes array
            if (!empty($options['attributes']['class'])) {
                $pictureAttr['class'] = trim(
                    ($pictureAttr['class'] ?? '') . ' ' . $options['attributes']['class']
                );
                unset($options['attributes']['class']);
            }

            $pictureAttr = array_merge($pictureAttr, $options['attributes']);
        }

        return $pictureAttr;
    }

	/**
	 * Build SVG element attributes
	 */
    protected function buildSvgAttributes(array $options): array
    {
        $attr = $options['attributes'] ?? [];
		// Class
        if ($options['class'] ?? null) $attr['class'] = $options['class'];

		// Width and height
        if ($options['width'] ?? null) $attr['width'] = $options['width'];
        if ($options['height'] ?? null) $attr['height'] = $options['height'];

		// Role for accessibility
        $attr['role'] = $options['role'] ?? 'img';
        return $attr;
    }
}
