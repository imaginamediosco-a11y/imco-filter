<?php
/**
 * Shortcode [imco_specs_table]
 * Muestra una tabla de especificaciones del producto usando:
 * - Datos básicos (nombre, precio).
 * - Atributos guardados en el metabox "IMCO - Atributos del producto".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve un array de pares [label, value] con los atributos IMCO
 * guardados en el meta _imco_product_attributes para un producto.
 *
 * Estructura meta (según product-metabox-basic.php):
 *  _imco_product_attributes = [
 *      'color'        => [ 'Negro', 'Blanco' ],
 *      'tipodecorrea' => [ 'Cuero' ],
 *      ...
 *  ]
 *
 * Y las categorías globales vienen de imco_get_attribute_categories():
 *  [
 *      [
 *          'id'    => 'color',
 *          'label' => 'COLOR',
 *          'options' => [...]
 *      ],
 *      ...
 *  ]
 *
 * @param int $product_id
 * @return array[]  [ [ 'label' => 'Color', 'value' => 'Negro, Blanco' ], ... ]
 */
function imco_get_product_imco_attribute_pairs( $product_id ) {

    // Meta con los valores seleccionados en el metabox IMCO.
    $raw = get_post_meta( $product_id, '_imco_product_attributes', true );
    if ( ! is_array( $raw ) || empty( $raw ) ) {
        return [];
    }

    // Categorías de atributos (para obtener los labels bonitos).
    if ( ! function_exists( 'imco_get_attribute_categories' ) ) {
        return [];
    }

    $cats = imco_get_attribute_categories();
    if ( empty( $cats ) || ! is_array( $cats ) ) {
        return [];
    }

    // Indexamos por id: ['color' => ['label' => 'COLOR', ...], ...]
    $indexed = [];
    foreach ( $cats as $cat ) {
        if ( empty( $cat['id'] ) ) {
            continue;
        }
        $id = $cat['id'];
        $indexed[ $id ] = $cat;
    }

    $result = [];

    // Recorremos lo que está guardado en el producto.
    foreach ( $raw as $cat_id => $values ) {

        $cat_id = sanitize_key( $cat_id );

        if ( ! isset( $indexed[ $cat_id ] ) ) {
            // Atributo que ya no existe en la configuración global.
            continue;
        }

        // Normalizar a array.
        if ( ! is_array( $values ) ) {
            $values = [ $values ];
        }

        $values = array_filter(
            array_map( 'sanitize_text_field', $values ),
            function( $v ) {
                return '' !== $v;
            }
        );

        if ( empty( $values ) ) {
            continue;
        }

        $label = ! empty( $indexed[ $cat_id ]['label'] )
            ? $indexed[ $cat_id ]['label']
            : $cat_id;

        $value_str = implode( ', ', $values );

        $result[] = [
            'label' => $label,
            'value' => $value_str,
        ];
    }

    return $result;
}

/**
 * Shortcode principal: [imco_specs_table]
 *
 * Se puede usar en la descripción larga o corta del producto:
 *
 *   [imco_specs_table]
 *
 * Luego puedes escribir debajo tu texto normal de descripción.
 *
 * @return string
 */
function imco_specs_table_shortcode() {

    if ( ! function_exists( 'wc_get_product' ) ) {
        return '';
    }

    global $product;

    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
    }

    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $product_id = $product->get_id();

    // ---- Filas base: nombre y precio ----.
    $rows = [];

    $rows[] = [
        'label' => __( 'Producto', 'imco-filter' ),
        'value' => $product->get_name(),
    ];

    $price = $product->get_price();
    if ( '' !== $price ) {
        $rows[] = [
            'label' => __( 'Precio', 'imco-filter' ),
            'value' => wc_price( $price ),
        ];
    }

    // ---- Atributos IMCO ----.
    $imco_pairs = imco_get_product_imco_attribute_pairs( $product_id );
    if ( ! empty( $imco_pairs ) ) {
        $rows = array_merge( $rows, $imco_pairs );
    }

    // Si no hay nada que mostrar, no devolvemos tabla.
    if ( empty( $rows ) ) {
        return '';
    }

    ob_start();
    ?>
    <table class="imco-specs-table">
        <tbody>
        <?php foreach ( $rows as $row ) : ?>
            <tr>
                <th><?php echo esc_html( $row['label'] ); ?></th>
                <td><?php echo esc_html( $row['value'] ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php

    return ob_get_clean();
}

// Registrar el shortcode.
add_shortcode( 'imco_specs_table', 'imco_specs_table_shortcode' );
