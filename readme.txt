=== SlideCraft – Responsive Slider & Carousel ===
Contributors: rajanit2000
Tags: slider, carousel, slideshow, responsive slider, image slider
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast, accessible sliders and carousels: hero banners, post and product carousels, galleries, testimonials and logo tickers.

== Description ==

SlideCraft builds sliders and carousels that look good, load fast and work for everyone. Make a slider once and place it anywhere with the **SlideCraft Slider** block, the `[slidecraft]` shortcode or a PHP function.

SlideCraft is the new version of **WP Smart Flexslider**. Your existing sliders are moved over automatically and your old shortcodes keep working.

= Slider types and effects =

* Slider, carousel (several slides per view, rows, centred mode, free scrolling), gallery with thumbnails, and continuous ticker.
* Slide, fade, coverflow, cube, flip, cards and creative transitions.
* Horizontal or vertical, loop, random order, autoplay with a progress bar, keyboard, mousewheel and touch.

= Slides and layers =

* Image, video (YouTube, Vimeo or MP4), content, testimonial, logo and dynamic posts slides.
* Image, colour, gradient or video backgrounds with a focal point, Ken Burns zoom and a colour overlay.
* Heading, text, button, image and countdown layers on a nine-point grid, with per-device visibility and entrance animations.
* Dynamic slides from posts, pages, custom post types or WooCommerce products, filtered by category, tag, featured or sticky.

= Visual builder =

* A slider library with search, duplicate, export and import.
* A live preview that uses your real site: click a layer to edit it, drag it to move it, and check desktop, tablet and mobile.
* Drag-and-drop slides and layers, autosave, undo/redo and saved versions you can restore.

= Design =

* 12 starter templates: Hero Fade, Split Hero, Promo Countdown, Fullscreen Video, Blog Carousel, Product Showcase, Coverflow Portfolio, Cards Stack, Testimonials, Logo Ticker, News Ticker and Gallery + Thumbnails.
* Height by aspect ratio, fixed pixels, viewport height or the first image.
* Six arrow styles, bullets, fraction, progress bar or thumbnails.
* Separate tablet and mobile settings for slides per view, gap, height, text size and arrows.

= Fast and accessible =

* About 32 KB of JavaScript (gzipped). Effects load only where they are used, and nothing loads on pages without a slider.
* Space is reserved before images load, so the page doesn't jump. The first image loads with high priority and the rest lazy-load with responsive sizes.
* Accessible carousel markup, a pause button whenever autoplay is on, pause on focus, keyboard support and reduced-motion support.
* YouTube (no-cookie) and Vimeo (do not track) videos load only when their slide is shown.

== Installation ==

1. In WordPress, go to **Plugins → Add New**, search for **SlideCraft**, then install and activate it.
2. Open **SlideCraft** in the admin menu and create a slider, or start from a template.
3. Add the **SlideCraft Slider** block to a page, or paste the slider's shortcode, e.g. `[slidecraft id="12"]`.

== Frequently Asked Questions ==

= I used WP Smart Flexslider. What happens to my sliders? =

When you update, each old slider is copied into SlideCraft with its images, titles, descriptions, links and settings. `[display_flexslider id="…"]` and `[display_flexslider]` keep working and show the new slider, so you don't need to edit your pages. The old slider data is left untouched.

= How do I add a slider with PHP? =

Use `slidecraft_slider( 12 );` in your theme. Pass `false` as the third argument to get the HTML instead of printing it.

= Which shortcode options are there? =

`[slidecraft id="12" align="full" autoplay="off" height="480" class="my-slider"]`. All options except `id` are optional and override the slider's own settings.

= Does it slow down my site? =

No. The script and styles load only on pages with a slider, extra effects load only when a slider uses them, and images are lazy-loaded with responsive sizes.

= Is it accessible? =

Sliders use the accessible carousel pattern, can be used with a keyboard, have a pause button whenever they play automatically, pause while focused and respect the "reduce motion" setting.

== Screenshots ==

1. The builder: slide list, live preview and settings. Click a layer to edit it, or drag it to move it.
2. Start from a blank slider or one of 12 templates.
3. Your slider library, with the shortcode for each slider.
4. A full-width hero slider with a fade transition.
5. Coverflow carousel for portfolios and albums.
6. Gallery with thumbnails and a lightbox.
7. Blog carousel built from your latest posts.
8. Testimonials with photo, name and star rating.

== Changelog ==

= 3.0.0 =
* New: rebuilt from scratch as SlideCraft, powered by Swiper.
* New: carousel, gallery and ticker layouts with slide, fade, coverflow, cube, flip, cards and creative effects.
* New: video, content, testimonial, logo and dynamic posts/products slides.
* New: text, button, image and countdown layers with entrance animations.
* New: 12 starter templates.
* New: visual builder with a live preview, drag-and-drop slides and layers, undo/redo, autosave and versions.
* New: SlideCraft Slider block, `[slidecraft]` shortcode and `slidecraft_slider()` function.
* New: tablet and mobile settings, lazy loading and accessible markup.
* Changed: requires WordPress 6.4 and PHP 7.4.
* Existing sliders and `[display_flexslider]` shortcodes are migrated automatically.

= 2.5 =
* Previous WP Smart Flexslider release.

== Upgrade Notice ==

= 3.0.0 =
WP Smart Flexslider is now SlideCraft. Your sliders and shortcodes are migrated automatically. Requires WordPress 6.4 and PHP 7.4.
