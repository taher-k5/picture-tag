<?php

namespace taherkathiriya\craftpicturetag\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\Html;
use craft\helpers\Template;
use Twig\Markup;
use taherkathiriya\craftpicturetag\PictureTag;
use taherkathiriya\craftpicturetag\models\PictureOptions;

/**
 * Template Service
 */
class TemplateService extends Component
{
    /**
     * Render picture tag
     */
    public function renderPicture(Asset $image, array $options = []): Markup
    {
        if (!$image || !$image->kind === Asset::KIND_IMAGE) {
            return new Markup('', Craft::$app->charset);
        }

        $settings = PictureTag::getInstance()->getSettings();
        $imageService = PictureTag::getInstance()->imageService;
        
        $options = $this->normalizeOptions($options);
        
        // Generate responsive sources
        $sources = $imageService->generateResponsiveSources($image, $options);
        
        // Generate sizes attribute
        $sizes = $imageService->generateSizes($settings->getDefaultBreakpoints(), $options['sizes'] ?? []);
        
        // Build picture tag
        $pictureAttributes = $this->buildPictureAttributes($options);
        $sourceTags = $this->buildSourceTags($sources, $options);
        $imgTag = $this->buildImgTag($image, $options, $sizes);
        
        $html = '<picture' . Html::renderTagAttributes($pictureAttributes) . '>' . "\n";
        $html .= $sourceTags;
        $html .= $imgTag;
        $html .= '</picture>';

        return new Markup($html, Craft::$app->charset);
    }

    /**
     * Render simple img tag with srcset
     */
    public function renderImg(Asset $image, array $options = []): Markup
    {
        if (!$image || !$image->kind === Asset::KIND_IMAGE) {
            return new Markup('', Craft::$app->charset);
        }

        $options = $this->normalizeOptions($options);
        
        // Generate srcset
        $imageService = PictureTag::getInstance()->imageService;
        $transform = $options['transform'] ?? [];
        $srcset = $imageService->generateSrcSet($image, $transform, $transform['width'] ?? 800);
        
        // Generate sizes
        $settings = PictureTag::getInstance()->getSettings();
        $sizes = $imageService->generateSizes($settings->getDefaultBreakpoints(), $options['sizes'] ?? []);
        
        // Build img attributes
        $imgAttributes = $this->buildImgAttributes($image, $options, $srcset, $sizes);
        
        $html = '<img' . Html::renderTagAttributes($imgAttributes) . '>';

        return new Markup($html, Craft::$app->charset);
    }

    /**
     * Render SVG inline or as img
     */
    public function renderSvg(Asset $asset, array $options = []): Markup
    {
        if (!$asset || !$asset->kind === Asset::KIND_IMAGE || $asset->getExtension() !== 'svg') {
            return new Markup('', Craft::$app->charset);
        }

        $settings = PictureTag::getInstance()->getSettings();
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
        $imageService = PictureTag::getInstance()->imageService;
        $svgContent = $imageService->getSvgContent($asset);
        
        if (!$svgContent) {
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
        $settings = PictureTag::getInstance()->getSettings();
        
        return array_merge([
            'class' => $settings->defaultPictureClass,
            'imgClass' => $settings->defaultImageClass,
            'loading' => $settings->enableLazyLoading ? 'lazy' : 'eager',
            'enableWebP' => $settings->enableWebP,
            'enableAvif' => $settings->enableAvif,
            'quality' => $settings->webpQuality,
            'breakpoints' => $settings->getDefaultBreakpoints(),
            'transforms' => $settings->getDefaultTransforms(),
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
    protected function buildSourceTags(array $sources, array $options): string
    {
        $html = '';
        $settings = PictureTag::getInstance()->getSettings();
        
        // Order sources by format priority (avif -> webp -> default)
        $formatOrder = ['avif', 'webp', 'default'];
        
        foreach ($formatOrder as $format) {
            if ($format === 'avif' && !$settings->enableAvif) continue;
            if ($format === 'webp' && !$settings->enableWebP) continue;
            
            foreach ($sources as $source) {
                if (!isset($source['sources'][$format])) continue;
                
                $srcset = $source['sources'][$format];
                if (empty($srcset)) continue;
                
                $sourceAttributes = [
                    'srcset' => $srcset,
                    'sizes' => $options['sizes'] ? implode(', ', $options['sizes']) : null,
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
     * Build img tag
     */
    protected function buildImgTag(Asset $image, array $options, string $sizes): string
    {
        $settings = PictureTag::getInstance()->getSettings();
        $imageService = PictureTag::getInstance()->imageService;
        
        // Get default transform
        $transform = $options['transform'] ?? $settings->getDefaultTransforms()['desktop'] ?? [];
        
        // Generate srcset for fallback img
        $srcset = $imageService->generateSrcSet($image, $transform, $transform['width'] ?? 800);
        
        // Build img attributes
        $imgAttributes = $this->buildImgAttributes($image, $options, $srcset, $sizes);
        
        return '    <img' . Html::renderTagAttributes($imgAttributes) . '>' . "\n";
    }

    /**
     * Build img element attributes
     */
    protected function buildImgAttributes(Asset $asset, array $options, string $srcset = '', string $sizes = ''): array
    {
        $settings = PictureTag::getInstance()->getSettings();
        $attributes = [];
        
        // Basic attributes
        $attributes['src'] = $asset->getUrl($options['transform'] ?? []);
        
        if (!empty($srcset)) {
            $attributes['srcset'] = $srcset;
        }
        
        if (!empty($sizes)) {
            $attributes['sizes'] = $sizes;
        }
        
        // Alt text
        $alt = $options['alt'] ?? $asset->alt ?? $asset->title ?? $settings->defaultAltText;
        if ($settings->requireAltText && empty($alt)) {
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
        if ($settings->enableFetchPriority) {
            $fetchpriority = $options['fetchpriority'] ?? ($options['loading'] === 'eager' ? 'high' : 'low');
            $attributes['fetchpriority'] = $fetchpriority;
        }
        
        // Lazy loading placeholder
        if ($options['loading'] === 'lazy' && $settings->enableLazyLoading && !empty($settings->lazyPlaceholder)) {
            $attributes['data-src'] = $attributes['src'];
            $attributes['src'] = $settings->lazyPlaceholder;
            $attributes['class'] = ($attributes['class'] ?? '') . ' ' . $settings->lazyLoadingClass;
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
