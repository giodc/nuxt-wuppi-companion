<?php
/**
 * Nuxt-Wuppi-Companion functions and definitions
 *
 * @package Nuxt-Wuppi-Companion
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Theme setup
 */
function nuxt_wuppi_companion_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title.
    add_theme_support( 'title-tag' );
    
    // Add support for block styles
    add_theme_support( 'wp-block-styles' );
    
    // Add support for editor styles
    add_theme_support( 'editor-styles' );
    
    // Enqueue editor styles
    add_editor_style( 'editor-style.css' );
    
    // Add support for responsive embeds
    add_theme_support( 'responsive-embeds' );
    
    // Add support for full and wide align images
    add_theme_support( 'align-wide' );
    
    // Add support for featured images
    add_theme_support( 'post-thumbnails' );
    
    // Add custom image sizes for featured images
    add_image_size( 'featured-small', 300, 200, true );
    add_image_size( 'featured-medium', 600, 400, true );
    add_image_size( 'featured-large', 1200, 800, true );

    // Register navigation menus
    register_nav_menus(
        array(
            'header' => esc_html__( 'Header Menu', 'nuxt-wuppi-companion' ),
            'footer' => esc_html__( 'Footer Menu', 'nuxt-wuppi-companion' ),
        )
    );
}
add_action( 'after_setup_theme', 'nuxt_wuppi_companion_setup' );

/**
 * Disable comments sitewide
 */
function nuxt_wuppi_disable_comments() {
    // Close comments on the front-end
    add_filter( 'comments_open', '__return_false', 20, 2 );
    add_filter( 'pings_open', '__return_false', 20, 2 );
    
    // Hide existing comments
    add_filter( 'comments_array', '__return_empty_array', 10, 2 );
    
    // Remove comments page in menu
    add_action( 'admin_menu', function() {
        remove_menu_page( 'edit-comments.php' );
    });
    
    // Remove comments links from admin bar
    add_action( 'init', function() {
        if ( is_admin_bar_showing() ) {
            remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
        }
    });
    
    // Disable comments support for all post types
    add_action( 'init', function() {
        $post_types = get_post_types();
        foreach ( $post_types as $post_type ) {
            if ( post_type_supports( $post_type, 'comments' ) ) {
                remove_post_type_support( $post_type, 'comments' );
                remove_post_type_support( $post_type, 'trackbacks' );
            }
        }
    });
    
    // Redirect any user trying to access comments page
    add_action( 'admin_init', function() {
        global $pagenow;
        
        if ( $pagenow === 'edit-comments.php' ) {
            wp_redirect( admin_url() );
            exit;
        }
    });
}
add_action( 'after_setup_theme', 'nuxt_wuppi_disable_comments' );

/**
 * Customizer additions.
 */
function nuxt_wuppi_companion_customize_register( $wp_customize ) {
    // Add section for redirect settings
    $wp_customize->add_section(
        'nuxt_wuppi_redirect_section',
        array(
            'title'       => __( 'Headless Redirect Settings', 'nuxt-wuppi-companion' ),
            'description' => __( 'Configure redirect settings for headless mode.', 'nuxt-wuppi-companion' ),
            'priority'    => 30,
        )
    );

    // Add setting for redirect URL
    $wp_customize->add_setting(
        'nuxt_wuppi_redirect_url',
        array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        )
    );

    // Add control for redirect URL
    $wp_customize->add_control(
        'nuxt_wuppi_redirect_url',
        array(
            'label'       => __( 'Frontend URL', 'nuxt-wuppi-companion' ),
            'description' => __( 'Enter the URL of your frontend application. Leave empty to disable redirect.', 'nuxt-wuppi-companion' ),
            'section'     => 'nuxt_wuppi_redirect_section',
            'type'        => 'url',
        )
    );
}
add_action( 'customize_register', 'nuxt_wuppi_companion_customize_register' );

/**
 * Handle redirect to frontend
 */
function nuxt_wuppi_maybe_redirect() {
    // Skip redirect for admin, login, ajax, rest, or graphql requests
    if (
        is_admin() ||
        wp_doing_ajax() ||
        wp_doing_cron() ||
        ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
        ( defined( 'GRAPHQL_REQUEST' ) && GRAPHQL_REQUEST ) ||
        strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ||
        strpos( $_SERVER['REQUEST_URI'], 'wp-json' ) !== false ||
        strpos( $_SERVER['REQUEST_URI'], 'graphql' ) !== false
    ) {
        return;
    }

    // Get the redirect URL from customizer
    $redirect_url = get_theme_mod( 'nuxt_wuppi_redirect_url' );

    // Only redirect if a URL is set
    if ( ! empty( $redirect_url ) ) {
        // Get current path
        $path = $_SERVER['REQUEST_URI'];
        
        // Add '/page/' prefix to all paths except for the homepage
        $clean_path = ltrim( $path, '/' );
        if ( !empty( $clean_path ) ) {
            // Only add the prefix if it's not already there
            if ( strpos( $clean_path, 'page/' ) !== 0 ) {
                $clean_path = 'page/' . $clean_path;
            }
        }
        
        // Redirect to frontend with modified path
        wp_redirect( trailingslashit( $redirect_url ) . $clean_path );
        exit;
    }
}
add_action( 'template_redirect', 'nuxt_wuppi_maybe_redirect' );

