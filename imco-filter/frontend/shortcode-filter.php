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

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode principal del filtro IMCO.
 *
 * @param array $atts
 * @return string
 */
function imco_product_filter_shortcode($atts = [])
{

    // Aseguramos que las funciones del frontend existen.
    if (
        !function_exists('imco_render_filter_form') ||
        !function_exists('imco_render_products_grid') ||
        !function_exists('imco_render_active_filters_block')
    ) {
        return '';
    }

    // Valores por defecto del shortcode.
    $atts = shortcode_atts(
        [
            'category' => '', // slug de product_cat
            'columns' => get_option('imco_products_per_row', 4),
            'columns_mobile' => get_option('imco_products_per_row_mobile', 2),
            'layout' => get_option('imco_filter_layout', 'horizontal'),
        ],
        $atts,
        'imco_product_filter'
    );

    // Sanitizar atributos.
    $category_slug = sanitize_title($atts['category']);

    $layout = in_array($atts['layout'], ['horizontal', 'vertical'], true)
        ? $atts['layout']
        : 'horizontal';

    $columns = (int) $atts['columns'];
    if ($columns < 1) {
        $columns = 1;
    } elseif ($columns > 6) {
        $columns = 6;
    }

    $columns_mobile = (int) $atts['columns_mobile'];
    if ($columns_mobile < 1) {
        $columns_mobile = 1;
    } elseif ($columns_mobile > 4) {
        $columns_mobile = 4;
    }

    // Variable global que usan las funciones del frontend
    // para saber la categoría actual (se respeta también en AJAX
    // si tu handler la utiliza).
    global $imco_current_category_slug;
    $old_category_slug = $imco_current_category_slug;
    $imco_current_category_slug = $category_slug;

    $collapsible = get_option('imco_collapsible_filter', 'no');

    $wrapper_classes = 'imco-filter-wrapper imco-layout-' . esc_attr($layout);
    if ('yes' === $collapsible) {
        $wrapper_classes .= ' imco-collapsible-filter';
    }

    $grid_classes = 'imco-products-grid imco-columns-' . $columns . ' imco-columns-mobile-' . $columns_mobile;

    ob_start();
    ?>
    <div class="imco-filter-archive <?php echo esc_attr($wrapper_classes); ?>" <?php if (!empty($category_slug)): ?>
            data-imco-base-category="<?php echo esc_attr($category_slug); ?>" <?php endif; ?>>

        <!-- Botón FILTRAR -->
        <button type="button" class="imco-mobile-filter-button">
            <span class="imco-mobile-filter-button-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </span>
            <span class="imco-mobile-filter-button-label">
                <?php esc_html_e('Filtrar', 'imco-filter'); ?>
            </span>
        </button>

        <!-- Fondo oscuro (overlay) -->
        <div class="imco-mobile-filter-overlay"></div>

        <!-- Panel de filtros -->
        <div class="imco-filter-panel">
            <button type="button" class="imco-mobile-filter-close"
                aria-label="<?php esc_attr_e('Cerrar filtros', 'imco-filter'); ?>">
                ×
            </button>

            <div class="imco-mobile-panel-title">
                <?php esc_html_e('Filtro', 'imco-filter'); ?>
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

            <div class="<?php echo esc_attr($grid_classes); ?>" id="imco-products-grid">
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
add_shortcode('imco_product_filter', 'imco_product_filter_shortcode');

// Alias opcional por si alguna vez escribes el nombre corto.
add_shortcode('imco_filter', 'imco_product_filter_shortcode');
