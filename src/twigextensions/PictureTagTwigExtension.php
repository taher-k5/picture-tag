<?php

namespace taherkathiriya\craftpicturetag\twigextensions;

use Craft;
use craft\elements\Asset;
use craft\helpers\Template;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;
use Twig\Markup;
use taherkathiriya\craftpicturetag\PictureTag;
use taherkathiriya\craftpicturetag\models\PictureOptions;

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
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('picture', [$this, 'picture'], ['is_safe' => ['html']]),
            new TwigFunction('picture_tag', [$this, 'picture'], ['is_safe' => ['html']]),
            new TwigFunction('img', [$this, 'img'], ['is_safe' => ['html']]),
            new TwigFunction('img_tag', [$this, 'img'], ['is_safe' => ['html']]),
            new TwigFunction('svg', [$this, 'svg'], ['is_safe' => ['html']]),
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
     * Render picture tag
     */
    public function picture($image, array $options = []): Markup
    {
        if (!$image instanceof Asset) {
            return new Markup('', Craft::$app->charset);
        }

        $templateService = PictureTag::getInstance()->templateService;
        return $templateService->renderPicture($image, $options);
    }

    /**
     * Render img tag
     */
    public function img($image, array $options = []): Markup
    {
        if (!$image instanceof Asset) {
            return new Markup('', Craft::$app->charset);
        }

        $templateService = PictureTag::getInstance()->templateService;
        return $templateService->renderImg($image, $options);
    }

    /**
     * Render SVG
     */
    public function svg($asset, array $options = [], ?bool $sanitize = null, ?string $namespace = null): Markup
    {
        if (!$asset instanceof Asset) {
            return new Markup('', Craft::$app->charset);
        }

        if ($sanitize !== null) {
            $options['sanitize'] = $sanitize;
        }

        if ($namespace !== null) {
            $options['namespace'] = $namespace;
        }

        $templateService = PictureTag::getInstance()->templateService;
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
        if (!$image instanceof Asset) {
            return '';
        }

        $imageService = PictureTag::getInstance()->imageService;
        return $imageService->generateSrcSet($image, $transform, $transform['width'] ?? 800);
    }

    /**
     * Generate responsive sizes
     */
    public function responsiveSizes(array $customSizes = []): string
    {
        $settings = PictureTag::getInstance()->getSettings();
        $imageService = PictureTag::getInstance()->imageService;
        return $imageService->generateSizes($settings->getDefaultBreakpoints(), $customSizes);
    }

    /**
     * Debug picture information
     */
    public function pictureDebug($image, array $transform = []): array
    {
        if (!$image instanceof Asset) {
            return [];
        }

        $imageService = PictureTag::getInstance()->imageService;
        return $imageService->getTransformInfo($image, $transform);
    }
}
