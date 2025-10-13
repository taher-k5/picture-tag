# Changelog

All notable changes to the Picture Tag plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-XX

### Added
- Initial release of Picture Tag plugin
- WebP and AVIF format support for modern image compression
- Responsive image generation with full `srcset` and `sizes` support
- Art direction support for different crops at different breakpoints
- Lazy loading functionality with Intersection Observer API
- SVG handling with inline rendering and optimization
- Comprehensive template functions: `picture()`, `img()`, `svg()`, `picture_options()`
- Fluent API for building picture options
- Configurable breakpoints and transforms
- Performance optimization features (preload, fetchpriority)
- Accessibility features (alt text enforcement, ARIA roles)
- JavaScript gallery functionality with lightbox
- CSS framework with responsive utilities
- Debug mode for development
- Caching system for image transforms
- Plugin settings interface in Craft CMS control panel
- Extensive documentation and examples

### Features
- **Image Formats**: WebP, AVIF, JPEG, PNG support
- **Responsive Design**: Automatic breakpoint handling
- **Performance**: Lazy loading, caching, preloading
- **Accessibility**: Screen reader support, keyboard navigation
- **Developer Experience**: Debug tools, comprehensive documentation
- **Flexibility**: Extensive configuration options
- **Browser Support**: Modern browsers with graceful fallbacks

### Technical Details
- Built for Craft CMS 5.0+
- PHP 8.2+ requirement
- MIT License
- PSR-4 autoloading
- Twig template integration
- JavaScript ES6+ features
- CSS Grid and Flexbox support
