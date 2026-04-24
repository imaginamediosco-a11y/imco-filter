<?php
/**
 * Manejador AJAX para el filtro de productos IMCO Filter.
 *
 * Este archivo responde a la acción AJAX "imco_filter_products"
 * y devuelve:
 *  - El HTML de los productos filtrados.
 *  - El HTML del bloque de filtros activos (chips).
 */

if (!defined('ABSPATH')) {
    exit; // Seguridad: no permitir acceso directo.
}

/**
 * Callback principal del AJAX de filtrado.
 *
 * JS debería llamar a:
 *   action = 'imco_filter_products'
 *   method = 'GET' o 'POST'
 *   data   = datos del formulario de filtros + paginación, etc.
 */
function imco_ajax_filter_products()
{

    // Asegurarnos de que las funciones de render existen.
    if (!function_exists('imco_render_products_grid')) {
        wp_send_json_error(
            array(
                'message' => 'Función imco_render_products_grid() no encontrada.',
            )
        );
    }

    // ---------------------------------------------------------------------------------
    // Normalizar los datos de la petición
    // ---------------------------------------------------------------------------------
    // En nuestro nuevo JS usamos GET para que la URL se pueda copiar/pegar, 
    // pero por si acaso llega por POST, unificamos todo en $_GET.
    // ---------------------------------------------------------------------------------
    $request_data = !empty($_GET) ? $_GET : (!empty($_POST) ? $_POST : array());

    if (!empty($request_data) && is_array($request_data)) {
        $data_copy = $request_data;
        unset($data_copy['action']);

        foreach ($data_copy as $key => $value) {
            $_GET[$key] = $value;
            $_REQUEST[$key] = $value;
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
    if (function_exists('imco_render_active_filters_block')) {
        imco_render_active_filters_block();
    }
    $filters_html = ob_get_clean();

    // ---------------------------------------------------------------------------------
    // Responder en formato JSON
    // ---------------------------------------------------------------------------------
    wp_send_json_success(
        array(
            'products_html' => $products_html,
            'filters_html' => $filters_html,
        )
    );
}

add_action('wp_ajax_imco_filter_products', 'imco_ajax_filter_products');
add_action('wp_ajax_nopriv_imco_filter_products', 'imco_ajax_filter_products');
