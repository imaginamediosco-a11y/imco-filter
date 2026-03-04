<?php
/**
 * Página de ajustes del plugin IMCO Filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registra las opciones del plugin.
 */
function imco_register_settings() {

    // Clave de licencia.
    register_setting(
        'imco_filter_settings_group',
        'imco_license_key',
        [
            'type'              => 'string',
            'sanitize_callback' => function ( $value ) {
                $value = trim( (string) $value );
                return sanitize_text_field( $value );
            },
            'default'           => '',
        ]
    );

    // Diseño del filtro (horizontal / vertical).
    register_setting(
        'imco_filter_settings_group',
        'imco_filter_layout',
        [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'horizontal',
        ]
    );

    // Número de productos por fila.
    register_setting(
        'imco_filter_settings_group',
        'imco_products_per_row',
        [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 4,
        ]
    );

    // Número de productos por página.
    register_setting(
        'imco_filter_settings_group',
        'imco_products_per_page',
        [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 12,
        ]
    );

    // NUEVO: columnas de categorías de atributos (layout horizontal).
    register_setting(
        'imco_filter_settings_group',
        'imco_attribute_group_columns',
        [
            'type'              => 'integer',
            'sanitize_callback' => function ( $value ) {
                $v = absint( $value );
                if ( $v < 1 ) {
                    $v = 1;
                }
                if ( $v > 6 ) {
                    $v = 6;
                }
                return $v;
            },
            'default'           => 4,
        ]
    );

    // NUEVO: columnas de variaciones/opciones de atributos.
    register_setting(
        'imco_filter_settings_group',
        'imco_attribute_options_columns',
        [
            'type'              => 'integer',
            'sanitize_callback' => function ( $value ) {
                $v = absint( $value );
                if ( $v < 1 ) {
                    $v = 1;
                }
                if ( $v > 6 ) {
                    $v = 6;
                }
                return $v;
            },
            'default'           => 4,
        ]
    );

    // Tipo de paginación (pages | load_more).
    register_setting(
        'imco_filter_settings_group',
        'imco_pagination_mode',
        [
            'type'              => 'string',
            'sanitize_callback' => function ( $value ) {
                $allowed = [ 'pages', 'load_more' ];
                return in_array( $value, $allowed, true ) ? $value : 'pages';
            },
            'default'           => 'pages',
        ]
    );

    // Activar filtro en archivos de categorías de WooCommerce (product_cat).
    register_setting(
        'imco_filter_settings_group',
        'imco_enable_category_archive',
        [
            'type'              => 'string',
            'sanitize_callback' => function ( $value ) {
                return ( 'yes' === $value ) ? 'yes' : 'no';
            },
            'default'           => 'no',
        ]
    );

    // Ocultar loop nativo de WooCommerce cuando IMCO está activo.
    register_setting(
        'imco_filter_settings_group',
        'imco_hide_woo_category_loop',
        [
            'type'              => 'string',
            'sanitize_callback' => function ( $value ) {
                return ( 'yes' === $value ) ? 'yes' : 'no';
            },
            'default'           => 'no',
        ]
    );
}
add_action( 'admin_init', 'imco_register_settings' );

/**
 * Renderiza la página de ajustes (contenido principal).
 */
