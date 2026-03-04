<?php
/**
 * Menús de administración de IMCO Filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registra el menú principal "IMCO Filter" y sus submenús.
 *
 * - IMCO Filter (Ajustes)
 * - Categorías de atributos
 */
function imco_register_admin_menu() {

    // Menú principal: IMCO Filter -> Ajustes generales.
    add_menu_page(
        __( 'IMCO Filter', 'imco-filter' ),   // Título de la página
        __( 'IMCO Filter', 'imco-filter' ),   // Título del menú
        'manage_options',                     // Capacidad requerida
        IMCO_PLUGIN_SLUG,                     // Slug de la página: 'imco-filter'
        'imco_render_main_admin_page',        // Callback (definido en settings-page.php)
        'dashicons-filter',                   // Icono
        58                                    // Posición
    );

    // Primer submenú: Ajustes (apunta a la misma página que el menú principal).
    add_submenu_page(
        IMCO_PLUGIN_SLUG,                     // Slug del menú padre
        __( 'Ajustes', 'imco-filter' ),       // Título de la página
        __( 'Ajustes', 'imco-filter' ),       // Título del submenú
        'manage_options',                     // Capacidad
        IMCO_PLUGIN_SLUG,                     // Slug: 'imco-filter'
        'imco_render_main_admin_page'         // Callback
    );

    // Segundo submenú: Categorías de atributos.
    add_submenu_page(
        IMCO_PLUGIN_SLUG,                          // Slug del menú padre
        __( 'Categorías de atributos', 'imco-filter' ), // Título de la página
        __( 'Categorías de atributos', 'imco-filter' ), // Título del submenú
        'manage_options',                          // Capacidad
        'imco-attribute-categories',               // Slug de la página
        'imco_render_attribute_categories_page'    // Callback (definido en attributes-config.php)
    );
}
add_action( 'admin_menu', 'imco_register_admin_menu' );
