<?php
/**
 * Página de ajustes del plugin IMCO Filter.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra las opciones del plugin.
 */
function imco_register_settings()
{

    // Clave de licencia.
    register_setting(
        'imco_filter_settings_group',
        'imco_license_key',
        [
            'type' => 'string',
            'sanitize_callback' => function ($value) {
                $value = trim((string) $value);
                return sanitize_text_field($value);
            },
            'default' => '',
        ]
    );

    // Diseño del filtro (horizontal / vertical).
    register_setting(
        'imco_filter_settings_group',
        'imco_filter_layout',
        [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'horizontal',
        ]
    );

    // Número de productos por fila.
    register_setting(
        'imco_filter_settings_group',
        'imco_products_per_row',
        [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 4,
        ]
    );

    // Número de productos por página.
    register_setting(
        'imco_filter_settings_group',
        'imco_products_per_page',
        [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 12,
        ]
    );

    // NUEVO: columnas de categorías de atributos (layout horizontal).
    register_setting(
        'imco_filter_settings_group',
        'imco_attribute_group_columns',
        [
            'type' => 'integer',
            'sanitize_callback' => function ($value) {
                $v = absint($value);
                if ($v < 1) {
                    $v = 1;
                }
                if ($v > 6) {
                    $v = 6;
                }
                return $v;
            },
            'default' => 4,
        ]
    );

    // NUEVO: columnas de variaciones/opciones de atributos.
    register_setting(
        'imco_filter_settings_group',
        'imco_attribute_options_columns',
        [
            'type' => 'integer',
            'sanitize_callback' => function ($value) {
                $v = absint($value);
                if ($v < 1) {
                    $v = 1;
                }
                if ($v > 6) {
                    $v = 6;
                }
                return $v;
            },
            'default' => 4,
        ]
    );

    // Tipo de paginación (pages | load_more).
    register_setting(
        'imco_filter_settings_group',
        'imco_pagination_mode',
        [
            'type' => 'string',
            'sanitize_callback' => function ($value) {
                $allowed = ['pages', 'load_more'];
                return in_array($value, $allowed, true) ? $value : 'pages';
            },
            'default' => 'pages',
        ]
    );

    // Activar filtro en archivos de categorías de WooCommerce (product_cat).
    register_setting(
        'imco_filter_settings_group',
        'imco_enable_category_archive',
        [
            'type' => 'string',
            'sanitize_callback' => function ($value) {
                return ('yes' === $value) ? 'yes' : 'no';
            },
            'default' => 'no',
        ]
    );

    // Ocultar loop nativo de WooCommerce cuando IMCO está activo.
    register_setting(
        'imco_filter_settings_group',
        'imco_hide_woo_category_loop',
        [
            'type' => 'string',
            'sanitize_callback' => function ($value) {
                return ('yes' === $value) ? 'yes' : 'no';
            },
            'default' => 'no',
        ]
    );
}
add_action('admin_init', 'imco_register_settings');

/**
 * Renderiza la página de ajustes (contenido principal).
 */
