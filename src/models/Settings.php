<?php

namespace taherkathiriya\craftpicturetag\models;

use craft\base\Model;

/**
 * Picture Tag Settings model
 */
class Settings extends Model
{
    // ✅ Breakpoints as per your condition
    public array $defaultBreakpoints = [
        'mobile' => 480,   // 0 - 480px
        'tablet' => 768,   // 481 - 768px
        'desktop' => 1024, // 769 - 1024px
        'large' => 1440,   // 1025 - 1440px
    ];

    // ✅ Default transforms off by default, applied only when enabled
    public array $defaultTransforms = [];
    public bool $enableDefaultTransforms = false; // toggle to enable/disable default transforms

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
            [['enableWebP', 'enableAvif', 'enableLazyLoading', 'enablePreload', 'enableSizes', 'enableSrcset', 'enableFetchPriority', 'requireAltText', 'enableArtDirection', 'enableCropping', 'enableFocalPoint', 'enableAspectRatio', 'includeDefaultStyles', 'enableSvgOptimization', 'inlineSvg', 'enableCache', 'enableDebug', 'showTransformInfo', 'enableDefaultTransforms'], 'boolean'],
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

    private function ensureArray(mixed $value, array $fallback): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return $fallback;
    }

    public function getDefaultBreakpoints(): array
    {
        return $this->ensureArray($this->defaultBreakpoints, []);
    }

    public function getDefaultTransforms(): array
    {
        if ($this->enableDefaultTransforms) {
            return $this->ensureArray([
                'mobile' => ['width' => 480, 'height' => 320, 'quality' => 80],
                'tablet' => ['width' => 768, 'height' => 512, 'quality' => 85],
                'desktop' => ['width' => 1024, 'height' => 683, 'quality' => 90],
                'large' => ['width' => 1440, 'height' => 960, 'quality' => 95],
            ], []);
        }
        return $this->ensureArray($this->defaultTransforms, []);
    }

    public function getBreakpointForWidth(int $width): ?string
    {
        $breakpoints = $this->getDefaultBreakpoints();
        if (empty($breakpoints)) {
            return null;
        }

        $keys = array_keys($breakpoints);
        $values = array_values($breakpoints);

        for ($i = 0; $i < count($values); $i++) {
            if ($i == 0 && $width <= $values[$i]) {
                return $keys[$i]; // Mobile: 0 - 480px
            } elseif ($i > 0 && $width > $values[$i - 1] && $width <= $values[$i]) {
                return $keys[$i]; // Tablet: 481 - 768px, Desktop: 769 - 1024px, Large: 1025 - 1440px
            }
        }

        return $keys[count($keys) - 1]; // Beyond large: 1441px+
    }

    public function getTransformForBreakpoint(string $breakpoint): ?array
    {
        $transforms = $this->getDefaultTransforms();
        return $transforms[$breakpoint] ?? null;
    }
}
