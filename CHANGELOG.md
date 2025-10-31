# Changelog

All notable changes to the **Picture Tag** plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2025-11-3

### Added
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

### Features
| Feature | Description |
| **Modern Image Formats** | WebP & AVIF via Craft transforms |
| **Responsive by Default** | Automatic `srcset` for 1x–3x densities |
| **Lazy Loading** | Native browser support + 1x1 SVG placeholder |
| **SVG Handling** | Optimized & inlined when enabled |
| **Performance First** | Cache + minimal output |
| **Developer Friendly** | Debug mode, config overrides, clean APIs |

### Technical Details
- Built for **Craft CMS 4.5+ / 5.0+**
- Requires **PHP 8.1+**
- **MIT License**
- PSR-4 autoloading
- Zero external dependencies
- No frontend JS/CSS included
- Fully compatible with `project.yaml`

---

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

## How to Upgrade

```bash
composer require taherkathiriya/craft-picturetag:^1.0
php craft plugin/install picture-tag