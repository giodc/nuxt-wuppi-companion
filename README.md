# Nuxt-Wuppi-Companion

A minimal WordPress theme designed for headless sites using WPGraphQL. This theme acts as a companion to headless frontend implementations, particularly those built with Nuxt.js.

## Description

Nuxt-Wuppi-Companion modifies WordPress behavior to work seamlessly in a headless setup. It provides essential functionality for redirecting frontend requests to your headless application while enhancing the WordPress REST API with additional data needed by your frontend.

### Key Features

- **Headless Mode**: Redirects frontend requests to your headless application
- **URL Rewriting**: Modifies permalinks to work with your frontend routing (adds `/page/` prefix)
- **Enhanced REST API**: Adds featured images data to REST API responses
- **Yoast SEO Integration**: Modifies sitemap URLs to use your frontend domain
- **Block Content Filtering**: Removes absolute URLs from block content
- **Comments Disabled**: Completely disables the WordPress comment system
- **Custom Image Sizes**: Provides multiple image sizes optimized for frontend use
- **Custom Subtitle Meta Field**: Adds a 'subtitle' field to posts for additional content

## Installation

1. Download or clone this repository to your WordPress themes directory:
   ```
   git clone https://github.com/giodc/nuxt-wuppi-companion.git
   ```

2. Activate the theme in the WordPress admin panel (Appearance > Themes)

3. Configure the theme settings in the WordPress Customizer (Appearance > Customize > Headless Redirect Settings)

## Configuration

### Headless Redirect Settings

In the WordPress Customizer, navigate to "Headless Redirect Settings" to configure:

- **Frontend URL**: Enter the URL of your frontend application (e.g., https://your-frontend-app.com). Leave empty to disable redirect.

## Usage

### REST API Enhancements

The theme enhances the WordPress REST API by adding featured image data to post responses. This data includes multiple image sizes and metadata.

### URL Structure

The theme modifies permalinks to add a `/page/` prefix to page URLs, making them compatible with common headless frontend routing patterns.

### Sitemap Integration

When using Yoast SEO, the theme automatically modifies sitemap URLs to use your frontend domain instead of the WordPress backend domain.

## Development

This theme is designed to be minimal and focused on headless functionality. It has no frontend styles as it's intended to be used with a separate frontend application.

### Requirements

- WordPress 5.0+
- WPGraphQL plugin (recommended)
- Yoast SEO plugin (for sitemap integration)

## License

GNU General Public License v2 or later - http://www.gnu.org/licenses/gpl-2.0.html