/**
 * Filter menu URLs to add /page/ prefix only to page links
 */
function nuxt_wuppi_modify_menu_urls( $items ) {
    foreach ( $items as $item ) {
        // Skip external links
        if ( strpos( $item->url, home_url() ) !== 0 ) {
            continue;
        }
        
        // Skip the home page
        if ( $item->url === home_url() || $item->url === trailingslashit( home_url() ) ) {
            continue;
        }
        
        // Skip if already has page prefix
        if ( strpos( $item->url, '/page/' ) !== false ) {
            continue;
        }
        
        // Skip category links
        if ( strpos( $item->url, '/category/' ) !== false ) {
            continue;
        }
        
        // Only add prefix to page links
        // Check if this is a page link by looking at the object type
        if ( $item->type === 'post_type' && isset( $item->object ) && $item->object === 'page' ) {
            // Get the path part of the URL
            $path = str_replace( home_url(), '', $item->url );
            $path = ltrim( $path, '/' );
            
            // Add the /page/ prefix
            if ( !empty( $path ) ) {
                $item->url = trailingslashit( home_url() ) . 'page/' . $path;
            }
        }
    }
    
    return $items;
}
add_filter( 'wp_nav_menu_objects', 'nuxt_wuppi_modify_menu_urls', 10, 1 );

/**
 * Filter nav menu item attributes to ensure page prefix is applied
 */
function nuxt_wuppi_nav_menu_link_attributes( $atts ) {
    if ( isset( $atts['href'] ) && strpos( $atts['href'], home_url() ) === 0 ) {
        // Skip the home page
        if ( $atts['href'] === home_url() || $atts['href'] === trailingslashit( home_url() ) ) {
            return $atts;
        }
        
        // Skip if already has page prefix
        if ( strpos( $atts['href'], '/page/' ) !== false ) {
            return $atts;
        }
        
        // Get the path part of the URL
        $path = str_replace( home_url(), '', $atts['href'] );
        $path = ltrim( $path, '/' );
        
        // Add the /page/ prefix
        if ( !empty( $path ) ) {
            $atts['href'] = trailingslashit( home_url() ) . 'page/' . $path;
        }
    }
    
    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'nuxt_wuppi_nav_menu_link_attributes', 10, 1 );

/**
 * Force /page/ prefix for all page permalinks
 *
 * @param string $permalink The permalink URL
 * @param object $post The post object
 * @return string Modified permalink URL
 */
function nuxt_wuppi_page_link( $permalink, $post = null ) {
    // Skip if not a valid permalink
    if ( empty( $permalink ) || is_admin() ) {
        return $permalink;
    }
    
    // If we have a post object, check if it's a page
    if ( $post && isset( $post->post_type ) && $post->post_type !== 'page' ) {
        return $permalink;
    }
    
    // Skip if already has page prefix
    if ( strpos( $permalink, '/page/' ) !== false ) {
        return $permalink;
    }
    
    // Skip for home page
    if ( $permalink === home_url() || $permalink === trailingslashit( home_url() ) ) {
        return $permalink;
    }
    
    // Skip category links
    if ( strpos( $permalink, '/category/' ) !== false ) {
        return $permalink;
    }
    
    // Get the path part of the URL
    $path = str_replace( home_url(), '', $permalink );
    $path = ltrim( $path, '/' );
    
    // Add the /page/ prefix
    if ( !empty( $path ) ) {
        return trailingslashit( home_url() ) . 'page/' . $path;
    }
    
    return $permalink;
}

// Apply to all permalink types that might contain pages
add_filter( 'page_link', 'nuxt_wuppi_page_link', 10, 2 );
add_filter( 'post_type_link', 'nuxt_wuppi_page_link', 10, 2 );

// Also add a general filter for all URLs
add_filter( 'the_permalink', 'nuxt_wuppi_force_page_prefix', 10, 1 );

/**
 * Force /page/ prefix for page permalinks in any context
 */
