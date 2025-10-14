<?php

namespace taherkathiriya\craftpicturetag\twigextensions;

use Craft;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\helpers\Template;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;
use Twig\Markup;
use taherkathiriya\craftpicturetag\PictureTag;
use taherkathiriya\craftpicturetag\models\PictureOptions;
use taherkathiriya\craftpicturetag\services\ImageService;
use taherkathiriya\craftpicturetag\services\TemplateService;

/**
 * Picture Tag Twig Extension
 */
class PictureTagTwigExtension extends AbstractExtension implements GlobalsInterface
{
	/**
	 * @inheritdoc
	 */
	public function getName(): string
	{
		return 'picture-tag';
	}

	/**
	 * Get the plugin instance with error handling
	 */
	private function getPlugin(): ?PictureTag
	{
		try {
			$instance = PictureTag::getInstance();
			if ($instance instanceof PictureTag) {
				return $instance;
			}
		} catch (\Throwable $e) {
			Craft::warning('PictureTag::getInstance() failed: ' . $e->getMessage(), __METHOD__);
		}

		// Fallback: get by handle via Craft's plugin service
		try {
			$plugin = Craft::$app->getPlugins()->getPlugin('picture-tag');
			return $plugin instanceof PictureTag ? $plugin : null;
		} catch (\Throwable $e) {
			Craft::error('Failed to get Picture Tag plugin via plugin service: ' . $e->getMessage(), __METHOD__);
			return null;
		}
	}

	/**
	 * Get template service with error handling
	 */
	private function getTemplateService(): ?TemplateService
	{
		$plugin = $this->getPlugin();
		if (!$plugin) {
			return null;
		}

		try {
			return $plugin->templateService;
		} catch (\Exception $e) {
			Craft::error('Failed to get Picture Tag template service: ' . $e->getMessage(), __METHOD__);
			return null;
		}
	}

	/**
	 * Get image service with error handling
	 */
	private function getImageService(): ?ImageService
	{
		$plugin = $this->getPlugin();
		if (!$plugin) {
			return null;
		}

		try {
			return $plugin->imageService;
		} catch (\Exception $e) {
			Craft::error('Failed to get Picture Tag image service: ' . $e->getMessage(), __METHOD__);
			return null;
		}
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
	 * @inheritdoc
	 */
	public function getGlobals(): array
	{
		return [
			'picture_tag' => $this,
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
			return new Markup('', Craft::$app->charset);
		}

		return $templateService->renderImg($image, $options);
	}

	/**
	 * Render SVG
	 */
	public function svg($asset, array $options = []): Markup
	{
		$asset = $this->normalizeAsset($asset);
		if (!$asset instanceof Asset) {
			return new Markup('', Craft::$app->charset);
		}

		$templateService = $this->getTemplateService();
		if (!$templateService) {
			return new Markup('', Craft::$app->charset);
		}

		return $templateService->renderSvg($asset, $options);
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
			return [];
		}
		return $imageService->getTransformInfo($image, $transform);
	}
}
