<?php
/**
 * Manejador AJAX para el filtro de productos IMCO Filter.
 *
 * Este archivo responde a la acción AJAX "imco_filter_products"
 * y devuelve:
 *  - El HTML de los productos filtrados.
 *  - El HTML del bloque de filtros activos (chips).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Seguridad: no permitir acceso directo.
}

/**
 * Callback principal del AJAX de filtrado.
 *
 * JS debería llamar a:
 *   action = 'imco_filter_products'
 *   method = 'POST'
 *   data   = datos del formulario de filtros + paginación, etc.
 */
function imco_ajax_filter_products() {

    // Asegurarnos de que las funciones de render existen.
    if ( ! function_exists( 'imco_render_products_grid' ) ) {
        wp_send_json_error(
            array(
                'message' => 'Función imco_render_products_grid() no encontrada.',
            )
        );
    }

    // ---------------------------------------------------------------------------------
    // Normalizar los datos de la petición
    // ---------------------------------------------------------------------------------
    // Muchos códigos del frontend (y del propio plugin) leen directamente de $_GET
    // o $_REQUEST. Como en AJAX llega todo por POST, replicamos esos valores en
    // $_GET y $_REQUEST para que el resto del plugin funcione igual que en una
    // carga normal de página.
    // ---------------------------------------------------------------------------------
    if ( ! empty( $_POST ) && is_array( $_POST ) ) {

        // Quitamos el campo 'action' para no ensuciar la query.
        $post_copy = $_POST;
        unset( $post_copy['action'] );

        foreach ( $post_copy as $key => $value ) {
            // No pisar $_GET explícito si ya viene algo por querystring.
            if ( ! isset( $_GET[ $key ] ) ) {
                $_GET[ $key ] = $value;
            }

            $_REQUEST[ $key ] = $value;
        }
    }

    // ---------------------------------------------------------------------------------
    // Construir HTML de productos filtrados
    // ---------------------------------------------------------------------------------
    ob_start();
    imco_render_products_grid();
    $products_html = ob_get_clean();

    // ---------------------------------------------------------------------------------
    // Construir HTML del bloque de filtros activos (chips)
    // ---------------------------------------------------------------------------------
    ob_start();
    if ( function_exists( 'imco_render_active_filters_block' ) ) {
        imco_render_active_filters_block();
    }
    $filters_html = ob_get_clean();

    // ---------------------------------------------------------------------------------
    // Responder en formato JSON
    // ---------------------------------------------------------------------------------
    wp_send_json_success(
        array(
            'products_html' => $products_html,
            'filters_html'  => $filters_html,
        )
    );
}

add_action( 'wp_ajax_imco_filter_products', 'imco_ajax_filter_products' );
add_action( 'wp_ajax_nopriv_imco_filter_products', 'imco_ajax_filter_products' );
