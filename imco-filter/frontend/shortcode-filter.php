<?php
/**
 * Shortcode [imco_product_filter] para usar IMCO Filter en
 * cualquier página / entrada / plantilla (incluido Elementor).
 *
 * Ejemplos:
 *   [imco_product_filter]
 *   [imco_product_filter category="relojeria-original-klasic" columns="3" layout="horizontal"]
 *
 * Parámetros:
 * - category (string)  Slug de la categoría (product_cat) base. Opcional.
 * - columns  (int)     1–6, número de columnas del grid de productos.
 * - layout   (string)  horizontal | vertical (por defecto horizontal).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode principal del filtro IMCO.
 *
 * @param array $atts
 * @return string
 */
function imco_product_filter_shortcode( $atts = [] ) {

    // Aseguramos que las funciones del frontend existen.
    if (
        ! function_exists( 'imco_render_filter_form' ) ||
        ! function_exists( 'imco_render_products_grid' ) ||
        ! function_exists( 'imco_render_active_filters_block' )
    ) {
        return '';
    }

    // Valores por defecto del shortcode.
    $atts = shortcode_atts(
        [
            'category' => '', // slug de product_cat
            'columns'  => get_option( 'imco_products_per_row', 4 ),
            'layout'   => get_option( 'imco_filter_layout', 'horizontal' ),
        ],
        $atts,
        'imco_product_filter'
    );

    // Sanitizar atributos.
    $category_slug = sanitize_title( $atts['category'] );

    $layout = in_array( $atts['layout'], [ 'horizontal', 'vertical' ], true )
        ? $atts['layout']
        : 'horizontal';

    $columns = (int) $atts['columns'];
    if ( $columns < 1 ) {
        $columns = 1;
    } elseif ( $columns > 6 ) {
        $columns = 6;
    }

    // Variable global que usan las funciones del frontend
    // para saber la categoría actual (se respeta también en AJAX
    // si tu handler la utiliza).
    global $imco_current_category_slug;
    $old_category_slug          = $imco_current_category_slug;
    $imco_current_category_slug = $category_slug;

    $wrapper_classes = 'imco-filter-wrapper imco-layout-' . esc_attr( $layout );
    $grid_classes    = 'imco-products-grid imco-columns-' . $columns;

    ob_start();
    ?>
    <div
        class="imco-filter-archive <?php echo esc_attr( $wrapper_classes ); ?>"
        <?php if ( ! empty( $category_slug ) ) : ?>
            data-imco-base-category="<?php echo esc_attr( $category_slug ); ?>"
        <?php endif; ?>
    >

        <!-- Botón móvil FILTRAR -->
        <button type="button" class="imco-mobile-filter-button">
            <span class="imco-mobile-filter-button-icon">⚲</span>
            <span class="imco-mobile-filter-button-label">
                <?php esc_html_e( 'Filtrar', 'imco-filter' ); ?>
            </span>
        </button>

        <!-- Fondo oscuro (overlay) -->
        <div class="imco-mobile-filter-overlay"></div>

        <!-- Panel de filtros -->
        <div class="imco-filter-panel">
            <button type="button"
                    class="imco-mobile-filter-close"
                    aria-label="<?php esc_attr_e( 'Cerrar filtros', 'imco-filter' ); ?>">
                ×
            </button>

            <div class="imco-mobile-panel-title">
                <?php esc_html_e( 'Filtro', 'imco-filter' ); ?>
            </div>

            <?php
            // El formulario de filtros (atributos, selects, etc.).
            // Si ya añadimos en tu template el hidden `imco_base_category`,
            // aquí no hace falta repetirlo.
            imco_render_filter_form();
            ?>
        </div>

        <!-- Resultados -->
        <div class="imco-filter-results">
            <?php imco_render_active_filters_block(); ?>

            <div class="<?php echo esc_attr( $grid_classes ); ?>" id="imco-products-grid">
                <?php imco_render_products_grid(); ?>
            </div>
        </div>
    </div>
    <?php

    $output = ob_get_clean();

    // Devolver la variable global a su valor anterior (no ensuciar globales).
    $imco_current_category_slug = $old_category_slug;

    return $output;
}

// Registrar el shortcode principal.
add_shortcode( 'imco_product_filter', 'imco_product_filter_shortcode' );

// Alias opcional por si alguna vez escribes el nombre corto.
add_shortcode( 'imco_filter', 'imco_product_filter_shortcode' );
