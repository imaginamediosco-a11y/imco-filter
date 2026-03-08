<?php
/**
 * Metabox básico de atributos de producto para IMCO Filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registra el metabox en la pantalla de producto.
 */
function imco_add_product_meta_box() {
    add_meta_box(
        'imco_product_attributes',
        __( 'IMCO - Atributos del producto', 'imco-filter' ),
        'imco_render_product_meta_box',
        'product',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'imco_add_product_meta_box' );

/**
 * Renderiza el metabox en la edición de producto.
 *
 * @param WP_Post $post
 */
function imco_render_product_meta_box( $post ) {

    if ( ! function_exists( 'imco_get_attribute_categories' ) ) {
        echo '<p>' . esc_html__( 'No se encontraron categorías de atributos configuradas.', 'imco-filter' ) . '</p>';
        return;
    }

    wp_nonce_field( 'imco_save_product_attributes', 'imco_product_attributes_nonce' );

    $categories = imco_get_attribute_categories();
    $saved      = get_post_meta( $post->ID, '_imco_product_attributes', true );

    if ( ! is_array( $saved ) ) {
        $saved = [];
    }

    if ( empty( $categories ) ) {
        echo '<p>' . esc_html__( 'Aún no has creado categorías de atributos en IMCO Filter.', 'imco-filter' ) . '</p>';
        return;
    }
    ?>

    <div class="imco-product-attributes-wrapper">

        <div class="imco-product-attributes-grid">
            <?php foreach ( $categories as $cat ) :

                $cat_id    = isset( $cat['id'] ) ? $cat['id'] : '';
                $cat_label = isset( $cat_id[0] ) ? $cat['label'] : '';
                $options   = ( isset( $cat['options'] ) && is_array( $cat['options'] ) ) ? $cat['options'] : [];

                if ( empty( $cat_id ) ) {
                    continue;
                }

                $selected_values = [];
                if ( isset( $saved[ $cat_id ] ) ) {
                    $val = $saved[ $cat_id ];
                    if ( is_array( $val ) ) {
                        $selected_values = array_map( 'sanitize_text_field', $val );
                    } else {
                        $selected_values = [ sanitize_text_field( $val ) ];
                    }
                }
                ?>
                <div class="imco-product-attribute-box">
                    <h4 class="imco-product-attribute-title">
                        <?php echo esc_html( $cat_label ); ?>
                    </h4>

                    <div class="imco-product-attribute-options">
                        <?php if ( ! empty( $options ) ) : ?>
                            <?php foreach ( $options as $option ) : ?>
                                <?php
                                $checked = in_array( $option, $selected_values, true ) ? 'checked="checked"' : '';
                                ?>
                                <label class="imco-product-attribute-option">
                                    <input
                                        type="checkbox"
                                        name="imco_product_attributes[<?php echo esc_attr( $cat_id ); ?>][]"
                                        value="<?php echo esc_attr( $option ); ?>"
                                        <?php echo $checked; ?>
                                    />
                                    <span><?php echo esc_html( $option ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p style="margin:0;font-size:11px;color:#6b7280;">
                                <?php esc_html_e( 'Esta categoría aún no tiene opciones definidas.', 'imco-filter' ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="imco-product-attributes-helper">
            <?php esc_html_e( 'Las categorías se muestran en tarjetas organizadas en columnas. Todas las opciones se asignan como casillas de verificación.', 'imco-filter' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Guarda los atributos del producto al actualizar.
 *
 * @param int $post_id
 */
function imco_save_product_meta_box( $post_id ) {

    // Comprobar nonce.
    if ( ! isset( $_POST['imco_product_attributes_nonce'] ) ||
         ! wp_verify_nonce( $_POST['imco_product_attributes_nonce'], 'imco_save_product_attributes' )
    ) {
        return;
    }

    // Evitar autosaves, revisiones, etc.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['imco_product_attributes'] ) || ! is_array( $_POST['imco_product_attributes'] ) ) {
        delete_post_meta( $post_id, '_imco_product_attributes' );
        return;
    }

    $raw = wp_unslash( $_POST['imco_product_attributes'] );
    $clean = [];

    foreach ( $raw as $cat_id => $values ) {
        $cat_id = sanitize_key( $cat_id );

        if ( is_array( $values ) ) {
            $vals = array_filter( array_map( 'sanitize_text_field', $values ) );
        } else {
            $vals = [ sanitize_text_field( $values ) ];
        }

        if ( ! empty( $vals ) ) {
            $clean[ $cat_id ] = $vals;
        }
    }

    if ( ! empty( $clean ) ) {
        update_post_meta( $post_id, '_imco_product_attributes', $clean );
    } else {
        delete_post_meta( $post_id, '_imco_product_attributes' );
    }
}
add_action( 'save_post_product', 'imco_save_product_meta_box' );
