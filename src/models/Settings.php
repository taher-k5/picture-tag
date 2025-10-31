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
    public const PLACEHOLDER_LAZY_PLACEHOLDER = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9IiNmNWY1ZjUiLz48L3N2Zz4=';
    public const PLACEHOLDER_CACHE_DURATION = 86400;

    // Main value properties (saved to project.yaml)
    public array $defaultTransforms = [];
    public bool $enableDefaultTransforms = false;
    public bool $enableWebP = true;
    public bool $enableAvif = false;
    public ?int $webpQuality = null;
    public ?int $avifQuality = null;
    public bool $enableLazyLoading = true;
    public ?string $lazyPlaceholder = null;
    public bool $enableSvgOptimization = true;
    public bool $inlineSvg = false;
    public bool $enableCache = true;
    public ?int $cacheDuration = null;
    public bool $enableDebug = false;
    // public bool $enableSvgSanitization = true;

    public function rules(): array
    {
        return [
            // Booleans
            [['enableDefaultTransforms','enableWebP','enableAvif','enableLazyLoading',
              'enableSvgOptimization','inlineSvg','enableCache','enableDebug'], 'boolean'],

            // Integers (0-100 for quality, any positive for cache)
            [['webpQuality','avifQuality'], 'integer', 'min' => 0, 'max' => 100, 'skipOnEmpty' => true],
            [['cacheDuration'], 'integer', 'min' => 0, 'skipOnEmpty' => true],

            // Strings
            [['lazyPlaceholder'], 'string', 'skipOnEmpty' => true],

            // Transforms
            [['defaultTransforms'], 'safe'],
            [['defaultTransforms'], 'validateTransforms', 'when' => fn() => $this->enableDefaultTransforms],
        ];
    }

    public function validateTransforms($attribute, $params): void
    {
        if (!$this->enableDefaultTransforms) {
            $this->$attribute = [];
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

            foreach (['width','height','quality'] as $key) {
                if (isset($transform[$key]) && (!is_numeric($transform[$key]) || $transform[$key] <= 0)) {
                    $this->addError($attribute, "Transform '{$name}' {$key} must be a positive number.");
                }
                if (isset($transform[$key])) {
                    $this->$attribute[$name][$key] = (int)$transform[$key];
                }
            }
            if (isset($transform['quality'])) {
                $this->$attribute[$name]['quality'] = min(100, max(1, (int)$transform['quality']));
            }
        }
    }

    public function ensureArray(mixed $value, array $fallback): array
    {
        if (is_array($value) && $value !== []) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && $decoded !== []) {
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
        foreach ($transforms as $name => $t) {
            foreach (['width','height','quality'] as $k) {
                if (isset($t[$k])) $transforms[$name][$k] = (int)$t[$k];
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
        $data = [
            'defaultTransforms' => $this->enableDefaultTransforms ? $this->defaultTransforms : [],
            'enableDefaultTransforms' => $this->enableDefaultTransforms,
            'enableWebP' => $this->enableWebP,
            'enableAvif' => $this->enableAvif,
            'webpQuality' => $this->webpQuality,
            'avifQuality' => $this->avifQuality,
            'enableLazyLoading' => $this->enableLazyLoading,
            'lazyPlaceholder' => $this->lazyPlaceholder,
            'enableSvgOptimization' => $this->enableSvgOptimization,
            'inlineSvg' => $this->inlineSvg,
            'enableCache' => $this->enableCache,
            'cacheDuration' => $this->cacheDuration,
            'enableDebug' => $this->enableDebug,
            // 'enableSvgSanitization' => $this->enableSvgSanitization,
        ];

        $projectConfig->set("plugins.{$pluginHandle}.settings", $data);
        return true;
    }
}