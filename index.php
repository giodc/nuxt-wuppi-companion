<?php
/**
 * Main template file
 *
 * This is a minimal template file as the theme is intended for headless use.
 *
 * @package Nuxt-Wuppi-Companion
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// If redirect is not enabled or not working, display minimal content
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site">
    <header class="site-header">
        <h1><?php bloginfo( 'name' ); ?></h1>
        <p><?php bloginfo( 'description' ); ?></p>
        
        <?php
        if ( has_nav_menu( 'header' ) ) {
            wp_nav_menu(
                array(
                    'theme_location' => 'header',
                    'menu_id'        => 'header-menu',
                )
            );
        }
        ?>
    </header>

    <main class="site-content">
        <p><?php esc_html_e( 'This site is configured for headless use.', 'nuxt-wuppi-companion' ); ?></p>
    </main>

    <footer class="site-footer">
        <?php
        if ( has_nav_menu( 'footer' ) ) {
            wp_nav_menu(
                array(
                    'theme_location' => 'footer',
                    'menu_id'        => 'footer-menu',
                )
            );
        }
        ?>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
