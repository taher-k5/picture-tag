# Picture Tag Plugin for Craft CMS

A powerful and advanced Craft CMS plugin for handling responsive images with WebP and AVIF support, lazy loading, art direction, and much more.

## Features

### 🖼️ **Advanced Image Handling**
- **WebP & AVIF Support**: Automatically generates modern image formats for better compression
- **Responsive Images**: Full `srcset` and `sizes` attribute generation
- **Art Direction**: Different crops for different screen sizes
- **Lazy Loading**: Built-in lazy loading with intersection observer
- **SVG Support**: Inline SVG rendering or as img tags with optimization

### 🎨 **Flexible Template Functions**
- `picture_tag()` - Full responsive picture element
- `img_tag()` - Simple responsive img tag
- `svg_tag()` - SVG handling with inline or img tag options
- `picture_options()` - Fluent API for building options
- `responsive_srcset()` - Generate srcset strings
- `responsive_sizes()` - Generate sizes attributes

### ⚙️ **Comprehensive Configuration**
- Customizable breakpoints and transforms
- Quality settings for different formats
- Performance optimization options
- Accessibility features
- Debug and development tools

### 🚀 **Performance Features**
- Image caching system
- Preload hints for critical images
- Fetch priority attributes
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

## Quick Start

### Basic Usage

```twig
{# Simple picture tag #}
{{ picture_tag(image) }}

{# Picture with custom options #}
{{ picture_tag(image, {
    class: 'hero-image',
    loading: 'eager',
    alt: 'Hero image description'
}) }}

{# Simple image tag #}
{{ img_tag(image, { width: 800, height: 600 }) }}

{# SVG handling #}
{{ svg_tag(svgAsset, { inline: true }) }}
```

### Advanced Usage with Art Direction

<!-- ```twig
{{ picture_image, {
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
``` -->

### Using the Fluent API

<!-- ```twig
{% set options = picture_options()
    .pictureClass('gallery-image')
    .imageClass('gallery-img')
    .lazy()
    .quality(85)
    .webp()
    .avif()
    .transformFor('mobile', { width: 320, height: 240 })
    .transformFor('desktop', { width: 800, height: 600 })
%}
{{ picture(image, options.toArray()) }}
``` -->

## Configuration

### Plugin Settings

Access the plugin settings in the Craft CMS control panel under **Settings > Plugins > Picture Tag**.

#### Breakpoints
Configure responsive breakpoints:
- Mobile: 480px
- Tablet: 768px
- Desktop: 1024px
- Large: 1200px

#### Image Formats
- **WebP**: Enable/disable WebP generation
- **AVIF**: Enable/disable AVIF generation (newer browsers)
- **Quality Settings**: Configure quality for each format

#### Performance
- **Lazy Loading**: Enable/disable lazy loading
- **Preload**: Add preload hints for critical images
- **Cache**: Enable image transform caching
- **Fetch Priority**: Add fetchpriority attributes

#### Accessibility
- **Alt Text**: Require alt text for all images
- **Default Alt Text**: Fallback alt text

### Configuration File

Create a `config/picture-tag.php` file to override default settings:

```php
<?php
return [
    'enableWebP' => true,
    'enableAvif' => true,
    'webpQuality' => 85,
    'defaultBreakpoints' => [
        'mobile' => 480,
        'tablet' => 768,
        'desktop' => 1024,
        'large' => 1200,
    ],
    // ... more settings
];
```

## Template Functions

### `picture(image, options)`

Renders a complete `<picture>` element with responsive sources.

**Parameters:**
- `image` (Asset): The image asset
- `options` (array): Configuration options

**Options:**
```twig
{
    class: 'picture-class',           // CSS class for picture element
    imgClass: 'img-class',           // CSS class for img element
    loading: 'lazy',                 // 'lazy' or 'eager'
    alt: 'Alt text',                 // Override alt text
    quality: 85,                     // Image quality (1-100)
    enableWebP: true,                // Enable WebP generation
    enableAvif: true,                // Enable AVIF generation
    artDirection: {                  // Art direction for different breakpoints
        mobile: { width: 480, height: 320 },
        desktop: { width: 1024, height: 600 }
    },
    sizes: [                         // Custom sizes attribute
        '(max-width: 768px) 100vw',
        '50vw'
    ],
    breakpoints: {                   // Custom breakpoints
        mobile: 480,
        desktop: 1024
    },
    transforms: {                    // Custom transforms
        mobile: { width: 480, quality: 80 },
        desktop: { width: 1024, quality: 90 }
    }
}
```

