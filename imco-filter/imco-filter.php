<?php
/**
 * Plugin Name: IMCO Filter
 * Description: Plugin para filtrar productos por atributos con layouts personalizables (horizontal / vertical) y administración propia.
 * Version: 2.2.1
 * Author: Tu Nombre
 * Text Domain: imco-filter
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('IMCO_PLUGIN_FILE')) {
    define('IMCO_PLUGIN_FILE', __FILE__);
}

require_once plugin_dir_path(__FILE__) . 'includes/core-constants.php';
require_once IMCO_PLUGIN_DIR . 'includes/core-loader.php';

function imco_init_plugin()
{
    imco_load_plugin_components();
}
add_action('plugins_loaded', 'imco_init_plugin');

/* ============================================================
 *  PÁGINA DE AJUSTES (VISUALIZACIÓN PRODUCTO)
 * ============================================================ */
// ELIMINADO: add_action('admin_menu', 'imco_register_general_settings_menu', 99);
// ELIMINADO: function imco_register_general_settings_menu() { ... }
// (Ya se registra correctamente en admin/admin-menu.php)

function imco_render_general_settings_page()
{
    if (isset($_POST['imco_settings_nonce']) && wp_verify_nonce($_POST['imco_settings_nonce'], 'imco_save_settings')) {
        $show_specs = isset($_POST['imco_auto_insert_specs']) ? 'yes' : 'no'; // CORREGIDO EL NAME
        $position = isset($_POST['imco_specs_position']) ? sanitize_text_field($_POST['imco_specs_position']) : 'after_summary'; // CORREGIDO EL DEFAULT
        $override = isset($_POST['imco_override_template']) ? 'yes' : 'no';

        update_option('imco_auto_insert_specs', $show_specs); // CORREGIDO EL OPTION NAME
        update_option('imco_specs_position', $position);
        update_option('imco_override_template', $override);

        echo '<div class="notice notice-success is-dismissible"><p>Ajustes guardados correctamente.</p></div>';
    }

    $show_specs = get_option('imco_auto_insert_specs', 'no'); // CORREGIDO EL OPTION NAME
    $position = get_option('imco_specs_position', 'after_summary'); // CORREGIDO EL DEFAULT
    $override = get_option('imco_override_template', 'no');
    ?>
    <style>
        .imco-modern-admin {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            max-width: 800px;
            margin-top: 20px;
            color: #1d1d1f;
        }

        .imco-modern-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 32px;
            border: 1px solid #f2f2f7;
            margin-bottom: 24px;
        }

        .imco-modern-admin h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .imco-modern-admin h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 24px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e5ea;
        }

        .imco-modern-admin select {
            border-radius: 8px;
            border: 1px solid #d1d1d6;
            padding: 8px 12px;
            background-color: #fbfbfd;
            width: 100%;
            max-width: 300px;
        }

        .imco-modern-admin .button-primary {
            background: #007aff !important;
            border-color: #007aff !important;
            border-radius: 8px !important;
            padding: 6px 20px !important;
            font-weight: 600 !important;
            color: white !important;
        }

        .imco-toggle-checkbox {
            transform: scale(1.2);
            margin-right: 10px;
        }
    </style>

    <div class="wrap imco-modern-admin">
        <h1>Visualización Producto - IMCO Filter</h1>

        <form method="post">
            <?php wp_nonce_field('imco_save_settings', 'imco_settings_nonce'); ?>

            <div class="imco-modern-card" style="border-left: 4px solid #007aff;">
                <h2>Diseño Apple-Style (Recomendado)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Sobrescribir Tema</th>
                        <td>
                            <label>
                                <input type="checkbox" name="imco_override_template" class="imco-toggle-checkbox"
                                    value="yes" <?php checked($override, 'yes'); ?> />
                                <strong>Activar la Plantilla Moderna de Producto IMCO</strong>
                            </label>
                            <p class="description">Al activar esto, el plugin ignorará el diseño de Astra/Elementor y
                                mostrará un diseño limpio, centrado, con la foto, precio y un acordeón moderno para la
                                descripción y especificaciones.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="imco-modern-card">
                <h2>Diseño Clásico (Solo inyectar tabla)</h2>
                <p class="description" style="margin-bottom: 16px;">Si NO activaste la opción de arriba, puedes usar estas
                    opciones para inyectar solo la tabla en tu tema actual.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row">Tabla de Especificaciones</th>
                        <td>
                            <label>
                                <input type="checkbox" name="imco_auto_insert_specs" class="imco-toggle-checkbox"
                                    value="yes" <?php checked($show_specs, 'yes'); ?> />
                                Insertar automáticamente la tabla en la descripción.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Posición de la Tabla</th>
                        <td>
                            <select name="imco_specs_position">
                                <option value="before_summary" <?php selected($position, 'before_summary'); ?>>Antes de la
                                    descripción larga</option>
                                <option value="after_summary" <?php selected($position, 'after_summary'); ?>>Después de la
                                    descripción larga</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary">Guardar Ajustes</button>
            </p>
        </form>
    </div>
    <?php
}

/* ============================================================
 *  SECUESTRO DE PLANTILLA (TEMPLATE OVERRIDE)
 * ============================================================ */
add_filter('template_include', 'imco_override_single_product_template', 99);

function imco_override_single_product_template($template)
{
    if (is_singular('product')) {
        $override = get_option('imco_override_template', 'no');
        if ($override === 'yes') {
            $custom_template = IMCO_PLUGIN_DIR . 'frontend/single-product-modern.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
    }
    return $template;
}

/* ============================================================
 *  INYECCIÓN AUTOMÁTICA DE LA TABLA (DISEÑO CLÁSICO)
 * ============================================================ */
// ELIMINADO: La inyección automática (imco_auto_inject_specs_table) ahora se maneja
// directamente en frontend/specs-table-shortcode.php usando los hooks de WooCommerce
// para que quede fuera de las pestañas de descripción.

/* ============================================================
 *  CARGAR CSS DE LA TABLA
 * ============================================================ */
function imco_specs_enqueue_specs_table_styles()
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    $base_url = defined('IMCO_PLUGIN_URL') ? IMCO_PLUGIN_URL : plugin_dir_url(IMCO_PLUGIN_FILE);

    wp_enqueue_style(
        'imco-specs-table',
        $base_url . 'assets/css/specs-table.css',
        array(),
        '1.1.0'
    );
}
add_action('wp_enqueue_scripts', 'imco_specs_enqueue_specs_table_styles');