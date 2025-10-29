<?php

namespace taherkathiriya\craftpicturetag\models;

use Craft;
use craft\base\Model;

/**
 * Picture Tag Settings model
 */
class Settings extends Model
{
    public const PLACEHOLDER_DEFAULT_TRANSFORMS = [
        'mobile' => ['width' => 480, 'height' => 320, 'quality' => 80],
        'tablet' => ['width' => 768, 'height' => 512, 'quality' => 85],
        'desktop' => ['width' => 1024, 'height' => 683, 'quality' => 90],
        'large' => ['width' => 1440, 'height' => 960, 'quality' => 95],
    ];
    public const PLACEHOLDER_WEBP_QUALITY = 80;
    public const PLACEHOLDER_AVIF_QUALITY = 75;
    public const PLACEHOLDER_LAZY_LOADING_CLASS = 'lazy';
    public const PLACEHOLDER_LAZY_PLACEHOLDER = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9IiNmNWY1ZjUiLz48L3N2Zz4=';
    public const PLACEHOLDER_DEFAULT_ALT_TEXT = 'Image';
    public const PLACEHOLDER_DEFAULT_PICTURE_CLASS = 'picture-responsive';
    public const PLACEHOLDER_DEFAULT_IMAGE_CLASS = 'picture-img';
    public const PLACEHOLDER_SVG_MAX_SIZE = 1024;
    public const PLACEHOLDER_CACHE_DURATION = 86400;

    // Main value properties (saved to project.yaml)
    public array $defaultTransforms = [];
    public bool $enableDefaultTransforms = false;
    public bool $enableWebP = true;
    public bool $enableAvif = false;
    public ?int $webpQuality = null;
    public ?int $avifQuality = null;
    public bool $enableLazyLoading = true;
    public ?string $lazyLoadingClass = null;
    public ?string $lazyPlaceholder = null;
    public bool $enablePreload = false;
    public bool $enableSizes = true;
    public bool $enableSrcset = true;
    public bool $enableFetchPriority = true;
    public bool $requireAltText = true;
    public ?string $defaultAltText = null;
    public bool $enableArtDirection = true;
    public bool $enableCropping = true;
    public bool $enableFocalPoint = true;
    public bool $enableAspectRatio = true;
    public bool $includeDefaultStyles = true;
    public ?string $defaultPictureClass = null;
    public ?string $defaultImageClass = null;
    public bool $enableSvgOptimization = true;
    public bool $inlineSvg = false;
    public ?int $svgMaxSize = null;
    public bool $enableCache = true;
    public ?int $cacheDuration = null;
    public bool $enableDebug = false;
    public bool $showTransformInfo = false;

    public function rules(): array
    {
        return [
            [['enableWebP', 'enableAvif', 'enableSvgOptimization', 'inlineSvg', 'requireAltText', 'enableCache', 'enableDebug', 'showTransformInfo', 'enableDefaultTransforms', 'enableLazyLoading', 'enablePreload', 'enableSizes', 'enableSrcset', 'enableFetchPriority', 'enableArtDirection', 'enableCropping', 'enableFocalPoint', 'enableAspectRatio', 'includeDefaultStyles'], 'boolean', 'skipOnEmpty' => true],
            [['webpQuality', 'avifQuality', 'svgMaxSize', 'cacheDuration'], 'integer', 'min' => 0, 'skipOnEmpty' => true],
            [['defaultAltText', 'lazyLoadingClass', 'lazyPlaceholder', 'defaultPictureClass', 'defaultImageClass'], 'string', 'skipOnEmpty' => true],
            [['defaultTransforms'], 'safe'],
            [['defaultTransforms'], 'validateTransforms', 'skipOnEmpty' => true, 'when' => fn() => $this->enableDefaultTransforms],
        ];
    }

