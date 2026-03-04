<?php
/**
 * Encola los assets del frontend (CSS escritorio + móvil, JS).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Carga estilos y scripts para el frontal (tienda, categorías, shortcode).
 */
function imco_enqueue_frontend_assets() {

    // No cargar en el admin.
    if ( is_admin() ) {
        return;
    }

    // ⚠️ IMPORTANTE:
    // Cambié las versiones a 1.0.2 para forzar que WordPress
    // y cualquier caché usen los archivos ACTUALIZADOS.

    // CSS ESCRITORIO
    wp_enqueue_style(
        'imco-filter-frontend-desktop',
        plugins_url( 'assets/css/frontend-desktop.css', IMCO_PLUGIN_FILE ),
        [],
        '1.0.2' // <-- antes 1.0.0
    );

    // CSS MÓVIL (solo contiene @media max-width:768px)
    wp_enqueue_style(
        'imco-filter-frontend-mobile',
        plugins_url( 'assets/css/frontend-mobile.css', IMCO_PLUGIN_FILE ),
        [ 'imco-filter-frontend-desktop' ],
        '1.0.2' // <-- antes 1.0.0
    );

    // JS frontend (AJAX + chips + panel móvil, etc.)
    wp_enqueue_script(
        'imco-filter-frontend',
        plugins_url( 'assets/js/frontend.js', IMCO_PLUGIN_FILE ),
        [ 'jquery' ],
        '1.0.2', // <-- antes 1.0.0
        true
    );

    // Objeto global imcoAjax que tu JS ya está usando
    wp_localize_script(
        'imco-filter-frontend',
        'imcoAjax',
        [
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'pagination_mode' => get_option( 'imco_pagination_mode', 'pages' ),
        ]
    );
}
add_action( 'wp_enqueue_scripts', 'imco_enqueue_frontend_assets' );
