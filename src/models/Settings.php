<?php

namespace taherkathiriya\craftpicturetag\models;

use craft\base\Model;

/**
 * Picture Tag Settings model
 */
class Settings extends Model
{
    public array $defaultBreakpoints = [
        'mobile' => 480,
        'tablet' => 768,
        'desktop' => 1024,
        'large' => 1440,
    ];

    public array $defaultTransforms = [
        'mobile' => ['width' => 480, 'height' => 320, 'quality' => 80],
        'tablet' => ['width' => 768, 'height' => 512, 'quality' => 85],
        'desktop' => ['width' => 1024, 'height' => 683, 'quality' => 90],
        'large' => ['width' => 1440, 'height' => 960, 'quality' => 95],
    ];
    public bool $enableDefaultTransforms = false;

    // Other properties remain unchanged
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
            [['enableWebP', 'enableAvif', 'enableSvgOptimization', 'inlineSvg', 'requireAltText', 'enableCache', 'enableDebug', 'showTransformInfo', 'enableDefaultTransforms', 'enableLazyLoading', 'enablePreload', 'enableSizes', 'enableSrcset', 'enableFetchPriority'], 'boolean'],
            [['webpQuality', 'avifQuality'], 'integer', 'min' => 0, 'max' => 100, 'skipOnEmpty' => true],
            [['svgMaxSize', 'cacheDuration'], 'integer', 'min' => 0, 'skipOnEmpty' => true],
            [['defaultAltText', 'lazyLoadingClass', 'lazyPlaceholder'], 'string', 'skipOnEmpty' => true],
            [['defaultBreakpoints', 'defaultTransforms'], 'safe'], // Allow empty or partial updates
            [['defaultBreakpoints'], 'validateBreakpoints', 'skipOnEmpty' => true],
            [['defaultTransforms'], 'validateTransforms', 'skipOnEmpty' => true, 'when' => fn() => $this->enableDefaultTransforms],
            [['webpQuality', 'avifQuality', 'svgMaxSize', 'cacheDuration'], 'default', 'value' => function ($model, $attribute) {
                return $model->$attribute ?? ($attribute === 'webpQuality' ? 80 : ($attribute === 'avifQuality' ? 75 : ($attribute === 'svgMaxSize' ? 10000 : 3600)));
            }],
            [['defaultAltText'], 'default', 'value' => 'Image'],
            [['defaultBreakpoints'], 'default', 'value' => ['mobile' => 480, 'tablet' => 768, 'desktop' => 1024, 'large' => 1440]],
            [['defaultTransforms'], 'default', 'value' => [
                'mobile' => ['width' => 480, 'height' => 320, 'quality' => 80],
                'tablet' => ['width' => 768, 'height' => 512, 'quality' => 85],
                'desktop' => ['width' => 1024, 'height' => 683, 'quality' => 90],
                'large' => ['width' => 1440, 'height' => 960, 'quality' => 95],
            ]],
        ];
    }

    public function validateBreakpoints($attribute, $params): void
    {
        // If empty or not submitted, use default values
        if (empty($this->$attribute) || !is_array($this->$attribute)) {
            $this->$attribute = $this->getDefaultBreakpoints();
            return;
        }

        // Only validate provided values
        $defaults = $this->getDefaultBreakpoints();
        foreach ($defaults as $name => $defaultValue) {
            if (isset($this->$attribute[$name]) && $this->$attribute[$name] !== '') {
                $width = $this->$attribute[$name];
                if (!is_numeric($width) || $width <= 0) {
                    $this->addError($attribute, "Breakpoint '{$name}' must be a positive number.");
                }
            } else {
                // Use default if not provided
                $this->$attribute[$name] = $defaultValue;
            }
        }
    }

    public function validateTransforms($attribute, $params): void
    {
        // If transforms are disabled or empty, use defaults or empty array
        if (!$this->enableDefaultTransforms || empty($this->$attribute) || !is_array($this->$attribute)) {
            $this->$attribute = $this->enableDefaultTransforms ? $this->getDefaultTransforms() : [];
            return;
        }

        // Only validate provided values
        $defaults = $this->getDefaultTransforms();
        foreach ($defaults as $name => $defaultTransform) {
            if (isset($this->$attribute[$name]) && is_array($this->$attribute[$name])) {
                $transform = $this->$attribute[$name];

                // Validate width
                if (isset($transform['width']) && $transform['width'] !== '') {
                    if (!is_numeric($transform['width']) || $transform['width'] <= 0) {
                        $this->addError($attribute, "Transform '{$name}' width must be a positive number.");
                    }
                } else {
                    $this->$attribute[$name]['width'] = $defaultTransform['width'];
                }

                // Validate height
                if (isset($transform['height']) && $transform['height'] !== '') {
                    if (!is_numeric($transform['height']) || $transform['height'] <= 0) {
                        $this->addError($attribute, "Transform '{$name}' height must be a positive number.");
                    }
                } else {
                    $this->$attribute[$name]['height'] = $defaultTransform['height'];
                }

                // Validate quality
                if (isset($transform['quality']) && $transform['quality'] !== '') {
                    if (!is_numeric($transform['quality']) || $transform['quality'] < 1 || $transform['quality'] > 100) {
                        $this->addError($attribute, "Transform '{$name}' quality must be between 1 and 100.");
                    }
                } else {
                    $this->$attribute[$name]['quality'] = $defaultTransform['quality'];
                }
            } else {
                // Use default transform if not provided
                $this->$attribute[$name] = $defaultTransform;
            }
        }
    }

    public function ensureArray(mixed $value, array $fallback): array
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
        return $this->ensureArray($this->defaultBreakpoints, [
            'mobile' => 480,
            'tablet' => 768,
            'desktop' => 1024,
            'large' => 1440,
        ]);
    }

    public function getDefaultTransforms(): array
    {
        if ($this->enableDefaultTransforms) {
            return $this->ensureArray($this->defaultTransforms, [
                'mobile' => ['width' => 480, 'height' => 320, 'quality' => 80],
                'tablet' => ['width' => 768, 'height' => 512, 'quality' => 85],
                'desktop' => ['width' => 1024, 'height' => 683, 'quality' => 90],
                'large' => ['width' => 1440, 'height' => 960, 'quality' => 95],
            ]);
        }
        return [];
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
