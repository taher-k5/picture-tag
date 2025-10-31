# Picture Tag Plugin for Craft CMS

A powerful and advanced Craft CMS plugin for handling responsive images with WebP and AVIF support, lazy loading and much more.

## Features

### 🖼️ **Advanced Image Handling**
- **WebP & AVIF Support**: Automatically generates modern image formats for better compression
- **Native Lazy Loading**: `loading="lazy"` + 1x1 SVG placeholder 
- **SVG Inline Support**: provide 'inline:true' for all SVG in just one click
- **SVG Optimization**: Remove unwanted strip comments, minify 
- **Caching**: Full transform caching

### 🎨 **Flexible Template Functions**
- `craft_picture()` - Full responsive picture element
- `craft_img()` - Simple responsive img tag
- `craft_svg()` - SVG handling with inline or img tag options
- `craft_srcset()` - Generate srcset strings


### ⚙️ **Comprehensive Configuration**
- Customizable breakpoints and transforms
- Quality settings for different formats
- Performance optimization options
- Accessibility features
- Debug and development tools

### 🚀 **Performance Features**
- Image caching system
- Optimized lazy loading
- Reduced motion support

## Installation

### Via Composer (Recommended)
```bash
composer require taher-kathiriya/craft-picture-tag
```

### Manual Installation
1. Download the plugin files
2. Place them in your `plugins/picture-tag/` directory
3. Install via the Craft CMS control panel

## How to Upgrade

```bash
composer require taherkathiriya/craft-picturetag:^1.0
php craft plugin/install picture-tag
```


## Quick Start

### Basic Usage

```twig
{# Full responsive picture #}
{{ craft_picture(image) }}

{# With options #}
{{ craft_picture(image, {
    transform: { width: 1200, quality: 90 },
    alt: 'My hero image'
}) }}

{# Simple img #}
{{ craft_img(image) }}

{# SVG inline #}
{{ craft_svg(svgAsset, { inline: true }) }}
```

### Advanced Usage with Art Direction

```twig
{{ craft_picture(image, {
    artDirection: {
        mobile: { width: 480, height: 320, mode: 'crop' },
        tablet: { width: 768, height: 400, mode: 'crop' },
        desktop: { width: 1024, height: 600, mode: 'crop' }
    },
    sizes: [
        '(max-width: 768px) 100vw',
        '(min-width: 769px) 50vw'
    ]
}) }}
```


## Configuration

### Plugin Settings

Access the plugin settings in the Craft CMS control panel under **Settings > Plugins > Picture Tag**.

#### Image Formats
- **WebP**: Enable/disable WebP generation
- **AVIF**: Enable/disable AVIF generation (newer browsers)
- **Quality Settings**: Configure quality for each format

#### Performance
- **Lazy Loading**: Enable/disable lazy loading
- **Cache**: Enable image transform caching

#### Accessibility
- **Alt Text**: its by default whenuser not give alt then its use default alt

### Configuration File

Create a `config/picture-tag.php` file to override default settings:

```php
<?php
return [
    'enableWebP' => true,
    'enableAvif' => false,
    'webpQuality' => 80,
    'avifQuality' => 75,
    'enableLazyLoading' => true,
    'lazyPlaceholder' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9IiNmNWY1ZjUiLz48L3N2Zz4=',
    'enableSvgOptimization' => true,
    'inlineSvg' => false,
    'enableCache' => true,
    'cacheDuration' => 86400,
    'enableDebug' => false,
    // ... more settings
];
```

## Examples

### Hero Image
```twig
<section class="hero">
    {{ craft_picture(entry.heroImage.one(), {
        class: 'hero-picture',
        fetchpriority: 'high',
        artDirection: {
            mobile: { width: 480, height: 300, mode: 'crop' },
            tablet: { width: 768, height: 400, mode: 'crop' },
            desktop: { width: 1200, height: 600, mode: 'crop' }
        },
        sizes: [
            '(max-width: 768px) 100vw',
            '100vw'
        ]
    }) }}
</section>
```

### Image Gallery
```twig
<div class="gallery">
    {% for image in entry.galleryImages.all() %}
        <div class="gallery-item">
            {{ craft_picture(image, {
                class: 'gallery-picture',
                loading: loop.first ? 'eager' : 'lazy',
                fetchpriority: loop.first ? 'high' : 'low',
                width: 400,
                height: 300
            }) }}
        </div>
    {% endfor %}
</div>
```

### Product Images
```twig
<div class="product-image">
    {{ craft_picture(product.image.one(), {
        class: 'product-picture',
        loading: 'lazy',
        artDirection: {
            mobile: { width: 300, height: 300, mode: 'crop' },
            desktop: { width: 500, height: 500, mode: 'crop' }
        },
        sizes: [
            '(max-width: 768px) 50vw',
            '25vw'
        ]
    }) }}
</div>
```

