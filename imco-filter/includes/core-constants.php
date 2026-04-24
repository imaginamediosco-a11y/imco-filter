<?php
/**
 * Constantes globales para IMCO Filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Versión del plugin.
 * Debe coincidir con el número del encabezado en imco-filter.php
 */
if ( ! defined( 'IMCO_PLUGIN_VERSION' ) ) {
    define( 'IMCO_PLUGIN_VERSION', '1.1.6' );
}

/**
 * Ruta absoluta al directorio del plugin.
 */
if ( ! defined( 'IMCO_PLUGIN_DIR' ) ) {
    define( 'IMCO_PLUGIN_DIR', plugin_dir_path( IMCO_PLUGIN_FILE ) );
}

/**
 * URL base del plugin.
 */
if ( ! defined( 'IMCO_PLUGIN_URL' ) ) {
    define( 'IMCO_PLUGIN_URL', plugin_dir_url( IMCO_PLUGIN_FILE ) );
}

/**
 * Slug base del plugin (para menús de admin, etc.).
 */
if ( ! defined( 'IMCO_PLUGIN_SLUG' ) ) {
    define( 'IMCO_PLUGIN_SLUG', 'imco-filter' );
}
