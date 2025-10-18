<?php

namespace taherkathiriya\craftpicturetag\twigextensions;

use Craft;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;
use taherkathiriya\craftpicturetag\Plugin;
use taherkathiriya\craftpicturetag\models\PictureOptions;
use taherkathiriya\craftpicturetag\services\ImageService;
use taherkathiriya\craftpicturetag\services\TemplateService;

class PictureTagTwigExtension extends AbstractExtension
{
    public function getName(): string
    {
        return 'picture-tag';
    }

    private function getPlugin(): ?Plugin
    {
        return Plugin::getInstance();
    }

    private function getTemplateService(): ?TemplateService
    {
        $plugin = $this->getPlugin();
        return $plugin ? $plugin->templateService : null;
    }

    private function getImageService(): ?ImageService
    {
        $plugin = $this->getPlugin();
        return $plugin ? $plugin->imageService : null;
    }

	/**
	 * @inheritdoc
	 */
	public function getFunctions(): array
	{
		return [
			new TwigFunction('picture', [$this, 'picture'], ['is_safe' => ['html']]),
			new TwigFunction('picture_tag', [$this, 'picture'], ['is_safe' => ['html']]),
			new TwigFunction('img', [$this, 'img'], ['is_safe' => ['html']]),
			new TwigFunction('img_tag', [$this, 'img'], ['is_safe' => ['html']]),
			// new TwigFunction('svg', [$this, 'svg'], ['is_safe' => ['html']]),
			new TwigFunction('svg_tag', [$this, 'svg'], ['is_safe' => ['html']]),
			new TwigFunction('picture_options', [$this, 'createPictureOptions']),
			new TwigFunction('responsive_srcset', [$this, 'responsiveSrcset']),
			new TwigFunction('responsive_sizes', [$this, 'responsiveSizes']),
			new TwigFunction('picture_debug', [$this, 'pictureDebug']),
		];
	}

	/**
	 * Attempt to normalize various inputs into an Asset element
	 */
	private function normalizeAsset($image): ?Asset
	{
		// Already an Asset
		if ($image instanceof Asset) {
			return $image;
		}

		// Asset query (e.g. from an Assets field)
		if ($image instanceof AssetQuery) {
			$asset = $image->one();
			return $asset instanceof Asset ? $asset : null;
		}

		// Array of assets
		if (is_array($image)) {
			$first = reset($image);
			return $first instanceof Asset ? $first : null;
		}

		// Numeric ID
		if (is_numeric($image)) {
			$asset = Craft::$app->getElements()->getElementById((int)$image, Asset::class);
			return $asset instanceof Asset ? $asset : null;
		}
        Craft::warning('Invalid image input in normalizeAsset', __METHOD__);
		return null;
	}

	/**
	 * Render picture tag
	 */
	public function picture($image, array $options = []): Markup
	{
		$image = $this->normalizeAsset($image);
		if (!$image instanceof Asset) {
			return new Markup('', Craft::$app->charset);
		}

		$templateService = $this->getTemplateService();
		if (!$templateService) {
            Craft::warning('Missing template service in picture', __METHOD__);
			return new Markup('', Craft::$app->charset);
		}

		return $templateService->renderPicture($image, $options);
	}

	/**
	 * Render img tag
	 */
	public function img($image, array $options = []): Markup
	{
		$image = $this->normalizeAsset($image);
		if (!$image instanceof Asset) {
			return new Markup('', Craft::$app->charset);
		}

		$templateService = $this->getTemplateService();
		if (!$templateService) {
            Craft::warning('Missing template service in img', __METHOD__);
			return new Markup('', Craft::$app->charset);
		}

		return $templateService->renderImg($image, $options);
	}

	/**
	 * Render SVG
	 */
	public function svg($image, array $options = []): Markup
	{
		$image = $this->normalizeAsset($image);
		if (!$image instanceof Asset) {
			return new Markup('', Craft::$app->charset);
		}

		$templateService = $this->getTemplateService();
		if (!$templateService) {
            Craft::warning('Missing template service in svg', __METHOD__);
			return new Markup('', Craft::$app->charset);
		}

		return $templateService->renderSvg($image, $options);
	}

	/**
	 * Create picture options object
	 */
	public function createPictureOptions(): PictureOptions
	{
		return new PictureOptions();
	}

	/**
	 * Generate responsive srcset
	 */
	public function responsiveSrcset($image, array $transform = []): string
	{
		$image = $this->normalizeAsset($image);
		if (!$image instanceof Asset) {
			return '';
		}

		$imageService = $this->getImageService();
		if (!$imageService) {
            Craft::warning('Missing image service in responsiveSrcset', __METHOD__);
			return '';
		}
		return $imageService->generateSrcSet($image, $transform, $transform['width'] ?? 800);
	}

	/**
	 * Generate responsive sizes
	 */
	public function responsiveSizes(array $customSizes = []): string
	{
		$plugin = $this->getPlugin();
		$imageService = $this->getImageService();
		if (!$plugin || !$imageService) {
            Craft::warning('Missing plugin or image service in responsiveSizes', __METHOD__);
			return '';
		}
		$settings = $plugin->getSettings();
		return $imageService->generateSizes($settings->getDefaultBreakpoints(), $customSizes);
	}

	/**
	 * Debug picture information
	 */
	public function pictureDebug($image, array $transform = []): array
	{
		$image = $this->normalizeAsset($image);
		if (!$image instanceof Asset) {
			return [];
		}

		$imageService = $this->getImageService();
		if (!$imageService) {
            Craft::warning('Missing image service in pictureDebug', __METHOD__);
			return [];
		}
		return $imageService->getTransformInfo($image, $transform);
	}
}
