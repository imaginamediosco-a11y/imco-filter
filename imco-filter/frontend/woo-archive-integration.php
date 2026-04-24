<?php
/**
 * Integración de IMCO Filter con archivos de WooCommerce:
 * - Categorías de producto (product_cat).
 * - Página de tienda principal (shop).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Devuelve true si debemos inyectar el filtro IMCO
 * en la página actual (shop o categoría), según ajustes.
 *
 * @return bool
 */
function imco_should_show_archive_filter()
{

    // Solo en páginas de WooCommerce relevantes
    if (function_exists('is_product_category') && is_product_category()) {
        $enabled_cat = get_option('imco_enable_category_archive', 'no');
        return ('yes' === $enabled_cat);
    }

    if (function_exists('is_shop') && is_shop()) {
        $enabled_shop = get_option('imco_enable_shop_archive', 'no');
        return ('yes' === $enabled_shop);
    }

    return false;
}

/**
 * Antes de que WooCommerce pinte el loop,
 * insertamos nuestro filtro (igual que el shortcode),
 * pero adaptado al contexto (shop o categoría).
 */
function imco_output_archive_filter()
{

    if (!imco_should_show_archive_filter()) {
        return;
    }

    if (!function_exists('imco_render_filter_form') || !function_exists('imco_render_products_grid')) {
        // Aún no están cargadas las funciones del frontend.
        return;
    }

    // Usamos la misma variable global que el shortcode para limitar por categoría.
    global $imco_current_category_slug;

    $imco_current_category_slug = '';

    // Si estamos en una categoría de producto, guardamos su slug.
    if (function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term) && !empty($term->slug)) {
            $imco_current_category_slug = sanitize_title($term->slug);
        }
    }

    // Layout (horizontal / vertical) y columnas desde ajustes.
    $layout = get_option('imco_filter_layout', 'horizontal');
    if (!in_array($layout, ['horizontal', 'vertical'], true)) {
        $layout = 'horizontal';
    }

    $columns = (int) get_option('imco_products_per_row', 4);
    if ($columns < 1) {
        $columns = 4;
    } elseif ($columns > 6) {
        $columns = 6;
    }

    $columns_mobile = (int) get_option('imco_products_per_row_mobile', 2);
    if ($columns_mobile < 1) {
        $columns_mobile = 1;
    } elseif ($columns_mobile > 4) {
        $columns_mobile = 4;
    }

    $collapsible = get_option('imco_collapsible_filter', 'no');

    $wrapper_classes = 'imco-filter-wrapper imco-layout-' . esc_attr($layout);
    if ('yes' === $collapsible) {
        $wrapper_classes .= ' imco-collapsible-filter';
    }

    $grid_classes = 'imco-products-grid imco-columns-' . $columns . ' imco-columns-mobile-' . $columns_mobile;
    ?>

    <div class="imco-filter-archive <?php echo esc_attr($wrapper_classes); ?>">

        <!-- Botón FILTRAR (Móvil y Desktop si es colapsable) -->
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

        <!-- Overlay oscuro -->
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

            <?php imco_render_filter_form(); ?>
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
    // ⬇️ Como el filtro está activo, ocultamos SIEMPRE el listado nativo de WooCommerce
    // para evitar duplicados.
    ?>
    <style>
        /* Ocultar cabecera, migas de pan, resultado, orden y grid nativo
                           en tienda y categorías cuando IMCO está reemplazando el listado */
        .woocommerce-breadcrumb,
        .woocommerce .woocommerce-products-header,
        .woocommerce .woocommerce-products-header__title,
        .woocommerce .page-title,
        .woocommerce .archive-description,
        .woocommerce .term-description,
        .woocommerce .woocommerce-result-count,
        .woocommerce .woocommerce-ordering {
            display: none !important;
        }

        /* Ocultar el grid de productos nativo */
        .woocommerce ul.products {
            display: none !important;
        }

        /* Restaurar el grid de productos de nuestro filtro IMCO */
        .woocommerce .imco-filter-archive ul.products {
            display: grid !important;
            /* Forzar grid para que las columnas funcionen */
        }

        /* Ocultar la paginación nativa */
        .woocommerce nav.woocommerce-pagination {
            display: none !important;
        }

        /* Restaurar nuestra paginación */
        .woocommerce .imco-filter-archive nav.woocommerce-pagination {
            display: block !important;
            display: revert !important;
        }
    </style>
    <?php
}
add_action('woocommerce_before_main_content', 'imco_output_archive_filter', 20);

/**
 * (Opcional) También añadimos una clase al body por si en algún momento
 * quieres usarla en CSS o JS. No es estrictamente necesaria para ocultar el loop.
 */
function imco_archive_body_class($classes)
{
    if (!imco_should_show_archive_filter()) {
        return $classes;
    }

    if (!in_array('imco-hide-woo-loop', $classes, true)) {
        $classes[] = 'imco-hide-woo-loop';
    }

    return $classes;
}
add_filter('body_class', 'imco_archive_body_class');
