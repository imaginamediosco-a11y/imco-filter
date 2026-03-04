<?php
/**
 * Loader del plugin IMCO Filter.
 * Carga todos los componentes (admin + frontend).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Carga los componentes principales del plugin.
 * Esta función se llama desde imco-filter.php.
 */
function imco_load_plugin_components() {

    // Núcleo: licencia + gestor de actualización.
    require_once IMCO_PLUGIN_DIR . 'includes/license-manager.php';
    require_once IMCO_PLUGIN_DIR . 'includes/upgrade-manager.php';

    // Administración.
    require_once IMCO_PLUGIN_DIR . 'admin/settings-page.php';
    require_once IMCO_PLUGIN_DIR . 'admin/admin-menu.php';
    require_once IMCO_PLUGIN_DIR . 'admin/product-metabox-basic.php';
    require_once IMCO_PLUGIN_DIR . 'admin/attributes-config.php';
    require_once IMCO_PLUGIN_DIR . 'admin/admin-assets.php';

    // Frontend.
    require_once IMCO_PLUGIN_DIR . 'frontend/template-render.php';
    require_once IMCO_PLUGIN_DIR . 'frontend/shortcode-filter.php';
    require_once IMCO_PLUGIN_DIR . 'frontend/frontend-assets.php';
    require_once IMCO_PLUGIN_DIR . 'frontend/ajax-handler.php';
    require_once IMCO_PLUGIN_DIR . 'frontend/woo-archive-integration.php';
}