function imco_render_settings_page()
{

    // Valores actuales.
    $license_key = get_option('imco_license_key', '');
    $layout = get_option('imco_filter_layout', 'horizontal');
    $columns = (int) get_option('imco_products_per_row', 4);
    $per_page = (int) get_option('imco_products_per_page', 12);
    $group_cols = (int) get_option('imco_attribute_group_columns', 4);
    $option_cols = (int) get_option('imco_attribute_options_columns', 4);
    $pagination = get_option('imco_pagination_mode', 'pages');
    $enable_arch = get_option('imco_enable_category_archive', 'no');
    $hide_wooLoop = get_option('imco_hide_woo_category_loop', 'no');

    // Estado de licencia.
    if (function_exists('imco_get_license_status')) {
        $license_status = imco_get_license_status();
    } else {
        $license_status = [
            'code' => 'unknown',
            'label' => '',
            'detail' => '',
        ];
    }

    // Estilos del "badge" de licencia.
    $badge_style = 'display:inline-block;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;margin-right:8px;';

    switch ($license_status['code']) {
        case 'valid':
            $badge_style .= 'background:#dcfce7;color:#166534;';
            break;
        case 'expired':
        case 'domain_mismatch':
        case 'invalid_format':
            $badge_style .= 'background:#fee2e2;color:#991b1b;';
            break;
        case 'missing':
        default:
            $badge_style .= 'background:#fef9c3;color:#854d0e;';
            break;
    }
    ?>

    <style>
        /* Estilos Apple-Style para el Admin */
        .imco-modern-admin {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            max-width: 900px;
            margin-top: 20px;
            color: #1d1d1f;
        }

        .imco-modern-admin h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .imco-modern-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 32px;
            border: 1px solid #f2f2f7;
            margin-bottom: 24px;
        }

        .imco-modern-card h2 {
            font-size: 20px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e5ea;
            color: #1d1d1f;
        }

        .imco-modern-admin .form-table th {
            font-weight: 500;
            color: #1d1d1f;
            width: 280px;
        }

        .imco-modern-admin input[type="text"],
        .imco-modern-admin input[type="number"] {
            border-radius: 8px;
            border: 1px solid #d1d1d6;
            padding: 8px 12px;
            background-color: #fbfbfd;
            width: 100%;
            max-width: 350px;
            transition: all 0.2s ease;
            box-shadow: none;
        }

        .imco-modern-admin input[type="text"]:focus,
        .imco-modern-admin input[type="number"]:focus {
            border-color: #007aff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
            background-color: #ffffff;
        }

        .imco-modern-admin .description {
            color: #86868b;
            font-size: 13px;
            margin-top: 6px;
            line-height: 1.4;
        }

        .imco-radio-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 400;
            color: #1d1d1f;
        }

        .imco-radio-group input[type="radio"] {
            margin-right: 8px;
            margin-top: -2px;
        }

        .imco-modern-admin .button-primary {
            background: #007aff !important;
            border-color: #007aff !important;
            border-radius: 8px !important;
            padding: 8px 24px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            color: white !important;
            box-shadow: 0 2px 4px rgba(0, 122, 255, 0.2) !important;
            transition: all 0.2s ease !important;
            height: auto !important;
            line-height: normal !important;
        }

        .imco-modern-admin .button-primary:hover {
            background: #005bb5 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 122, 255, 0.3) !important;
        }

        /* iOS Toggle Switch */
        .imco-switch-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
            padding: 16px;
            background: #fbfbfd;
            border-radius: 12px;
            border: 1px solid #e5e5ea;
        }

        .imco-ios-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 30px;
            flex-shrink: 0;
        }

        .imco-ios-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .imco-ios-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e5ea;
            transition: .3s;
            border-radius: 30px;
        }

        .imco-ios-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .imco-ios-switch input:checked+.imco-ios-slider {
            background-color: #34c759;
        }

        .imco-ios-switch input:checked+.imco-ios-slider:before {
            transform: translateX(20px);
        }

        .imco-switch-text {
            flex: 1;
        }

        .imco-switch-title {
            font-weight: 600;
            color: #1d1d1f;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .imco-switch-text .description {
            margin-top: 0;
        }
    </style>

    <div class="wrap imco-modern-admin">
        <h1><?php esc_html_e('Ajustes de IMCO Filter', 'imco-filter'); ?></h1>

        <form method="post" action="options.php">
            <?php settings_fields('imco_filter_settings_group'); ?>

            <!-- TARJETA 1: LICENCIA -->
            <div class="imco-modern-card">
                <h2>Licencia del Producto</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_license_key"><?php esc_html_e('Clave de licencia', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="imco_license_key" name="imco_license_key"
                                    value="<?php echo esc_attr($license_key); ?>"
                                    placeholder="IMCO-dominio.com-YYYYMMDD" />
                                <p class="description">
                                    <?php esc_html_e('Formato: IMCO-dominio.com-YYYYMMDD. Ejemplo: IMCO-ejemplo.com-20261231', 'imco-filter'); ?>
                                </p>

                                <?php if (!empty($license_status['label'])): ?>
                                    <div style="margin-top:12px; display: flex; align-items: center;">
                                        <span style="<?php echo esc_attr($badge_style); ?>">
                                            <?php echo esc_html($license_status['label']); ?>
                                        </span>
                                        <span style="color: #86868b; font-size: 13px;">
                                            <?php echo esc_html($license_status['detail']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TARJETA 2: DISEÑO Y CUADRÍCULA -->
            <div class="imco-modern-card">
                <h2>Diseño y Cuadrícula</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Tipo de filtro', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <fieldset class="imco-radio-group">
                                    <label>
                                        <input type="radio" name="imco_filter_layout" value="horizontal" <?php checked($layout, 'horizontal'); ?> />
                                        <?php esc_html_e('Horizontal (filtros arriba, productos abajo)', 'imco-filter'); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="imco_filter_layout" value="vertical" <?php checked($layout, 'vertical'); ?> />
                                        <?php esc_html_e('Vertical (filtros a la izquierda, productos a la derecha)', 'imco-filter'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Este ajuste afecta tanto al shortcode como a las categorías de WooCommerce.', 'imco-filter'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_products_per_row"><?php esc_html_e('Productos por fila', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="imco_products_per_row" name="imco_products_per_row" min="1" max="6"
                                    value="<?php echo esc_attr($columns); ?>" />
                                <p class="description">
                                    <?php esc_html_e('Número máximo de productos a mostrar por fila en el grid.', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_products_per_page"><?php esc_html_e('Productos por página', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="imco_products_per_page" name="imco_products_per_page" min="1"
                                    max="48" value="<?php echo esc_attr($per_page); ?>" />
                                <p class="description">
                                    <?php esc_html_e('Cantidad de productos que se mostrarán por página o por cada clic en "Cargar más".', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_attribute_group_columns"><?php esc_html_e('Columnas de categorías', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="imco_attribute_group_columns" name="imco_attribute_group_columns"
                                    min="1" max="6" value="<?php echo esc_attr($group_cols); ?>" />
                                <p class="description">
                                    <?php esc_html_e('Número de columnas para las categorías de atributos (COLOR, TIPO DE CORREA) en layout horizontal.', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_attribute_options_columns"><?php esc_html_e('Columnas de opciones', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="imco_attribute_options_columns"
                                    name="imco_attribute_options_columns" min="1" max="6"
                                    value="<?php echo esc_attr($option_cols); ?>" />
                                <p class="description">
                                    <?php esc_html_e('Número de columnas para las opciones dentro de cada categoría (Negro, Blanco, Rojo).', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TARJETA 3: PAGINACIÓN -->
            <div class="imco-modern-card">
                <h2>Paginación</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Tipo de paginación', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <fieldset class="imco-radio-group">
                                    <label>
                                        <input type="radio" name="imco_pagination_mode" value="pages" <?php checked($pagination, 'pages'); ?> />
                                        <?php esc_html_e('Páginas numeradas (1, 2, 3...)', 'imco-filter'); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="imco_pagination_mode" value="load_more" <?php checked($pagination, 'load_more'); ?> />
                                        <?php esc_html_e('Botón "Cargar más" (AJAX)', 'imco-filter'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Elige si quieres una paginación tradicional por páginas o un botón que cargue más productos sin recargar la página.', 'imco-filter'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TARJETA 4: INTEGRACIÓN WOOCOMMERCE -->
            <div class="imco-modern-card">
                <h2>Integración con WooCommerce</h2>
                <p class="description" style="margin-bottom: 20px;">Configura cómo interactúa el filtro con las páginas
                    nativas de tu tienda.</p>

                <!-- Switch 1 -->
                <div class="imco-switch-wrapper">
                    <label class="imco-ios-switch">
                        <input type="checkbox" id="imco_enable_category_archive" name="imco_enable_category_archive"
                            value="yes" <?php checked($enable_arch, 'yes'); ?> />
                        <span class="imco-ios-slider"></span>
                    </label>
                    <div class="imco-switch-text">
                        <div class="imco-switch-title">
                            <?php esc_html_e('Activar filtro IMCO en archivos de categorías', 'imco-filter'); ?></div>
                        <p class="description">
                            <?php esc_html_e('Al entrar en /product-category/alguna-categoria/ se insertará automáticamente el filtro limitado a esa categoría.', 'imco-filter'); ?>
                        </p>
                    </div>
                </div>

                <!-- Switch 2 -->
                <div class="imco-switch-wrapper">
                    <label class="imco-ios-switch">
                        <input type="checkbox" id="imco_hide_woo_category_loop" name="imco_hide_woo_category_loop"
                            value="yes" <?php checked($hide_wooLoop, 'yes'); ?> />
                        <span class="imco-ios-slider"></span>
                    </label>
                    <div class="imco-switch-text">
                        <div class="imco-switch-title">
                            <?php esc_html_e('Ocultar listado nativo de productos', 'imco-filter'); ?></div>
                        <p class="description">
                            <?php esc_html_e('Oculta el grid de productos estándar de WooCommerce en las categorías donde se muestre el filtro IMCO para evitar duplicados.', 'imco-filter'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <p class="submit" style="margin-top: 30px;">
                <button type="submit"
                    class="button button-primary"><?php esc_html_e('Guardar Cambios', 'imco-filter'); ?></button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Callback usado por la página principal del menú.
 */
function imco_render_main_admin_page()
{
    imco_render_settings_page();
}