<?php

namespace taherkathiriya\craftpicturetag\models;

use craft\base\Model;

/**
 * Picture Options model
 */
class PictureOptions extends Model
{
    public ?string $class = null;
    public ?string $imgClass = null;
    public ?string $id = null;
    public ?string $alt = null;
    public ?string $title = null;
    public ?int $width = null;
    public ?int $height = null;
    public ?string $loading = null;
    public ?string $fetchpriority = null;
    public bool $preload = false;
    public bool $inline = false;
    public array $attributes = [];
    public array $sourceAttributes = [];
    public array $breakpoints = [];
    public array $transforms = [];
    public array $artDirection = [];
    public array $sizes = [];
    public array $transform = [];
    public ?int $quality = null;
    public ?bool $enableWebP = null;
    public ?bool $enableAvif = null;
    public ?string $role = null;
    // public mixed $sanitize = null;
    // public mixed $namespace = null;

    public function rules(): array
    {
        return [
            [['class', 'imgClass', 'id', 'alt', 'title', 'loading', 'fetchpriority', 'role'], 'string'],
            // [['class', 'imgClass', 'id', 'alt', 'title', 'loading', 'fetchpriority', 'role', 'namespace'], 'string'],
            [['width', 'height', 'quality'], 'integer', 'min' => 1],
            [['preload', 'inline'], 'boolean'],
            [['enableWebP', 'enableAvif'], 'boolean'],
            // [['sanitize'], 'safe'], // Can be boolean, string, or other values
            [['attributes', 'sourceAttributes', 'breakpoints', 'transforms', 'artDirection', 'sizes', 'transform'], 'safe'],
        ];
    }

    /**
     * Set custom sizes
     */
    public function setSizes(array $sizes): self
    {
        $this->sizes = $sizes;
        return $this;
    }

    /**
     * Add custom size
     */
    public function addSize(string $size): self
    {
        $this->sizes[] = $size;
        return $this;
    }

    /**
     * Set custom attribute
     */
    public function setAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Set source attribute
     */
    public function setSourceAttribute(string $name, mixed $value): self
    {
        $this->sourceAttributes[$name] = $value;
        return $this;
    }

    /**
     * Enable lazy loading
     */
    public function lazy(bool $lazy = true): self
    {
        $this->loading = $lazy ? 'lazy' : 'eager';
        return $this;
    }

    /**
     * Set loading priority
     */
    public function priority(string $priority): self
    {
        $this->fetchpriority = $priority;
        return $this;
    }

    /**
     * Enable preload
     */
    public function preload(bool $preload = true): self
    {
        $this->preload = $preload;
        return $this;
    }

    /**
     * Set image class
     */
    public function imageClass(string $class): self
    {
        $this->imgClass = $class;
        return $this;
    }

    /**
     * Set picture class
     */
    public function pictureClass(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Set dimensions
     */
    public function dimensions(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * Set alt text
     */
    public function alt(string $alt): self
    {
        $this->alt = $alt;
        return $this;
    }

    /**
     * Set title
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Enable WebP format
     */
    public function webp(bool $enable = true): self
    {
        $this->enableWebP = $enable;
        return $this;
    }

    /**
     * Enable AVIF format
     */
    public function avif(bool $enable = true): self
    {
        $this->enableAvif = $enable;
        return $this;
    }

    /**
     * Set quality
     */
    public function quality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * Make inline (for SVG)
     */
    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;
        return $this;
    }

    // /**
    //  * Set sanitize option for SVG
    //  */
    // public function sanitize(mixed $sanitize = true): self
    // {
    //     $this->sanitize = $sanitize;
    //     return $this;
    // }

    // /**
    //  * Set namespace for SVG
    //  */
    // public function namespace(string $namespace): self
    // {
    //     $this->namespace = $namespace;
    //     return $this;
    // }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Remove null values
        return array_filter($array, fn($value) => $value !== null && $value !== []);
    }
}