    public function validateTransforms($attribute, $params): void
    {
        if (!$this->enableDefaultTransforms) {
            $this->$attribute = [];
            Craft::info('Default transforms cleared due to enableDefaultTransforms being false', __METHOD__);
            return;
        }

        if (!is_array($this->$attribute)) {
            $this->addError($attribute, 'Transforms must be an array.');
            return;
        }

        foreach ($this->$attribute as $name => $transform) {
            if (!is_array($transform)) {
                $this->addError($attribute, "Transform '{$name}' must be an array.");
                continue;
            }

            if (isset($transform['width']) && !empty($transform['width']) && (!is_numeric($transform['width']) || $transform['width'] <= 0)) {
                $this->addError($attribute, "Transform '{$name}' width must be a positive number.");
            }

            if (isset($transform['height']) && !empty($transform['height']) && (!is_numeric($transform['height']) || $transform['height'] <= 0)) {
                $this->addError($attribute, "Transform '{$name}' height must be a positive number.");
            }

            if (isset($transform['quality']) && !empty($transform['quality']) && (!is_numeric($transform['quality']) || $transform['quality'] < 1 || $transform['quality'] > 100)) {
                $this->addError($attribute, "Transform '{$name}' quality must be between 1 and 100.");
            }

            // Cast values to integers
            if (isset($transform['width'])) {
                $this->$attribute[$name]['width'] = (int)$transform['width'];
            }
            if (isset($transform['height'])) {
                $this->$attribute[$name]['height'] = (int)$transform['height'];
            }
            if (isset($transform['quality'])) {
                $this->$attribute[$name]['quality'] = (int)$transform['quality'];
            }
        }
    }

    public function ensureArray(mixed $value, array $fallback): array
    {
        if (is_array($value) && !empty($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }
        return $fallback;
    }

    public function getDefaultTransforms(): array
    {
        if (!$this->enableDefaultTransforms) {
            return [];
        }
        $transforms = $this->ensureArray($this->defaultTransforms, self::PLACEHOLDER_DEFAULT_TRANSFORMS);
        foreach ($transforms as $name => $transform) {
            if (isset($transform['width'])) {
                $transforms[$name]['width'] = (int)$transform['width'];
            }
            if (isset($transform['height'])) {
                $transforms[$name]['height'] = (int)$transform['height'];
            }
            if (isset($transform['quality'])) {
                $transforms[$name]['quality'] = (int)$transform['quality'];
            }
        }
        return $transforms;
    }

    /**
     * Save settings to project config
     */
    public function saveSettings(): bool
    {
        // Force clear defaultTransforms if enableDefaultTransforms is false
        if (!$this->enableDefaultTransforms) {
            $this->defaultTransforms = [];
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $pluginHandle = 'picture-tag';
        $configData = [
            'defaultTransforms' => $this->enableDefaultTransforms ? $this->defaultTransforms : [],
            'enableDefaultTransforms' => $this->enableDefaultTransforms,
            'enableWebP' => $this->enableWebP,
            'enableAvif' => $this->enableAvif,
            'webpQuality' => $this->webpQuality,
            'avifQuality' => $this->avifQuality,
            'enableLazyLoading' => $this->enableLazyLoading,
            'lazyLoadingClass' => $this->lazyLoadingClass,
            'lazyPlaceholder' => $this->lazyPlaceholder,
            'enablePreload' => $this->enablePreload,
            'enableSizes' => $this->enableSizes,
            'enableSrcset' => $this->enableSrcset,
            'enableFetchPriority' => $this->enableFetchPriority,
            'requireAltText' => $this->requireAltText,
            'defaultAltText' => $this->defaultAltText,
            'enableArtDirection' => $this->enableArtDirection,
            'enableCropping' => $this->enableCropping,
            'enableFocalPoint' => $this->enableFocalPoint,
            'enableAspectRatio' => $this->enableAspectRatio,
            'includeDefaultStyles' => $this->includeDefaultStyles,
            'defaultPictureClass' => $this->defaultPictureClass,
            'defaultImageClass' => $this->defaultImageClass,
            'enableSvgOptimization' => $this->enableSvgOptimization,
            'inlineSvg' => $this->inlineSvg,
            'svgMaxSize' => $this->svgMaxSize,
            'enableCache' => $this->enableCache,
            'cacheDuration' => $this->cacheDuration,
            'enableDebug' => $this->enableDebug,
            'showTransformInfo' => $this->showTransformInfo,
        ];

        Craft::info('Saving picture-tag settings: ' . print_r($configData, true), __METHOD__);
        $projectConfig->set("plugins.{$pluginHandle}.settings", $configData);
        return true;
    }
}