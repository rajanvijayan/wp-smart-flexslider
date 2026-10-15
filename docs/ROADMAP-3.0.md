# SlideCraft 3.0: plan (slug `wp-smart-flexslider`)

## Context

WP Smart Flexslider 2.5 is live on WordPress.org: about 90 active installs, last updated in January 2018, tested up to WordPress 4.8.

**The plugin:**
- It bundles Bootstrap 3 (CSS, JS and Glyphicons) and jQuery. On themes that aren't Bootstrap 3 they clash, and they load on every page.
- The admin is a single meta box.
- The one effect is Bootstrap's carousel slide.

**The data:**
- One `flexslider` post per slider, with these post meta fields:
  - `slide_attachmenid`: an array of attachment IDs
  - `flex-slide-title`, `flex-slide-url`, `flex-slide-desc`: arrays parallel to the IDs
  - `nav_controls`, `pager_controls`, `show_title`, `show_description`, `show_slide_url`: Yes/No
  - `interval` in milliseconds
  - `hover_pass`: `hover` or `false`
- Sliders are placed with `[display_flexslider id="…"]`. The older 1.x code used `[display_flexslider]` with each `flexslider` post as one slide.

**Repo and release problems:**
- The GitHub repo (`master`) still holds the 1.x code, so SVN is the real source.
- The readme's `Stable tag: 4.3` doesn't exist in SVN, so WordPress.org falls back to `trunk`.

3.0 replaces the plugin entirely with a modern slider builder.

Decisions made:
- **Name:** **SlideCraft – Responsive Slider & Carousel**. The slug and text domain stay `wp-smart-flexslider`. No plugin on WordPress.org uses "SlideCraft".
- **Engine:** **Swiper** (MIT, 14.x), loading only the modules each slider needs.
- **Building and placing sliders:** a **slider library with a visual React builder**. A slider is placed with a **block**, the existing **shortcode** or a **PHP function**, and can be reused on many pages.
- **Free:** everything ships in the free WordPress.org plugin.
- **Requirements:** WordPress 6.4+, PHP 7.4+. The same baseline and tooling as BookPress.

Outcome: one plugin that covers what people want from a slider:
- hero banners
- product and post carousels
- testimonials
- logo strips
- galleries with thumbnails
- video slides

All of it with no jQuery and no Bootstrap, assets only on pages that show a slider, and accessible by default. Existing 2.5 sliders keep working after the update.

## Features

### Slide types
- **Image:** focal point, Ken Burns zoom, overlay colour or gradient, link.
- **Video:** YouTube, Vimeo or a self-hosted MP4/WebM, as the slide or as a background. Autoplay muted, plays only when visible.
- **Content:** background image, colour or gradient with **layers**.
- **Dynamic (post query):** posts, pages, custom post types or WooCommerce products, filtered by taxonomy, order, count and "featured only". Template fields map to layers: title, excerpt, image, price, button.
- **Testimonial:** quote, name, role, avatar, star rating.
- **Logo:** image with an optional link, greyscale until hover.

### Layers (content and dynamic slides)
- **Layer types:** heading, text, button (up to 2), image or badge, icon, countdown.
- **Position:** a 9-point anchor plus offsets per device; maximum width; alignment.
- **Animations:** in and out (fade, slide up/down/left/right, zoom, blur-in, typewriter for headings), with delay and duration, played when the slide becomes active.
- **Style:** theme.json palette colours, fonts (theme fonts plus a few bundled OFL fonts), size per device.

### Slider types and effects
- **Hero / full-width / full-screen:** height by viewport (vh), aspect ratio or fixed pixels.
- **Carousel:** slides per view and gap per breakpoint, centred, auto-width, loop, free mode, grid (rows).
- **Effects:** slide, fade, coverflow, cube, flip, cards, creative presets, parallax background.
- **Thumbnail gallery:** main slider plus a thumbnail strip, with lightbox and zoom.
- **Vertical** slider and a continuous **ticker** (marquee) for logos and news.

### Navigation and behaviour
- **Arrows:** 6 styles, inside or outside, shown on hover or always.
- **Pagination:** dots, dynamic dots, fraction, progress bar, thumbnails.
- **Autoplay:** delay, pause on hover or focus, a progress ring, and a **pause/play button** (required for accessibility).
- **Input:** keyboard, mouse wheel, swipe and drag (each can be turned off); start slide; random order.
- **Responsive:** breakpoints (mobile, tablet, desktop and custom) override slides per view, height, layer visibility and font sizes.

### Templates (starter gallery)
About 12 ready-made sliders, each with a thumbnail:
- Hero Fade
- Split Hero
- Product Showcase (WooCommerce)
- Blog Carousel
- Testimonials
- Logo Ticker
- Gallery + Thumbnails
- Fullscreen Video
- Coverflow Portfolio
- Cards Stack
- Before/After
- News Ticker

Picking a template creates a slider with demo content from the site's own media and posts where possible.