function imco_render_settings_page() {

    // Valores actuales.
    $license_key  = get_option( 'imco_license_key', '' );
    $layout       = get_option( 'imco_filter_layout', 'horizontal' );
    $columns      = (int) get_option( 'imco_products_per_row', 4 );
    $per_page     = (int) get_option( 'imco_products_per_page', 12 );
    $group_cols   = (int) get_option( 'imco_attribute_group_columns', 4 );
    $option_cols  = (int) get_option( 'imco_attribute_options_columns', 4 );
    $pagination   = get_option( 'imco_pagination_mode', 'pages' );
    $enable_arch  = get_option( 'imco_enable_category_archive', 'no' );
    $hide_wooLoop = get_option( 'imco_hide_woo_category_loop', 'no' );

    // Estado de licencia.
    if ( function_exists( 'imco_get_license_status' ) ) {
        $license_status = imco_get_license_status();
    } else {
        $license_status = [
            'code'   => 'unknown',
            'label'  => '',
            'detail' => '',
        ];
    }

    // Estilos del "badge" de licencia.
    $badge_style = 'display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;margin-right:8px;';

    switch ( $license_status['code'] ) {
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
    <div class="wrap">
        <h1><?php esc_html_e( 'IMCO Filter - Ajustes', 'imco-filter' ); ?></h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'imco_filter_settings_group' ); ?>

            <table class="form-table" role="presentation">
                <tbody>

                <!-- Clave de licencia -->
                <tr>
                    <th scope="row">
                        <label for="imco_license_key"><?php esc_html_e( 'Clave de licencia', 'imco-filter' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="imco_license_key"
                               name="imco_license_key"
                               class="regular-text"
                               value="<?php echo esc_attr( $license_key ); ?>" />
                        <p class="description">
                            <?php esc_html_e( 'Formato: IMCO-dominio.com-YYYYMMDD. Ejemplo: IMCO-ejemplo.com-20261231', 'imco-filter' ); ?>
                        </p>

                        <?php if ( ! empty( $license_status['label'] ) ) : ?>
                            <p style="margin-top:8px;">
                                <span style="<?php echo esc_attr( $badge_style ); ?>">
                                    <?php echo esc_html( $license_status['label'] ); ?>
                                </span>
                                <span>
                                    <?php echo esc_html( $license_status['detail'] ); ?>
                                </span>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Tipo de layout -->
                <tr>
                    <th scope="row">
                        <label for="imco_filter_layout"><?php esc_html_e( 'Tipo de filtro', 'imco-filter' ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                       name="imco_filter_layout"
                                       value="horizontal"
                                    <?php checked( $layout, 'horizontal' ); ?> />
                                <?php esc_html_e( 'Horizontal (filtros arriba, productos abajo)', 'imco-filter' ); ?>
                            </label>
                            <br />
                            <label>
                                <input type="radio"
                                       name="imco_filter_layout"
                                       value="vertical"
                                    <?php checked( $layout, 'vertical' ); ?> />
                                <?php esc_html_e( 'Vertical (filtros a la izquierda, productos a la derecha)', 'imco-filter' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Este ajuste afecta tanto al shortcode como a las categorías de WooCommerce (si están activadas).', 'imco-filter' ); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

                <!-- Número de productos por fila -->
                <tr>
                    <th scope="row">
                        <label for="imco_products_per_row"><?php esc_html_e( 'Productos por fila', 'imco-filter' ); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               id="imco_products_per_row"
                               name="imco_products_per_row"
                               min="1"
                               max="6"
                               value="<?php echo esc_attr( $columns ); ?>" />
                        <p class="description">
                            <?php esc_html_e( 'Número máximo de productos a mostrar por fila en el grid.', 'imco-filter' ); ?>
                        </p>
                    </td>
                </tr>

                <!-- Número de productos por página -->
                <tr>
                    <th scope="row">
                        <label for="imco_products_per_page"><?php esc_html_e( 'Productos por página', 'imco-filter' ); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               id="imco_products_per_page"
                               name="imco_products_per_page"
                               min="1"
                               max="48"
                               value="<?php echo esc_attr( $per_page ); ?>" />
                        <p class="description">
                            <?php esc_html_e( 'Cantidad de productos que se mostrarán por página o por cada clic en "Cargar más".', 'imco-filter' ); ?>
                        </p>
                    </td>
                </tr>

                <!-- NUEVO: columnas de categorías de atributos -->
                <tr>
                    <th scope="row">
                        <label for="imco_attribute_group_columns">
                            <?php esc_html_e( 'Columnas de categorías de atributos', 'imco-filter' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number"
                               id="imco_attribute_group_columns"
                               name="imco_attribute_group_columns"
                               min="1"
                               max="6"
                               value="<?php echo esc_attr( $group_cols ); ?>" />
                        <p class="description">
                            <?php esc_html_e( 'Número de columnas que se usarán para las categorías de atributos (COLOR, TIPO DE CORREA, etc.) en el layout horizontal.', 'imco-filter' ); ?>
                        </p>
                    </td>
                </tr>

                <!-- NUEVO: columnas de variaciones/opciones -->
                <tr>
                    <th scope="row">
                        <label for="imco_attribute_options_columns">
                            <?php esc_html_e( 'Columnas de variaciones de atributos', 'imco-filter' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number"
                               id="imco_attribute_options_columns"
                               name="imco_attribute_options_columns"
                               min="1"
                               max="6"
                               value="<?php echo esc_attr( $option_cols ); ?>" />
                        <p class="description">
                            <?php esc_html_e( 'Número de columnas para las opciones dentro de cada categoría (por ejemplo: Negro, Blanco, Rojo). Afecta tanto al layout horizontal como al vertical.', 'imco-filter' ); ?>
                        </p>
                    </td>
                </tr>

                <!-- Tipo de paginación -->
                <tr>
                    <th scope="row">
                        <label for="imco_pagination_mode"><?php esc_html_e( 'Tipo de paginación', 'imco-filter' ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                       name="imco_pagination_mode"
                                       value="pages"
                                    <?php checked( $pagination, 'pages' ); ?> />
                                <?php esc_html_e( 'Páginas numeradas (1, 2, 3...)', 'imco-filter' ); ?>
                            </label>
                            <br />
                            <label>
                                <input type="radio"
                                       name="imco_pagination_mode"
                                       value="load_more"
                                    <?php checked( $pagination, 'load_more' ); ?> />
                                <?php esc_html_e( 'Botón "Cargar más" (AJAX)', 'imco-filter' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Elige si quieres una paginación tradicional por páginas o un botón que cargue más productos sin recargar la página.', 'imco-filter' ); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

                <!-- Categorías de producto de WooCommerce (switches) -->
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Categorías de producto de WooCommerce', 'imco-filter' ); ?>
                    </th>
                    <td>
                        <!-- Switch: activar filtro en archivos de categoría -->
                        <div class="imco-switch-wrapper">
                            <label class="imco-switch">
                                <input type="checkbox"
                                       id="imco_enable_category_archive"
                                       name="imco_enable_category_archive"
                                       value="yes"
                                    <?php checked( $enable_arch, 'yes' ); ?> />
                                <span class="imco-switch-slider"></span>
                            </label>
                            <div class="imco-switch-text">
                                <div class="imco-switch-title">
                                    <?php esc_html_e( 'Activar filtro IMCO en archivos de categorías', 'imco-filter' ); ?>
                                </div>
                                <p class="description">
                                    <?php esc_html_e( 'Cuando está activado, al entrar en /product-category/alguna-categoria/ se insertará el mismo filtro que usa el shortcode, limitado a esa categoría.', 'imco-filter' ); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Switch: ocultar loop nativo de WooCommerce -->
                        <div class="imco-switch-wrapper" style="margin-top: 10px;">
                            <label class="imco-switch">
                                <input type="checkbox"
                                       id="imco_hide_woo_category_loop"
                                       name="imco_hide_woo_category_loop"
                                       value="yes"
                                    <?php checked( $hide_wooLoop, 'yes' ); ?> />
                                <span class="imco-switch-slider"></span>
                            </label>
                            <div class="imco-switch-text">
                                <div class="imco-switch-title">
                                    <?php esc_html_e( 'Ocultar listado nativo de productos de WooCommerce', 'imco-filter' ); ?>
                                </div>
                                <p class="description">
                                    <?php esc_html_e( 'Si está activado, se ocultará el grid de productos estándar de WooCommerce en las categorías donde se muestre el filtro IMCO (para evitar ver los productos duplicados).', 'imco-filter' ); ?>
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>

                </tbody>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Callback usado por la página principal del menú.
 */
function imco_render_main_admin_page() {
    imco_render_settings_page();
}
