<?php
/**
 * Plugin Name: IMCO Filter
 * Description: Plugin para filtrar productos por atributos con layouts personalizables (horizontal / vertical) y administración propia.
 * Version: 1.1.6
 * Author: Tu Nombre
 * Text Domain: imco-filter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Seguridad: impedir acceso directo.
}

/**
 * Constante con la ruta del archivo principal del plugin.
 */
if ( ! defined( 'IMCO_PLUGIN_FILE' ) ) {
    define( 'IMCO_PLUGIN_FILE', __FILE__ );
}

/**
 * Cargamos las constantes globales del plugin.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/core-constants.php';

/**
 * Cargamos el "loader" que se encarga de cargar otros componentes.
 */
require_once IMCO_PLUGIN_DIR . 'includes/core-loader.php';

/**
 * Inicializamos el plugin.
 */
function imco_init_plugin() {
    imco_load_plugin_components();
}
add_action( 'plugins_loaded', 'imco_init_plugin' );

/* ============================================================
 *  SHORTCODE TABLA DE ESPECIFICACIONES: [imco_specs_table]
 *  Muestra una tabla con datos del producto + atributos
 *  (WooCommerce + IMCO Filter) para usarla en la descripción.
 * ============================================================ */

if ( ! function_exists( 'imco_specs_table_shortcode' ) ) {

    /**
     * Shortcode: [imco_specs_table]
     */
    function imco_specs_table_shortcode() {

        // Si WooCommerce no está disponible, salimos.
        if ( ! function_exists( 'wc_price' ) ) {
            return '';
        }

        global $product;

        // Asegurarnos de tener un objeto WC_Product
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            if ( is_singular( 'product' ) ) {
                $product = wc_get_product( get_the_ID() );
            }
        }

        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return '';
        }

        $rows = [];

        /* ------------------------
         *  FILAS BÁSICAS
         * ------------------------ */

        // SKU / referencia
        $sku = $product->get_sku();
        if ( $sku ) {
            $rows['Ref.'] = esc_html( $sku );
        }

        // Precio
        $price = $product->get_price();
        if ( '' !== $price ) {
            $rows['Precio'] = wc_price( $price );
        }

        // Nombre del producto
        $rows['Producto'] = esc_html( $product->get_name() );

        /* ------------------------
         *  ATRIBUTOS DE WOOCOMMERCE (opcionales)
         *  Si no usas atributos nativos de Woo, esto casi no mostrará nada,
         *  pero lo dejamos por si en algún momento quieres usarlos.
         * ------------------------ */

        $attributes = $product->get_attributes();

        if ( ! empty( $attributes ) ) {

            // Mapa opcional slug -> etiqueta que quieres mostrar
            $custom_labels = [
                'pa_garantia'             => 'Garantía',
                'pa_tamano-de-la-caja'    => 'Tamaño de la caja',
                'pa_funcion'              => 'Función',
                'pa_material-cristal'     => 'Material cristal',
                'pa_material-del-pulso'   => 'Material del pulso',
                'pa_tipo-de-cierre'       => 'Tipo de cierre',
                'pa_movimiento'           => 'Movimiento',
                'pa_resistencia-al-agua'  => 'Resistencia al agua',
                'pa_color-del-tablero'    => 'Color del tablero',
                'pa_genero'               => 'Género',
            ];

            foreach ( $attributes as $attribute ) {

                // Slug completo, ej: "pa_genero"
                $attr_name = $attribute->get_name();

                // Etiqueta legible
                $label = isset( $custom_labels[ $attr_name ] )
                    ? $custom_labels[ $attr_name ]
                    : wc_attribute_label( $attr_name );

                // Valor(es)
                if ( $attribute->is_taxonomy() ) {
                    $terms = wc_get_product_terms(
                        $product->get_id(),
                        $attr_name,
                        [ 'fields' => 'names' ]
                    );
                    $value = implode( ', ', $terms );
                } else {
                    $value = implode( ', ', $attribute->get_options() );
                }

                if ( '' !== $value ) {
                    $rows[ $label ] = esc_html( $value );
                }
            }
        }

        /* ------------------------
         *  ATRIBUTOS DE IMCO FILTER
         *  (los del metabox "IMCO - Atributos del producto")
         * ------------------------ */

        $imco_meta = get_post_meta( $product->get_id(), '_imco_product_attributes', true );

        if ( is_array( $imco_meta ) && ! empty( $imco_meta ) ) {

            // Índice id -> label usando la configuración del plugin
            $imco_labels = [];
            if ( function_exists( 'imco_get_attribute_categories' ) ) {
                $cats = imco_get_attribute_categories();
                if ( is_array( $cats ) ) {
                    foreach ( $cats as $cat ) {
                        if ( empty( $cat['id'] ) ) {
                            continue;
                        }
                        $id    = $cat['id'];
                        $label = ! empty( $cat['label'] ) ? $cat['label'] : $id;
                        $imco_labels[ $id ] = $label;
                    }
                }
            }

            // Recorremos lo que se guardó en el meta
            foreach ( $imco_meta as $cat_id => $values ) {

                if ( empty( $cat_id ) ) {
                    continue;
                }

                if ( ! is_array( $values ) ) {
                    $values = [ $values ];
                }

                $values = array_filter( array_map( 'sanitize_text_field', $values ) );
                if ( empty( $values ) ) {
                    continue;
                }

                // Etiqueta que verá el usuario
                if ( isset( $imco_labels[ $cat_id ] ) ) {
                    $label = $imco_labels[ $cat_id ];
                } else {
                    // Fallback si por alguna razón no encontramos la categoría
                    $label = ucfirst( str_replace( [ '-', '_' ], ' ', $cat_id ) );
                }

                $rows[ $label ] = esc_html( implode( ', ', $values ) );
            }
        }

        // Si no hay nada que mostrar, no imprimimos tabla.
        if ( empty( $rows ) ) {
            return '';
        }

        /* ------------------------
         *  HTML DE LA TABLA
         * ------------------------ */

        ob_start();
        ?>
        <table class="imco-specs-table">
            <tbody>
            <?php foreach ( $rows as $label => $value ) : ?>
                <tr>
                    <th><?php echo esc_html( $label ); ?></th>
                    <td><?php echo $value; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php

        return ob_get_clean();
    }
}

// Registrar el shortcode
add_shortcode( 'imco_specs_table', 'imco_specs_table_shortcode' );

/**
 * Cargar estilos de la tabla de especificaciones solo en páginas de producto.
 */
function imco_specs_enqueue_specs_table_styles() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }

    // Ruta base del plugin (por si IMCO_PLUGIN_URL no existe).
    $base_url = defined( 'IMCO_PLUGIN_URL' )
        ? IMCO_PLUGIN_URL
        : plugin_dir_url( IMCO_PLUGIN_FILE );

    wp_enqueue_style(
        'imco-specs-table',
        $base_url . 'assets/css/specs-table.css',
        array(),
        '1.0.1'
    );
}
add_action( 'wp_enqueue_scripts', 'imco_specs_enqueue_specs_table_styles' );
