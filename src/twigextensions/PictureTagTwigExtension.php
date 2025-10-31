<?php

namespace taherkathiriya\craftpicturetag\twigextensions;

use Craft;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;
use taherkathiriya\craftpicturetag\PictureTag;
use taherkathiriya\craftpicturetag\services\ImageService;
use taherkathiriya\craftpicturetag\services\TemplateService;

class PictureTagTwigExtension extends AbstractExtension
{
    public function getName(): string
    {
        return 'picture-tag';
    }

    private function getPlugin(): ?PictureTag
    {
        return PictureTag::getInstance();
    }
    private function getTemplateService(): ?TemplateService { return $this->getPlugin()?->templateService; }
    private function getImageService(): ?ImageService { return $this->getPlugin()?->imageService; }
	/**
	 * @inheritdoc
	 */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('craft_picture', [$this, 'craftPicture'], ['is_safe' => ['html']]),
            new TwigFunction('craft_img', [$this, 'craftImg'], ['is_safe' => ['html']]),
            new TwigFunction('craft_svg', [$this, 'craftSvg'], ['is_safe' => ['html']]),
            new TwigFunction('craft_srcset', [$this, 'craftSrcset']),
		];
	}

	/**
	 * Attempt to normalize various inputs into an Asset element
	 */
	private function normalizeAsset($image): ?Asset
	{
		// Already an Asset
        if ($image instanceof Asset) return $image;

		// Asset query (e.g. from an Assets field)
        if ($image instanceof AssetQuery) { $a = $image->one(); return $a instanceof Asset ? $a : null; }

		// Array of assets
        if (is_array($image)) { $first = reset($image); return $first instanceof Asset ? $first : null; }
        if (is_numeric($image)) return Craft::$app->elements->getElementById((int)$image, Asset::class);
		return null;
	}

	/**
	 * Render picture tag
	 */
    public function craftPicture($image, array $options = []): Markup
	{
		$image = $this->normalizeAsset($image);
        if (!$image) return new Markup('', Craft::$app->getView()->getTwig()->getCharset());
        return $this->getTemplateService()?->renderCraftPicture($image, $options) ?? new Markup('', Craft::$app->getView()->getTwig()->getCharset());
    }

    
	/**
	 * Render image tag
	 */
    public function craftImg($image, array $options = []): Markup
	{
		$image = $this->normalizeAsset($image);
        if (!$image) return new Markup('', Craft::$app->getView()->getTwig()->getCharset());
        return $this->getTemplateService()?->renderCraftImg($image, $options) ?? new Markup('', Craft::$app->getView()->getTwig()->getCharset());
    }

	/**
	 * Render SVG
	 */
    public function craftSvg($image, array $options = []): Markup
	{
		$image = $this->normalizeAsset($image);
        if (!$image) return new Markup('', Craft::$app->getView()->getTwig()->getCharset());
        return $this->getTemplateService()?->renderCraftSvg($image, $options) ?? new Markup('', Craft::$app->getView()->getTwig()->getCharset());
	}

	/**
	 * Generate responsive srcset
	 */
    public function craftSrcset($image, array $transform = []): string
	{
		$image = $this->normalizeAsset($image);
        if (!$image) return '';
        $max = $transform['width'] ?? 800;
        return $this->getImageService()?->generateSrcSet($image, $transform, $max) ?? '';
    }
}
