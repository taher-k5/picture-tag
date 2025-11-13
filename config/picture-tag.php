<?php

/**
 * Picture Tag Plugin – Minimal Configuration
 * 
 * Only the 13 settings you requested are kept.
 * Everything else is removed to keep the plugin lightweight.
 */

return [

    // 1. Default Transforms (used when enableDefaultTransforms = true)
    // 'defaultTransforms' => [
    //     'mobile' => [
    //         'width'   => 480,
    //         'height'  => 320,
    //         'quality' => 80,
    //     ],
    //     'tablet' => [
    //         'width'   => 768,
    //         'height'  => 512,
    //         'quality' => 85,
    //     ],
    //     'desktop' => [
    //         'width'   => 1024,
    //         'height'  => 683,
    //         'quality' => 90,
    //     ],
    //     'large' => [
    //         'width'   => 1440,
    //         'height'  => 960,
    //         'quality' => 95,
    //     ],
    // ],

    // 2. Enable Default Transforms
    'enableDefaultTransforms' => false,

    // 3. WebP Support
    'enableWebP' => true,

    // 4. AVIF Support
    'enableAvif' => false,

    // 5. WebP Quality
    'webpQuality' => 80,

    // 6. AVIF Quality
    'avifQuality' => 75,

    // 7. Lazy Loading
    'enableLazyLoading' => true,

    // 8. Lazy Placeholder (base64 1x1 SVG)
    'lazyPlaceholder' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9IiNmNWY1ZjUiLz48L3N2Zz4=',

    // 9. SVG Optimization
    'enableSvgOptimization' => true,

    // 10. Inline SVG
    'inlineSvg' => false,

    // 11. Cache
    'enableCache' => true,

    // 12. Cache Duration (seconds)
    'cacheDuration' => 86400, // 24 hours

    // 13. Debug Mode
    'enableDebug' => false,

];