### Accessibility, performance and SEO
- **Accessibility (WCAG 2.2 AA):**
  - the ARIA carousel pattern: role and label on the region, `aria-roledescription="slide"`, "Slide N of M"
  - a visible pause/play button whenever autoplay is on
  - no autoplay motion when `prefers-reduced-motion` is set
  - focus stays sensible when slides change
  - the builder warns about missing alt text
- **Performance:**
  - no jQuery or Bootstrap
  - CSS and JS load only on pages with a slider, found by `has_block()` or when the shortcode renders
  - Swiper modules are loaded per slider (ES module chunks)
  - the first slide's image loads eagerly with `fetchpriority="high"` for LCP; the rest are lazy
  - responsive `srcset`/`sizes` from WordPress image sizes
  - CSS custom properties instead of inline styles
  - target: under 30 KB gzipped of JS for a simple fade slider
- **SEO:**
  - slide text is real, crawlable HTML
  - optional `ImageGallery`/`ItemList` schema for gallery and post sliders
  - no layout shift: aspect-ratio boxes are reserved before Swiper starts

### Builder (the admin experience)
A top-level **SlideCraft** menu opens a React app, with the same stack as BookPress: `@wordpress/scripts` 27 and `@wordpress/components`.

1. **Slider library:** a card per slider with a live thumbnail, slide count, the shortcode with a copy button, and where it's used (posts that contain its block). Actions: Duplicate, Export, Delete; **New slider**, which opens the template gallery; **Import** (JSON).
2. **Editor:**
   - **Left:** the slide list. Drag to reorder, add a slide of any type, duplicate, hide.
   - **Centre:** a **live canvas**, the real front-end renderer in an iframe.
     - device toggles: desktop, tablet, mobile
     - select layers on the canvas and drag them to position
     - play or pause the preview
   - **Right:** an inspector with tabs **Slide · Layers · Style · Slider settings · Responsive**.
   - **History:** undo and redo, autosave with a "Saved" indicator, and revision history (the last 10 versions).
3. **Media:** the WordPress media library with multi-select to create many image slides at once, focal-point picker and alt-text editing in place.

### Embedding
- **Block `slidecraft/slider`:**
  - choose a slider from the library, with a live preview in the editor
  - per-placement settings: alignment, height and autoplay
  - "Create new slider" opens the builder
- **Shortcodes:**
  - `[slidecraft id="…"]`
  - the legacy `[display_flexslider id="…"]` and id-less `[display_flexslider]` are kept and mapped to the migrated sliders
- **PHP:** `slidecraft_slider( $id, $overrides )`
- **Widget:** the block in widget areas, plus a classic widget.
- **REST API** `slidecraft/v1`:
  - sliders: CRUD, duplicate, import/export
  - preview
  - post-query sample data for dynamic slides

## Migration from 2.5 (and 1.x)

- On activation and in admin, a **migration notice**: "Your old sliders were moved to SlideCraft".
- Each `flexslider` post becomes a SlideCraft slider:
  - each attachment ID becomes an image slide, with its title, description and URL turned into heading, text and link layers
  - `interval` → autoplay delay
  - `hover_pass` → pause on hover
  - `nav_controls` → arrows
  - `pager_controls` → dots
  - `show_*` → layer visibility
- The 1.x shape (one `flexslider` post per slide, no `id`) becomes one slider named "Legacy slider", built from those posts' featured images, titles and content.
- **Old data stays put** and is never deleted on update. A "Restore" link keeps the old meta untouched for one version.
- Legacy shortcodes render the migrated slider. A legacy-ID→new-ID map is kept in an option.
- Migration runs again safely: it is idempotent, and the new slider stores `_slidecraft_legacy_id`.

## Architecture (namespace `SlideCraft\`, classmap, `class-*.php`)

```
wp-smart-flexslider.php       bootstrap: header (Plugin Name: SlideCraft – Responsive Slider & Carousel), constants, autoload
src/class-plugin.php          wires services; activation (caps, migration), deactivation
src/sliders/                  class-slider-post-type.php (CPT slidecraft_slider, show_ui=false, show_in_rest=false)
                              class-slider-schema.php (versioned JSON: settings, breakpoints, slides[], layers[]; defaults, sanitize, migrate)
                              class-slider-repository.php (config in post meta _slidecraft_config, revisions, duplicate, import/export)
src/render/                   class-renderer.php (slider JSON -> semantic HTML + CSS vars + data-config)
                              class-assets.php (conditional enqueue, module list per slider, preload first image)
                              class-dynamic-source.php (post/product queries -> slide data, cached with transients)
                              class-schema-org.php (optional JSON-LD)
src/rest/                     sliders, preview, sources (post types, taxonomies, products), media helpers
src/blocks/slider/            block.json, render.php (server-rendered; uses Renderer)
src/legacy/                   class-migrator.php (2.5 and 1.x), class-legacy-shortcodes.php
src/admin/                    class-admin-app.php (menu, mounts React app)
assets/src/admin/             React builder (library, editor, canvas, inspector, templates)
assets/src/frontend/          slider runtime: Swiper init, layer animations, video, a11y controls, reduced motion
templates/                    starter slider JSON + thumbnails; overridable markup partials (theme: slidecraft/)
```

