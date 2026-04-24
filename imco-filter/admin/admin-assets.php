<?php
/**
 * Assets del área de administración para IMCO Filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Encola estilos del admin solo en páginas relevantes:
 * - Páginas del plugin IMCO (ajustes, categorías, etc.).
 * - Pantalla de edición/creación de producto (post.php, post-new.php) para el post type "product".
 */
function imco_enqueue_admin_assets( $hook ) {

    $load = false;

    // 1) Cargar en pantallas propias del plugin (tienen "imco" en el hook).
    if ( false !== strpos( $hook, 'imco' ) ) {
        $load = true;
    }

    // 2) Cargar también en la edición/creación de productos (para el metabox).
    if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {

        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();

            if ( $screen && 'product' === $screen->post_type ) {
                $load = true;
            }
        }
    }

    if ( ! $load ) {
        return;
    }

    wp_enqueue_style(
        'imco-admin',
        IMCO_PLUGIN_URL . 'assets/css/admin.css',
        [],
        IMCO_PLUGIN_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'imco_enqueue_admin_assets' );
