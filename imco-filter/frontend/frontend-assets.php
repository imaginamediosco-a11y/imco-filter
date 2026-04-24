<?php
/**
 * Encola los assets del frontend (CSS unificado, JS).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Carga estilos y scripts para el frontal (tienda, categorías, shortcode).
 */
function imco_enqueue_frontend_assets()
{
    // No cargar en el admin.
    if (is_admin()) {
        return;
    }

    // Subimos versión a 1.0.5 para limpiar caché del navegador
    $version = '1.0.7';

    // CSS UNIFICADO (Escritorio + Móvil)
    wp_enqueue_style(
        'imco-filter-frontend',
        plugins_url('assets/css/frontend.css', IMCO_PLUGIN_FILE),
        [],
        $version
    );

    // Color del botón de filtro
    $button_color = get_option('imco_filter_button_color', '#e60000');
    $custom_css = "
        .imco-mobile-filter-button,
        .imco-collapsible-filter .imco-mobile-filter-button {
            background-color: {$button_color} !important;
        }
        .imco-collapsible-filter .imco-mobile-filter-button:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2) !important;
        }
    ";
    wp_add_inline_style('imco-filter-frontend', $custom_css);

    // JS frontend (AJAX + chips + panel móvil, etc.)
    wp_enqueue_script(
        'imco-filter-frontend',
        plugins_url('assets/js/frontend.js', IMCO_PLUGIN_FILE),
        ['jquery'],
        $version,
        true
    );

    // Objeto global imcoAjax que tu JS ya está usando
    wp_localize_script(
        'imco-filter-frontend',
        'imcoAjax',
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'pagination_mode' => get_option('imco_pagination_mode', 'pages'),
        ]
    );
}
add_action('wp_enqueue_scripts', 'imco_enqueue_frontend_assets');

// FIX DEFINITIVO PARA AJAX:
// Si wp_localize_script falla por culpa de plugins de caché/optimización (Autoptimize, LiteSpeed, etc),
// inyectamos la variable directamente en el <head> como respaldo indestructible.
function imco_inject_ajax_variable_head()
{
    if (is_admin())
        return;

    $ajax_url = admin_url('admin-ajax.php');
    $pagination_mode = get_option('imco_pagination_mode', 'pages');

    echo "<script type='text/javascript'>
        /* IMCO AJAX BACKUP */
        if (typeof imcoAjax === 'undefined') {
            var imcoAjax = {
                'ajax_url': '" . esc_url($ajax_url) . "',
                'pagination_mode': '" . esc_js($pagination_mode) . "'
            };
        }
    </script>\n";
}
add_action('wp_head', 'imco_inject_ajax_variable_head', 0);