### `img_tag(image, options)`

Renders a simple responsive `<img>` tag.

**Parameters:**
- `image` (Asset): The image asset
- `options` (array): Configuration options

### `svg(asset, options)`

Handles SVG assets with inline or image tag options.

**Parameters:**
- `asset` (Asset): The SVG asset
- `options` (array): Configuration options

**Options:**
```twig
{
    inline: true,                    // Inline SVG content
    class: 'svg-class',             // CSS class
    width: 24,                      // Width
    height: 24,                     // Height
    role: 'img'                     // ARIA role
}
```

### `picture_options()`

Creates a fluent API for building picture options.

**Methods:**
```twig
picture_options()
    .pictureClass('hero-picture')   // Set picture class
    .imageClass('hero-img')         // Set img class
    .lazy()                         // Enable lazy loading
    .eager()                        // Enable eager loading
    .quality(90)                    // Set quality
    .webp()                         // Enable WebP
    .avif()                         // Enable AVIF
    .alt('Alt text')                // Set alt text
    .title('Title')                 // Set title
    .dimensions(800, 600)           // Set width and height
    .transformFor('mobile', { width: 480 })  // Set transform for breakpoint
    .addSize('(max-width: 768px) 100vw')     // Add custom size
    .setAttribute('data-id', '123') // Set custom attribute
```

### `responsive_srcset(image, transform)`

Generates a responsive srcset string.

### `responsive_sizes(customSizes)`

Generates responsive sizes attribute.

### `picture_debug(image, transform)`

Returns debug information about image transforms (development only).

## Examples

### Hero Image
```twig
<section class="hero">
    {{ picture(entry.heroImage.one(), {
        class: 'hero-picture',
        loading: 'eager',
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
            {{ picture(image, {
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
    {{ picture(product.image.one(), {
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
{# Inline SVG #}
{{ svg(iconAsset, { inline: true, class: 'icon' }) }}

{# SVG as img #}
{{ svg(iconAsset, { width: 24, height: 24, alt: 'Icon' }) }}
```

## CSS Classes

The plugin includes default CSS classes for styling:

### Picture Element Classes
- `.picture-responsive` - Base responsive picture class
- `.picture-container` - Container with aspect ratio
- `.picture-aspect-ratio` - Aspect ratio container

### Image Classes
- `.picture-img` - Base image class
- `.lazy` - Lazy loading state
- `.loaded` - Loaded state

### Aspect Ratio Classes
- `.picture-aspect-ratio--1-1` - 1:1 aspect ratio
- `.picture-aspect-ratio--4-3` - 4:3 aspect ratio
- `.picture-aspect-ratio--16-9` - 16:9 aspect ratio

### Object Fit Classes
- `.picture-fit-cover` - object-fit: cover
- `.picture-fit-contain` - object-fit: contain
- `.picture-fit-fill` - object-fit: fill

## JavaScript Features

### Lazy Loading
The plugin includes JavaScript for advanced lazy loading:
- Intersection Observer API
- Fallback for older browsers
- Smooth fade-in animations
- Error handling

### Gallery Support
Built-in gallery functionality with lightbox:
- Click to enlarge
- Keyboard navigation
- Touch/swipe support
- Responsive design

### Usage
```javascript
// Initialize PictureTag (automatic)
// Or manually:
const pictureTag = new PictureTag({
    rootMargin: '50px 0px',
    threshold: 0.01,
    loadingClass: 'lazy',
    loadedClass: 'loaded'
});

// Initialize gallery
const gallery = new PictureGallery(document.querySelector('.gallery'));
```

## Performance Considerations

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
- Preload hints for critical images
- Fetch priority attributes
- Optimized lazy loading
- Reduced motion support

## Browser Support

### Modern Browsers
- Full support for WebP and AVIF
- Intersection Observer for lazy loading
- Native picture element support

### Legacy Browsers
- Graceful fallbacks
- Traditional lazy loading
- JPEG/PNG fallbacks

## Accessibility

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

## Development

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

## Troubleshooting

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
- Initial release
- WebP and AVIF support
- Responsive image generation
- Lazy loading functionality
- Art direction support
- SVG handling
- Comprehensive configuration options
- JavaScript gallery features
- Accessibility features
- Performance optimizations

## Credits

- **Developer**: Taher Kathiriya
- **Inspired by**: Picture Marion Newlevant plugin
- **Built for**: Craft CMS 5.0+

---

For more information and updates, visit the [project repository](https://github.com/taher-k5/picture-tag).