- **Swiper** comes from npm (`swiper@14`, pinned) and is bundled by `@wordpress/scripts` as ES module chunks. Each effect or module is its own chunk, loaded with a dynamic `import()` for the modules a slider uses.
- **Front-end runtime:** a small custom element `<slidecraft-slider>` reads the `data-config` JSON. It works with Swiper's module loading, layer animations (CSS classes plus `@keyframes`), video control (IntersectionObserver), the pause button and reduced motion.
- **Data:** the slider is one JSON document. Slides and layers have stable IDs so the builder can undo and edit safely. `schema_version` drives migrations.
- **Security:**
  - the `slidecraft_manage` capability, mapped to `edit_posts` and changeable by filter
  - every text and HTML field is sanitised with `wp_kses_post` on save and escaped on render
  - URLs are checked with `esc_url_raw`
  - dynamic queries respect `read_post`

## Repository, CI and release

The plan **reuses the BookPress setup**:
- **Repository files:**
  - a project rules file with the same rules as BookPress
  - `.distignore`, `composer.json` (PHPCS: WPCS plus PHPCompatibilityWP), `package.json`
  - `phpunit.xml.dist`, `bin/install-wp-tests.sh`
- **Workflows:**
  - `ci.yml`: PHPCS, ESLint, build, the shipped-PHP lint, PHPUnit (PHP 7.4 with WordPress 6.4, and PHP 8.3 with the latest WordPress), Plugin Check
  - `deploy.yml`: tag → SVN
  - `assets.yml`: readme and asset updates
- **Branches:** work happens on `release/3.0`, branched from `master`, with one PR per milestone. It merges to `master` at release by **pushing the tag first, then merging**, as in RELEASING.md. `master` is the default branch.
- **One-time setup:**
  - add the `SVN_USERNAME` and `SVN_PASSWORD` secrets to this repo
  - fix the readme's Stable tag; 3.0 sets `Stable tag: 3.0.0`
- **WordPress.org assets:**
  - new banners and icon with the SlideCraft name
  - 6–8 screenshots: library, template gallery, editor canvas, layers, responsive view, block in the editor, front-end examples
  - a new walkthrough video
  - the readme rewritten for search: tags `slider, carousel, hero slider, responsive slider, slideshow`

## Milestones (about 11 weeks for one developer)

| # | Scope | Estimate |
|---|---|---|
| M0 | Repo setup: project rules, tooling, CI, deploy pipeline, secrets; import the live 2.5 code as the baseline commit so history matches SVN | 0.5 w |
| M1 | Foundations: bootstrap, slider CPT, schema, repository, REST, capability, migrator for 2.5 and 1.x, legacy shortcodes | 1.5 w |
| M2 | Front-end: renderer, Swiper runtime with module loading, image, content and video slides, layers and animations, navigation, autoplay with pause, responsive, accessibility, conditional assets | 2 w |
| M3 | Builder: library, editor, live canvas, slide list, inspector tabs, media integration, autosave, undo/redo, revisions | 3 w |
| M4 | Dynamic and special slides: post and product sources, testimonials, logos and ticker, gallery with thumbnails and lightbox | 1.5 w |
| M5 | Block and embedding: block with preview, shortcode, PHP function, widget; templates gallery (12 starters, thumbnails) | 1 w |
| M6 | Release: readme, assets, screenshots, video, PHPUnit and Playwright, performance budget, accessibility audit, RC on a 2.5 site, 3.0.0 | 1.5 w |

## Verification

- **PHPUnit:**
  - schema defaults, sanitising and migration
  - **migrator fixtures from real 2.5 meta and the 1.x shape**, checking slide count, order, titles, links and settings
  - legacy shortcodes render the migrated slider
  - permissions: a subscriber is refused; private posts never appear in dynamic slides
  - the renderer escapes all user content
- **Playwright:**
  - create a slider from a template
  - add image slides from the media library
  - add a heading and button layer and drag it on the canvas
  - switch to mobile, save, and insert the block into a page
  - on the front end: the slider runs, the arrows, dots and keyboard work, and the pause button stops autoplay
  - with emulated reduced motion, there's no autoplay
- **Upgrade test:**
  - install 2.5 from WordPress.org on a test site and create sliders with the old admin
  - update to the 3.0 build
  - the old shortcodes still show the same images and links; no PHP notices
- **Performance:** on a page with one fade slider, Lighthouse reports no jQuery or Bootstrap requests, the JS budget is met, CLS is 0, and the first image is the LCP element with `fetchpriority=high`.
- **Accessibility:** axe-core in Playwright reports no serious violations on every starter template.
- **Release gates:**
  - PHPCS, Plugin Check and `composer audit` are clean
  - npm audit shows no high-severity issues in the shipped code
  - the shipped-PHP lint passes
