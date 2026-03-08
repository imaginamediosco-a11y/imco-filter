<?php
/**
 * Plantilla Moderna de Producto IMCO (Apple-Style)
 * Sobrescribe la plantilla de cualquier tema.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargamos el header del tema para no romper la navegación de la página
get_header('shop');

// Evitamos que nuestra propia función inyecte la tabla duplicada en the_content()
remove_filter('the_content', 'imco_auto_inject_specs_table');

// INICIO DEL LOOP DE WORDPRESS (Esto es lo que faltaba)
while (have_posts()):
    the_post();

    global $product;
    if (empty($product) || !is_a($product, 'WC_Product')) {
        $product = wc_get_product(get_the_ID());
    }

    // Si por alguna razón no hay producto válido, saltamos
    if (!$product) {
        continue;
    }
    ?>

    <div class="imco-modern-product-container">
        <div class="imco-modern-product-wrapper">

            <!-- 1. Referencia (SKU) y Categoría -->
            <div class="imco-product-meta-top">
                <?php if (wc_product_sku_enabled() && ($product->get_sku() || $product->is_type('variable'))): ?>
                    <div class="imco-sku">
                        <?php esc_html_e('Ref:', 'imco-filter'); ?>
                        <span><?php echo ($sku = $product->get_sku()) ? $sku : esc_html__('N/A', 'imco-filter'); ?></span>
                    </div>
                <?php endif; ?>

                <div class="imco-category">
                    <?php echo wc_get_product_category_list($product->get_id(), ', '); ?>
                </div>
            </div>

            <!-- 2. Título del Producto -->
            <h1 class="imco-product-title"><?php the_title(); ?></h1>

            <!-- 3. Imagen / Galería -->
            <div class="imco-product-gallery">
                <?php
                // Usamos la función nativa para no perder el zoom o slider de WooCommerce
                woocommerce_show_product_images();
                ?>
            </div>

            <!-- 4. Precio -->
            <div class="imco-product-price">
                <?php echo $product->get_price_html(); ?>
            </div>

            <!-- 5. Botón de Compra (Formulario nativo para soportar variaciones) -->
            <div class="imco-product-add-to-cart">
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>

            <!-- 6. Acordeón Moderno (Descripción y Especificaciones) -->
            <div class="imco-tabs-wrapper">
                <?php
                // Leer la opción de posición que ya configuramos en el panel
                $position = get_option('imco_specs_position', 'after_summary');

                if ($position === 'before_summary'):
                    // ESPECIFICACIONES PRIMERO
                    ?>
                    <!-- Botones de las pestañas -->
                    <div class="imco-tabs-nav">
                        <button class="imco-tab-btn active" data-tab="imco-tab-specs">Especificaciones</button>
                        <button class="imco-tab-btn" data-tab="imco-tab-desc">Descripción</button>
                    </div>

                    <!-- Contenido: Especificaciones -->
                    <div class="imco-tab-content active" id="imco-tab-specs">
                        <?php echo do_shortcode('[imco_specs_table]'); ?>
                    </div>

                    <!-- Contenido: Descripción -->
                    <div class="imco-tab-content" id="imco-tab-desc">
                        <?php the_content(); ?>
                    </div>
                <?php else:
                    // DESCRIPCIÓN PRIMERO (Por defecto)
                    ?>
                    <!-- Botones de las pestañas -->
                    <div class="imco-tabs-nav">
                        <button class="imco-tab-btn active" data-tab="imco-tab-desc">Descripción</button>
                        <button class="imco-tab-btn" data-tab="imco-tab-specs">Especificaciones</button>
                    </div>

                    <!-- Contenido: Descripción -->
                    <div class="imco-tab-content active" id="imco-tab-desc">
                        <?php the_content(); ?>
                    </div>

                    <!-- Contenido: Especificaciones -->
                    <div class="imco-tab-content" id="imco-tab-specs">
                        <?php echo do_shortcode('[imco_specs_table]'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Script para que funcionen las pestañas -->
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const tabBtns = document.querySelectorAll('.imco-tab-btn');
                    const tabContents = document.querySelectorAll('.imco-tab-content');

                    tabBtns.forEach(btn => {
                        btn.addEventListener('click', () => {
                            // Quitar clase 'active' de todos los botones y contenidos
                            tabBtns.forEach(b => b.classList.remove('active'));
                            tabContents.forEach(c => c.classList.remove('active'));

                            // Agregar clase 'active' al botón clickeado y su contenido
                            btn.classList.add('active');
                            const targetId = btn.getAttribute('data-tab');
                            document.getElementById(targetId).classList.add('active');
                        });
                    });
                });
            </script>

        </div>
    </div>

    <?php
endwhile; // FIN DEL LOOP DE WORDPRESS

// Restauramos el filtro por seguridad
add_filter('the_content', 'imco_auto_inject_specs_table');

// Cargamos el footer del tema
get_footer('shop');
?>