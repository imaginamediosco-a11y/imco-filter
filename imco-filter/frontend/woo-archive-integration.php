<?php
/**
 * Integración de IMCO Filter con archivos de WooCommerce:
 * - Categorías de producto (product_cat).
 * - Página de tienda principal (shop).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve true si debemos inyectar el filtro IMCO
 * en la página actual (shop o categoría), según ajustes.
 *
 * @return bool
 */
function imco_should_show_archive_filter() {

    // Ajuste del admin: activar en archivos de categorías (y tienda)
    $enabled = get_option( 'imco_enable_category_archive', 'no' );
    if ( 'yes' !== $enabled ) {
        return false;
    }

    // Solo en páginas de WooCommerce relevantes
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        return true;
    }

    if ( function_exists( 'is_shop' ) && is_shop() ) {
        return true;
    }

    return false;
}

/**
 * Antes de que WooCommerce pinte el loop,
 * insertamos nuestro filtro (igual que el shortcode),
 * pero adaptado al contexto (shop o categoría).
 */
function imco_output_archive_filter() {

    if ( ! imco_should_show_archive_filter() ) {
        return;
    }

    if ( ! function_exists( 'imco_render_filter_form' ) || ! function_exists( 'imco_render_products_grid' ) ) {
        // Aún no están cargadas las funciones del frontend.
        return;
    }

    // Usamos la misma variable global que el shortcode para limitar por categoría.
    global $imco_current_category_slug;

    $imco_current_category_slug = '';

    // Si estamos en una categoría de producto, guardamos su slug.
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term = get_queried_object();
        if ( $term && ! is_wp_error( $term ) && ! empty( $term->slug ) ) {
            $imco_current_category_slug = sanitize_title( $term->slug );
        }
    }

    // Layout (horizontal / vertical) y columnas desde ajustes.
    $layout = get_option( 'imco_filter_layout', 'horizontal' );
    if ( ! in_array( $layout, [ 'horizontal', 'vertical' ], true ) ) {
        $layout = 'horizontal';
    }

    $columns = (int) get_option( 'imco_products_per_row', 4 );
    if ( $columns < 1 ) {
        $columns = 4;
    } elseif ( $columns > 6 ) {
        $columns = 6;
    }

    $wrapper_classes = 'imco-filter-wrapper imco-layout-' . esc_attr( $layout );
    $grid_classes    = 'imco-products-grid imco-columns-' . $columns;

    // ¿Debemos ocultar el loop nativo?
    $hide_loop = get_option( 'imco_hide_woo_category_loop', 'no' );
    ?>

    <div class="imco-filter-archive <?php echo esc_attr( $wrapper_classes ); ?>">

        <!-- Botón móvil FILTRAR -->
        <button type="button" class="imco-mobile-filter-button">
            <span class="imco-mobile-filter-button-icon">⚲</span>
            <span class="imco-mobile-filter-button-label">
                <?php esc_html_e( 'Filtrar', 'imco-filter' ); ?>
            </span>
        </button>

        <!-- Overlay oscuro -->
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

    <?php imco_render_filter_form(); ?>
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
    // ⬇️ Si está activado "Ocultar listado nativo de WooCommerce",
    // metemos aquí MISMO el CSS que lo esconde.
    if ( 'yes' === $hide_loop ) : ?>
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
            .woocommerce .woocommerce-ordering,
            .woocommerce ul.products {
                display: none !important;
            }
        </style>
    <?php
    endif;
}
add_action( 'woocommerce_before_main_content', 'imco_output_archive_filter', 5 );

/**
 * (Opcional) También añadimos una clase al body por si en algún momento
 * quieres usarla en CSS o JS. No es estrictamente necesaria para ocultar el loop.
 */
function imco_archive_body_class( $classes ) {

    $hide_loop = get_option( 'imco_hide_woo_category_loop', 'no' );
    if ( 'yes' !== $hide_loop ) {
        return $classes;
    }

    if ( ! imco_should_show_archive_filter() ) {
        return $classes;
    }

    if ( ! in_array( 'imco-hide-woo-loop', $classes, true ) ) {
        $classes[] = 'imco-hide-woo-loop';
    }

    return $classes;
}
add_filter( 'body_class', 'imco_archive_body_class' );
