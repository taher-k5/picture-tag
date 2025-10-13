<?php

/**
 * Picture Tag Plugin Configuration
 * 
 * You can override plugin settings by copying this file to your project's config folder
 * and modifying the values as needed.
 */

return [
    // Default responsive breakpoints
    'defaultBreakpoints' => [
        'mobile' => 480,
        'tablet' => 768,
        'desktop' => 1024,
        'large' => 1200,
    ],

    // Default image transforms for each breakpoint
    'defaultTransforms' => [
        'mobile' => [
            'width' => 480,
            'height' => 320,
            'quality' => 80,
            'mode' => 'crop'
        ],
        'tablet' => [
            'width' => 768,
            'height' => 512,
            'quality' => 85,
            'mode' => 'crop'
        ],
        'desktop' => [
            'width' => 1024,
            'height' => 683,
            'quality' => 90,
            'mode' => 'crop'
        ],
        'large' => [
            'width' => 1200,
            'height' => 800,
            'quality' => 95,
            'mode' => 'crop'
        ],
    ],

    // WebP and AVIF support
    'enableWebP' => true,
    'enableAvif' => false,
    'webpQuality' => 80,
    'avifQuality' => 75,

    // Lazy loading configuration
    'enableLazyLoading' => true,
    'lazyLoadingClass' => 'lazy',
    'lazyPlaceholder' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9IiNmNWY1ZjUiLz48L3N2Zz4=',

    // Performance settings
    'enablePreload' => false,
    'enableSizes' => true,
    'enableSrcset' => true,
    'enableFetchPriority' => true,

    // Accessibility settings
    'requireAltText' => true,
    'defaultAltText' => 'Image',

    // Advanced features
    'enableArtDirection' => true,
    'enableCropping' => true,
    'enableFocalPoint' => true,
    'enableAspectRatio' => true,

    // CSS and styling
    'includeDefaultStyles' => true,
    'defaultPictureClass' => 'picture-responsive',
    'defaultImageClass' => 'picture-img',

    // SVG handling
    'enableSvgOptimization' => true,
    'inlineSvg' => false,
    'svgMaxSize' => 1024, // bytes

    // Cache settings
    'enableCache' => true,
    'cacheDuration' => 86400, // 24 hours in seconds

    // Debug settings
    'enableDebug' => false,
    'showTransformInfo' => false,

    // Custom breakpoints for specific use cases
    'customBreakpoints' => [
        // Example: Hero images
        'hero' => [
            'mobile' => 480,
            'tablet' => 768,
            'desktop' => 1200,
            'large' => 1920,
        ],
        
        // Example: Gallery thumbnails
        'gallery' => [
            'mobile' => 150,
            'tablet' => 200,
            'desktop' => 250,
            'large' => 300,
        ],
        
        // Example: Product images
        'product' => [
            'mobile' => 300,
            'tablet' => 400,
            'desktop' => 500,
            'large' => 600,
        ],
    ],

    // Custom transforms for specific use cases
    'customTransforms' => [
        // Example: Hero images
        'hero' => [
            'mobile' => ['width' => 480, 'height' => 300, 'quality' => 85, 'mode' => 'crop'],
            'tablet' => ['width' => 768, 'height' => 400, 'quality' => 90, 'mode' => 'crop'],
            'desktop' => ['width' => 1200, 'height' => 600, 'quality' => 95, 'mode' => 'crop'],
            'large' => ['width' => 1920, 'height' => 800, 'quality' => 95, 'mode' => 'crop'],
        ],
        
        // Example: Gallery thumbnails
        'gallery' => [
            'mobile' => ['width' => 150, 'height' => 150, 'quality' => 80, 'mode' => 'crop'],
            'tablet' => ['width' => 200, 'height' => 200, 'quality' => 85, 'mode' => 'crop'],
            'desktop' => ['width' => 250, 'height' => 250, 'quality' => 90, 'mode' => 'crop'],
            'large' => ['width' => 300, 'height' => 300, 'quality' => 90, 'mode' => 'crop'],
        ],
        
        // Example: Product images
        'product' => [
            'mobile' => ['width' => 300, 'height' => 300, 'quality' => 85, 'mode' => 'crop'],
            'tablet' => ['width' => 400, 'height' => 400, 'quality' => 90, 'mode' => 'crop'],
            'desktop' => ['width' => 500, 'height' => 500, 'quality' => 95, 'mode' => 'crop'],
            'large' => ['width' => 600, 'height' => 600, 'quality' => 95, 'mode' => 'crop'],
        ],
    ],

    // Common size configurations
    'commonSizes' => [
        // Full width
        'full-width' => [
            '(max-width: 768px) 100vw',
            '100vw'
        ],
        
        // Half width
        'half-width' => [
            '(max-width: 768px) 100vw',
            '50vw'
        ],
        
        // Third width
        'third-width' => [
            '(max-width: 768px) 100vw',
            '(max-width: 1024px) 50vw',
            '33vw'
        ],
        
        // Quarter width
        'quarter-width' => [
            '(max-width: 768px) 50vw',
            '(max-width: 1024px) 33vw',
            '25vw'
        ],
        
        // Gallery grid
        'gallery-grid' => [
            '(max-width: 480px) 100vw',
            '(max-width: 768px) 50vw',
            '(max-width: 1024px) 33vw',
            '25vw'
        ],
    ],

    // Image format priorities
    'formatPriorities' => [
        'avif' => 1,
        'webp' => 2,
        'default' => 3,
    ],

    // Density multipliers for srcset generation
    'densityMultipliers' => [1, 1.5, 2, 3],

    // Maximum width for density variants
    'maxDensityWidth' => 2400,

    // Image quality presets
    'qualityPresets' => [
        'low' => 60,
        'medium' => 80,
        'high' => 90,
        'lossless' => 100,
    ],

    // Transform modes
    'transformModes' => [
        'crop' => 'crop',
        'fit' => 'fit',
        'stretch' => 'stretch',
    ],

    // Focal point positions
    'focalPointPositions' => [
        'top-left' => ['x' => 0, 'y' => 0],
        'top' => ['x' => 0.5, 'y' => 0],
        'top-right' => ['x' => 1, 'y' => 0],
        'left' => ['x' => 0, 'y' => 0.5],
        'center' => ['x' => 0.5, 'y' => 0.5],
        'right' => ['x' => 1, 'y' => 0.5],
        'bottom-left' => ['x' => 0, 'y' => 1],
        'bottom' => ['x' => 0.5, 'y' => 1],
        'bottom-right' => ['x' => 1, 'y' => 1],
    ],
];