function nuxt_wuppi_force_page_prefix( $permalink ) {
    global $post;
    
    // Only apply to pages
    if ( !$post || $post->post_type !== 'page' ) {
        return $permalink;
    }
    
    // Skip if already has page prefix
    if ( strpos( $permalink, '/page/' ) !== false ) {
        return $permalink;
    }
    
    // Skip for home page
    if ( $permalink === home_url() || $permalink === trailingslashit( home_url() ) ) {
        return $permalink;
    }
    
    // Get the path part of the URL
    $path = str_replace( home_url(), '', $permalink );
    $path = ltrim( $path, '/' );
    
    // Add the /page/ prefix
    if ( !empty( $path ) ) {
        return trailingslashit( home_url() ) . 'page/' . $path;
    }
    
    return $permalink;
}

// We're not modifying category links as requested by the user
// The term_link filter has been removed

/**
 * Add featured image to REST API responses
 */
function nuxt_wuppi_add_featured_image_to_rest() {
    // Register featured image field for posts
    register_rest_field(
        'post',
        'featured_image',
        array(
            'get_callback'    => 'nuxt_wuppi_get_featured_image',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}
add_action('rest_api_init', 'nuxt_wuppi_add_featured_image_to_rest');

/**
 * Get featured image data for REST API
 *
 * @param array $post The post object
 * @return array Featured image data
 */
function nuxt_wuppi_get_featured_image($post) {
    if (!has_post_thumbnail($post['id'])) {
        return null;
    }
    
    $featured_id = get_post_thumbnail_id($post['id']);
    
    // Get image in various sizes
    $small = wp_get_attachment_image_src($featured_id, 'thumbnail');
    $medium = wp_get_attachment_image_src($featured_id, 'medium');
    $large = wp_get_attachment_image_src($featured_id, 'large');
    $full = wp_get_attachment_image_src($featured_id, 'full');
    
    // Get custom image sizes
    $featured_small = wp_get_attachment_image_src($featured_id, 'featured-small');
    $featured_medium = wp_get_attachment_image_src($featured_id, 'featured-medium');
    $featured_large = wp_get_attachment_image_src($featured_id, 'featured-large');
    
    // Get alt text
    $alt = get_post_meta($featured_id, '_wp_attachment_image_alt', true);
    
    return array(
        'id' => $featured_id,
        'alt' => $alt,
        'thumbnail' => $small ? $small[0] : '',
        'medium' => $medium ? $medium[0] : '',
        'large' => $large ? $large[0] : '',
        'full' => $full ? $full[0] : '',
        'featured_small' => $featured_small ? $featured_small[0] : '',
        'featured_medium' => $featured_medium ? $featured_medium[0] : '',
        'featured_large' => $featured_large ? $featured_large[0] : '',
        'sizes' => array(
            'thumbnail' => $small ? array('url' => $small[0], 'width' => $small[1], 'height' => $small[2]) : null,
            'medium' => $medium ? array('url' => $medium[0], 'width' => $medium[1], 'height' => $medium[2]) : null,
            'large' => $large ? array('url' => $large[0], 'width' => $large[1], 'height' => $large[2]) : null,
            'full' => $full ? array('url' => $full[0], 'width' => $full[1], 'height' => $full[2]) : null,
            'featured_small' => $featured_small ? array('url' => $featured_small[0], 'width' => $featured_small[1], 'height' => $featured_small[2]) : null,
            'featured_medium' => $featured_medium ? array('url' => $featured_medium[0], 'width' => $featured_medium[1], 'height' => $featured_medium[2]) : null,
            'featured_large' => $featured_large ? array('url' => $featured_large[0], 'width' => $featured_large[1], 'height' => $featured_large[2]) : null,
        ),
    );
}

/**
 * Filter blocks to remove absolute URLs
 */
add_filter( 'render_block', function( $block_content, $block ) {
	// Target specific blocks
	if ( isset( $block['blockName'] ) && in_array( $block['blockName'], [ 'core/page-list', 'core/latest-posts', 'core/tag-cloud', 'core/post-template', 'core/query' ] ) ) {
		$home_url = home_url();

		// Use DOMDocument to safely parse and rewrite URLs
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML('<?xml encoding="utf-8" ?>' . $block_content);

		$links = $dom->getElementsByTagName('a');
		foreach ( $links as $link ) {
			$href = $link->getAttribute('href');
			if ( strpos( $href, $home_url ) === 0 ) {
				$relative_url = str_replace( $home_url, '', $href );
				$link->setAttribute( 'href', $relative_url );
			}
		}

		$body = $dom->getElementsByTagName('body')->item(0);
		$block_content = '';
		foreach ( $body->childNodes as $child ) {
			$block_content .= $dom->saveHTML($child);
		}
	}
	return $block_content;
}, 10, 2 );

/**
 * Filter Yoast sitemap URLs to use headless frontend domain
 */
function modify_yoast_sitemap_urls($url, $type = null, $object = null) {
    // Your WordPress backend domain
    $wordpress_domain = get_site_url();
    $nuxt_wuppi_redirect_url = get_theme_mod( 'nuxt_wuppi_redirect_url' );
   
    if ( empty( $nuxt_wuppi_redirect_url ) ) {
        $frontend_domain = $wordpress_domain;
    } else {
        $frontend_domain = $nuxt_wuppi_redirect_url;
    }
    
    // Replace the WordPress domain with your frontend domain
    $modified_url = str_replace($wordpress_domain, $frontend_domain, $url);
    
    return $modified_url;
}

/**
 * Check if Yoast SEO is active and add filter
 */
if(in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins')))){ 
    add_filter('wpseo_sitemap_url', 'modify_yoast_sitemap_urls', 10, 2);
}

/**
 * Register custom meta field 'subtitle' for posts
 */
function nuxt_wuppi_register_post_meta() {
    register_post_meta('post', 'subtitle', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => 'sanitize_text_field',
    ]);
}
add_action('init', 'nuxt_wuppi_register_post_meta');

/**
 * Add subtitle field to REST API response
 */
function nuxt_wuppi_add_subtitle_to_rest() {
    register_rest_field('post', 'subtitle', [
        'get_callback' => function($post) {
            return get_post_meta($post['id'], 'subtitle', true);
        },
        'update_callback' => function($value, $post) {
            return update_post_meta($post->ID, 'subtitle', sanitize_text_field($value));
        },
        'schema' => [
            'description' => __('Post subtitle', 'nuxt-wuppi-companion'),
            'type' => 'string',
        ],
    ]);
}
add_action('rest_api_init', 'nuxt_wuppi_add_subtitle_to_rest');

/**
 * Register subtitle field with WPGraphQL
 */
function nuxt_wuppi_add_subtitle_to_graphql() {
    // Check if WPGraphQL is active
    if (!function_exists('register_graphql_field')) {
        return;
    }
    
    // Register the subtitle field for the Post type in GraphQL
    register_graphql_field('Post', 'subtitle', [
        'type' => 'String',
        'description' => __('The subtitle of the post', 'nuxt-wuppi-companion'),
        'resolve' => function($post) {
            $post_id = $post->databaseId;
            return get_post_meta($post_id, 'subtitle', true);
        }
    ]);
}
add_action('graphql_register_types', 'nuxt_wuppi_add_subtitle_to_graphql');

/**
 * Add subtitle meta box to post editor
 */
function nuxt_wuppi_add_subtitle_meta_box() {
    add_meta_box(
        'nuxt_wuppi_subtitle_meta_box',
        __('Post Subtitle', 'nuxt-wuppi-companion'),
        'nuxt_wuppi_subtitle_meta_box_callback',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'nuxt_wuppi_add_subtitle_meta_box');

/**
 * Render subtitle meta box content
 */
function nuxt_wuppi_subtitle_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('nuxt_wuppi_subtitle_meta_box', 'nuxt_wuppi_subtitle_meta_box_nonce');
    
    // Get current subtitle value
    $subtitle = get_post_meta($post->ID, 'subtitle', true);
    
    // Output field
    echo '<div style="padding: 5px 0;">';
    echo '<label for="nuxt_wuppi_subtitle" style="display: block; font-weight: bold; margin-bottom: 5px;">' . __('Enter a subtitle for this post', 'nuxt-wuppi-companion') . '</label>';
    echo '<input type="text" id="nuxt_wuppi_subtitle" name="nuxt_wuppi_subtitle" value="' . esc_attr($subtitle) . '" style="width: 100%; padding: 8px; font-size: 1.2em;" />';
    echo '</div>';
}

/**
 * Save subtitle meta box data
 */
function nuxt_wuppi_save_subtitle_meta_box($post_id) {
    // Check if nonce is set
    if (!isset($_POST['nuxt_wuppi_subtitle_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nuxt_wuppi_subtitle_meta_box_nonce'], 'nuxt_wuppi_subtitle_meta_box')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save subtitle
    if (isset($_POST['nuxt_wuppi_subtitle'])) {
        update_post_meta($post_id, 'subtitle', sanitize_text_field($_POST['nuxt_wuppi_subtitle']));
    }
}
add_action('save_post', 'nuxt_wuppi_save_subtitle_meta_box');


