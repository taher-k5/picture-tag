<?php

namespace taherkathiriya\craftpicturetag\models;

use craft\base\Model;
use craft\helpers\ConfigHelper;

/**
 * Picture Tag Settings model
 */
class Settings extends Model
{
    // Default responsive breakpoints
    public array $defaultBreakpoints = [
        'mobile' => 480,
        'tablet' => 768,
        'desktop' => 1024,
        'large' => 1200,
    ];

    // Default image transforms
    public array $defaultTransforms = [
        'mobile' => ['width' => 480, 'height' => 320, 'quality' => 80],
        'tablet' => ['width' => 768, 'height' => 512, 'quality' => 85],
        'desktop' => ['width' => 1024, 'height' => 683, 'quality' => 90],
        'large' => ['width' => 1200, 'height' => 800, 'quality' => 95],
    ];

    // WebP settings
    public bool $enableWebP = true;
    public bool $enableAvif = false;
    public int $webpQuality = 80;
    public int $avifQuality = 75;

    // Lazy loading settings
    public bool $enableLazyLoading = true;
    public string $lazyLoadingClass = 'lazy';
    public string $lazyPlaceholder = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9IiNmNWY1ZjUiLz48L3N2Zz4=';

    // Performance settings
    public bool $enablePreload = false;
    public bool $enableSizes = true;
    public bool $enableSrcset = true;
    public bool $enableFetchPriority = true;

    // Accessibility settings
    public bool $requireAltText = true;
    public string $defaultAltText = 'Image';

    // Advanced settings
    public bool $enableArtDirection = true;
    public bool $enableCropping = true;
    public bool $enableFocalPoint = true;
    public bool $enableAspectRatio = true;

    // CSS and styling
    public bool $includeDefaultStyles = true;
    public string $defaultPictureClass = 'picture-responsive';
    public string $defaultImageClass = 'picture-img';

    // SVG handling
    public bool $enableSvgOptimization = true;
    public bool $inlineSvg = false;
    public int $svgMaxSize = 1024; // bytes

    // Cache settings
    public bool $enableCache = true;
    public int $cacheDuration = 86400; // 24 hours in seconds

    // Debug settings
    public bool $enableDebug = false;
    public bool $showTransformInfo = false;

    public function rules(): array
    {
        return [
            [['defaultBreakpoints'], 'validateBreakpoints'],
            [['defaultTransforms'], 'validateTransforms'],
            [['webpQuality', 'avifQuality'], 'integer', 'min' => 1, 'max' => 100],
            [['cacheDuration'], 'integer', 'min' => 60],
            [['svgMaxSize'], 'integer', 'min' => 100],
            [['lazyLoadingClass', 'defaultAltText', 'defaultPictureClass', 'defaultImageClass'], 'string'],
            [['lazyPlaceholder'], 'string'],
            [['enableWebP', 'enableAvif', 'enableLazyLoading', 'enablePreload', 'enableSizes', 'enableSrcset', 'enableFetchPriority', 'requireAltText', 'enableArtDirection', 'enableCropping', 'enableFocalPoint', 'enableAspectRatio', 'includeDefaultStyles', 'enableSvgOptimization', 'inlineSvg', 'enableCache', 'enableDebug', 'showTransformInfo'], 'boolean'],
        ];
    }

    public function validateBreakpoints($attribute, $params): void
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, 'Breakpoints must be an array.');
            return;
        }

        foreach ($this->$attribute as $name => $width) {
            if (!is_numeric($width) || $width <= 0) {
                $this->addError($attribute, "Breakpoint '{$name}' must be a positive number.");
            }
        }
    }

    public function validateTransforms($attribute, $params): void
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, 'Transforms must be an array.');
            return;
        }

        foreach ($this->$attribute as $name => $transform) {
            if (!is_array($transform)) {
                $this->addError($attribute, "Transform '{$name}' must be an array.");
                continue;
            }

            if (isset($transform['width']) && (!is_numeric($transform['width']) || $transform['width'] <= 0)) {
                $this->addError($attribute, "Transform '{$name}' width must be a positive number.");
            }

            if (isset($transform['height']) && (!is_numeric($transform['height']) || $transform['height'] <= 0)) {
                $this->addError($attribute, "Transform '{$name}' height must be a positive number.");
            }

            if (isset($transform['quality']) && (!is_numeric($transform['quality']) || $transform['quality'] < 1 || $transform['quality'] > 100)) {
                $this->addError($attribute, "Transform '{$name}' quality must be between 1 and 100.");
            }
        }
    }

    public function getDefaultBreakpoints(): array
    {
        return ConfigHelper::localizedValue($this->defaultBreakpoints);
    }

    public function getDefaultTransforms(): array
    {
        return ConfigHelper::localizedValue($this->defaultTransforms);
    }

    public function getBreakpointForWidth(int $width): ?string
    {
        $breakpoints = $this->getDefaultBreakpoints();
        
        foreach ($breakpoints as $name => $breakpointWidth) {
            if ($width <= $breakpointWidth) {
                return $name;
            }
        }

        // Return the largest breakpoint if width exceeds all
        return array_key_last($breakpoints);
    }

    public function getTransformForBreakpoint(string $breakpoint): ?array
    {
        $transforms = $this->getDefaultTransforms();
        return $transforms[$breakpoint] ?? null;
    }
}