### SVG Icons
```twig
{# Inline SVG | just inline true in settings #}
{{ craft_svg(iconAsset, { class: 'icon' }) }} 

{# SVG as img #}
{{ craft_svg(iconAsset, { width: 24, height: 24, alt: 'Icon' }) }}
```

### Image Optimization
- WebP and AVIF formats for modern browsers
- Configurable quality settings
- Smart srcset generation
- Lazy loading for non-critical images

### Caching
- Transform caching system
- Configurable cache duration
- Automatic cache invalidation

### Loading Performance
- Optimized lazy loading
- Reduced motion support

### Modern Browsers
- Full support for WebP and AVIF
- Intersection Observer for lazy loading
- Native picture element support

### Legacy Browsers
- Graceful fallbacks
- Traditional lazy loading
- JPEG/PNG fallbacks

### Features
- Required alt text enforcement
- Proper ARIA roles
- Keyboard navigation support
- Screen reader compatibility
- High contrast mode support

### Best Practices
- Always provide meaningful alt text
- Use appropriate loading priorities
- Consider reduced motion preferences
- Test with screen readers

### Debug Mode
Enable debug mode in plugin settings to see:
- Transform information
- Image dimensions
- Format support
- Performance metrics

### Debug Function
```twig
{% if craft.app.config.general.devMode %}
    {% set debugInfo = picture_debug(image, { width: 800 }) %}
    <pre>{{ dump(debugInfo) }}</pre>
{% endif %}
```

### Common Issues

**Images not loading:**
- Check asset permissions
- Verify image transforms are enabled
- Check cache settings

**WebP/AVIF not generating:**
- Ensure image format supports conversion
- Check quality settings
- Verify plugin settings

**Lazy loading not working:**
- Check JavaScript is loading
- Verify CSS classes are correct
- Test in different browsers

### Support
- GitHub Issues: [https://github.com/taher-k5/picture-tag/issues](https://github.com/taher-k5/picture-tag/issues)
- Documentation: [https://github.com/taher-k5/picture-tag/wiki](https://github.com/taher-k5/picture-tag/wiki)

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Development Setup
```bash
git clone https://github.com/taher-k5/picture-tag.git
cd picture-tag
composer install
npm install
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Changelog

### Version 1.0.0
- **Initial release** of the minimal, high-performance Picture Tag plugin
- **WebP & AVIF support** with automatic `<source>` generation
- **Responsive `srcset`** using density multipliers (1x, 1.5x, 2x, 3x)
- **Native lazy loading** via `loading="lazy"` + customizable `data-placeholder`
- **SVG optimization & inline rendering** with configurable toggle
- **Caching layer** with `enableCache` and `cacheDuration` (in seconds)
- **Debug mode** (`enableDebug`) for development inspection
- **Twig functions**:
  - `craft_picture()` – Full `<picture>` tag with WebP/AVIF with fallbacks
  - `craft_img()` – Responsive `<img>` with `srcset`
  - `craft_svg()` – Inline or `<img>` SVG rendering
  - `craft_srcset()` – Generate `srcset` string manually
- **Project config support** – All settings saved to `config/picture-tag.php`
- **Craft CP Settings UI** – Clean, tabbed interface with validation
- **No JavaScript or CSS bloat** – Zero frontend assets by default


## [Unreleased] – Upcoming Features

> These features are **planned** but **not included** in the current version.

### Future Planned
- [ ] **Default transform system** with `enableDefaultTransforms` and full control over width, height, quality
- [ ] **Lightbox gallery** with swipe & keyboard support
- [ ] **Art direction** with per-breakpoint crops
- [ ] **Aspect ratio containers** (16:9, 1:1, etc.)
- [ ] **CSS utility classes** (`picture-responsive`, `picture-fit-cover`, etc.)
- [ ] **Shimmer placeholder animation**
- [ ] **Error state UI** for failed images
- [ ] **Preload & fetchpriority** controls
- [ ] **Focal point cropping**
- [ ] **Custom breakpoints & sizes**
- [ ] **JavaScript lazy loader** (for legacy browsers)

> These will be added in future **major versions** (e.g. `2.0.0`) as optional modules.

---


## Credits

- **Inspired by**:  Marion Newlevant (Picture) and Club Studio (Inline Svg)
- **Built for**: Craft CMS 5.0+
- **Developer**: Taher Kathiriya
- **Developer**: Taha Dudhiya
---

For more information and updates, visit the [project repository](https://github.com/taher-k5/picture-tag).