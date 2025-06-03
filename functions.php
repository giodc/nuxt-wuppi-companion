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
