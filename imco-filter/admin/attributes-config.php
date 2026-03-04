<?php
/**
 * Administración de categorías de atributos IMCO (sistema propio, NO WooCommerce).
 *
 * - Las categorías se guardan en la opción 'imco_attribute_categories'.
 * - Cada categoría tiene:
 *   - id               (slug interno, único)
 *   - label            (nombre visible)
 *   - type             (select | checkbox | buttons)
 *   - options          (array de strings)
 *   - option_columns   (int, columnas para las variaciones de esta categoría)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve las categorías de atributos IMCO.
 *
 * Estructura devuelta (lista numérica):
 * [
 *   [
 *     'id'             => 'coloresderejojeria',
 *     'label'          => 'Color',
 *     'type'           => 'checkbox',
 *     'options'        => [ 'Negro', 'Blanco', 'Rojo' ],
 *     'option_columns' => 0,
 *   ],
 *   ...
 * ]
 *
 * Internamente en la BD se guarda como array asociativo:
 * [
 *   'coloresderejojeria' => [
 *      'label'          => 'Color',
 *      'type'           => 'checkbox',
 *      'options'        => [ ... ],
 *      'option_columns' => 0,
 *   ],
 *   ...
 * ]
 *
 * @return array
 */
function imco_get_attribute_categories() {
    $stored = get_option( 'imco_attribute_categories', [] );

    if ( ! is_array( $stored ) ) {
        $stored = [];
    }

    $result = [];

    foreach ( $stored as $id => $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $id = sanitize_key( $id );
        if ( '' === $id ) {
            continue;
        }

        $label = isset( $row['label'] ) ? (string) $row['label'] : $id;
        $type  = isset( $row['type'] ) ? (string) $row['type'] : 'select';

        if ( ! in_array( $type, [ 'select', 'checkbox', 'buttons' ], true ) ) {
            $type = 'select';
        }

        $options = [];
        if ( isset( $row['options'] ) && is_array( $row['options'] ) ) {
            foreach ( $row['options'] as $opt ) {
                $opt = trim( (string) $opt );
                if ( '' !== $opt ) {
                    $options[] = $opt;
                }
            }
        }

        $option_columns = isset( $row['option_columns'] ) ? (int) $row['option_columns'] : 0;
        if ( $option_columns < 0 ) {
            $option_columns = 0;
        } elseif ( $option_columns > 6 ) {
            $option_columns = 6;
        }

        $result[] = [
            'id'             => $id,
            'label'          => $label,
            'type'           => $type,
            'options'        => $options,
            'option_columns' => $option_columns,
        ];
    }

    return $result;
}

/**
 * Guarda la lista de categorías en la opción, usando array asociativo por id.
 *
 * @param array $categories Lista numérica con ['id','label','type','options','option_columns'].
 */
function imco_set_attribute_categories( $categories ) {
    $to_store = [];

    if ( is_array( $categories ) ) {
        foreach ( $categories as $cat ) {
            if ( ! is_array( $cat ) ) {
                continue;
            }

            $id = isset( $cat['id'] ) ? sanitize_key( $cat['id'] ) : '';
            if ( '' === $id ) {
                continue;
            }

            $label = isset( $cat['label'] ) ? sanitize_text_field( $cat['label'] ) : $id;

            $type = isset( $cat['type'] ) ? sanitize_text_field( $cat['type'] ) : 'select';
            if ( ! in_array( $type, [ 'select', 'checkbox', 'buttons' ], true ) ) {
                $type = 'select';
            }

            $options = [];
            if ( isset( $cat['options'] ) && is_array( $cat['options'] ) ) {
                foreach ( $cat['options'] as $opt ) {
                    $opt = trim( (string) $opt );
                    if ( '' !== $opt ) {
                        $options[] = $opt;
                    }
                }
            }

            $option_columns = isset( $cat['option_columns'] ) ? (int) $cat['option_columns'] : 0;
            if ( $option_columns < 0 ) {
                $option_columns = 0;
            } elseif ( $option_columns > 6 ) {
                $option_columns = 6;
            }

            $to_store[ $id ] = [
                'label'          => $label,
                'type'           => $type,
                'options'        => $options,
                'option_columns' => $option_columns,
            ];
        }
    }

    update_option( 'imco_attribute_categories', $to_store );
}

/**
 * Renderiza la página de administración de categorías de atributos IMCO.
 *
 * Callback: imco_render_attribute_categories_page
 */
function imco_render_attribute_categories_page() {

    // 1) Cargar categorías actuales
    $categories = imco_get_attribute_categories();

    // 2) Borrado
    if ( isset( $_GET['imco_delete_attribute'] ) && isset( $_GET['_wpnonce'] ) ) {
        $delete_id = sanitize_key( wp_unslash( $_GET['imco_delete_attribute'] ) );

        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'imco_delete_attribute_' . $delete_id ) ) {

            $new = [];
            foreach ( $categories as $cat ) {
                if ( $cat['id'] !== $delete_id ) {
                    $new[] = $cat;
                }
            }

            imco_set_attribute_categories( $new );
            $categories = $new;

            echo '<div class="notice notice-success is-dismissible"><p>';
            esc_html_e( 'Categoría de atributos eliminada correctamente.', 'imco-filter' );
            echo '</p></div>';
        }
    }

    // 3) Guardar / crear categoría (formulario superior)
    $editing_id   = '';
    $editing_data = null;

    if ( isset( $_POST['imco_attribute_category_nonce'] )
         && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['imco_attribute_category_nonce'] ) ), 'imco_save_attribute_category' )
    ) {
        $name         = isset( $_POST['imco_cat_name'] ) ? sanitize_text_field( wp_unslash( $_POST['imco_cat_name'] ) ) : '';
        $internal_id  = isset( $_POST['imco_cat_internal_id'] ) ? sanitize_key( wp_unslash( $_POST['imco_cat_internal_id'] ) ) : '';
        $type         = isset( $_POST['imco_cat_type'] ) ? sanitize_text_field( wp_unslash( $_POST['imco_cat_type'] ) ) : 'select';
        $options_raw  = isset( $_POST['imco_cat_options'] ) ? wp_unslash( $_POST['imco_cat_options'] ) : '';
        $opt_columns  = isset( $_POST['imco_cat_option_columns'] ) ? (int) $_POST['imco_cat_option_columns'] : 0;
        $original_id  = isset( $_POST['imco_original_id'] ) ? sanitize_key( wp_unslash( $_POST['imco_original_id'] ) ) : '';

        if ( '' === $internal_id && '' !== $name ) {
            $internal_id = sanitize_title( $name );
        }

        if ( ! in_array( $type, [ 'select', 'checkbox', 'buttons' ], true ) ) {
            $type = 'select';
        }

        $opt_columns = max( 0, min( 6, $opt_columns ) );

        $options_list = [];
        if ( is_string( $options_raw ) && $options_raw !== '' ) {
            $parts = explode( ',', $options_raw );
            foreach ( $parts as $part ) {
                $part = trim( (string) $part );
                if ( '' !== $part ) {
                    $options_list[] = $part;
                }
            }
        }

        if ( '' !== $internal_id && '' !== $name ) {
            $found = false;

            // Si venimos en modo edición (original_id) y ha cambiado el ID, actualizamos por ese ID viejo.
            if ( '' !== $original_id ) {
                foreach ( $categories as &$cat ) {
                    if ( $cat['id'] === $original_id ) {
                        $cat['id']             = $internal_id;
                        $cat['label']          = $name;
                        $cat['type']           = $type;
                        $cat['options']        = $options_list;
                        $cat['option_columns'] = $opt_columns;
                        $found                 = true;
                        break;
                    }
                }
                unset( $cat );
            }

            // Si no se encontró por original_id, buscamos por el ID actual (sobreescribir).
            if ( ! $found ) {
                foreach ( $categories as &$cat ) {
                    if ( $cat['id'] === $internal_id ) {
                        $cat['label']          = $name;
                        $cat['type']           = $type;
                        $cat['options']        = $options_list;
                        $cat['option_columns'] = $opt_columns;
                        $found                 = true;
                        break;
                    }
                }
                unset( $cat );
            }

            // Si no existe, la añadimos
            if ( ! $found ) {
                $categories[] = [
                    'id'             => $internal_id,
                    'label'          => $name,
                    'type'           => $type,
                    'options'        => $options_list,
                    'option_columns' => $opt_columns,
                ];
            }

            imco_set_attribute_categories( $categories );

            echo '<div class="notice notice-success is-dismissible"><p>';
            esc_html_e( 'Categoría de atributos guardada correctamente.', 'imco-filter' );
            echo '</p></div>';
        }
    }

    // 4) Modo edición: cargar datos en el formulario
    if ( isset( $_GET['edit_attribute'] ) ) {
        $editing_id = sanitize_key( wp_unslash( $_GET['edit_attribute'] ) );

        foreach ( $categories as $cat ) {
            if ( $cat['id'] === $editing_id ) {
                $editing_data = $cat;
                break;
            }
        }
    }

    $form_name        = $editing_data ? $editing_data['label'] : '';
    $form_internal_id = $editing_data ? $editing_data['id'] : '';
    $form_type        = $editing_data ? $editing_data['type'] : 'select';
    $form_options     = $editing_data ? implode( ', ', $editing_data['options'] ) : '';
    $form_columns     = $editing_data ? (int) $editing_data['option_columns'] : 0;
    ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Categorías de atributos - IMCO Filter', 'imco-filter' ); ?></h1>

        <h2><?php echo $editing_data ? esc_html__( 'Añadir / editar categoría de atributos', 'imco-filter' ) : esc_html__( 'Añadir categoría de atributos', 'imco-filter' ); ?></h2>

        <form method="post">
            <?php wp_nonce_field( 'imco_save_attribute_category', 'imco_attribute_category_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="imco_cat_name"><?php esc_html_e( 'Nombre de la categoría', 'imco-filter' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="imco_cat_name"
                                   name="imco_cat_name"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $form_name ); ?>"
                                   placeholder="<?php esc_attr_e( 'Ejemplo: Color, Material de la correa…', 'imco-filter' ); ?>" />
                            <p class="description">
                                <?php esc_html_e( 'Nombre que verás en el panel de productos y en el filtro del frontend.', 'imco-filter' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="imco_cat_internal_id"><?php esc_html_e( 'ID interno (opcional)', 'imco-filter' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="imco_cat_internal_id"
                                   name="imco_cat_internal_id"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $form_internal_id ); ?>"
                                   placeholder="<?php esc_attr_e( 'Ejemplo: color, correa, diametro…', 'imco-filter' ); ?>" />
                            <p class="description">
                                <?php esc_html_e( 'Si lo dejas vacío, se generará automáticamente a partir del nombre. Solo letras minúsculas, números y guiones.', 'imco-filter' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="imco_cat_type"><?php esc_html_e( 'Tipo de selección (frontend)', 'imco-filter' ); ?></label>
                        </th>
                        <td>
                            <select id="imco_cat_type" name="imco_cat_type">
                                <option value="select" <?php selected( $form_type, 'select' ); ?>>
                                    <?php esc_html_e( 'Lista de selección (una sola opción)', 'imco-filter' ); ?>
                                </option>
                                <option value="checkbox" <?php selected( $form_type, 'checkbox' ); ?>>
                                    <?php esc_html_e( 'Casillas de selección múltiple', 'imco-filter' ); ?>
                                </option>
                                <option value="buttons" <?php selected( $form_type, 'buttons' ); ?>>
                                    <?php esc_html_e( 'Botones (chips) de selección', 'imco-filter' ); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Esto NO afecta al backend (siempre verás casillas allí). Solo define cómo se verá el filtro para el usuario en la tienda.', 'imco-filter' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="imco_cat_option_columns"><?php esc_html_e( 'Columnas de variaciones', 'imco-filter' ); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="imco_cat_option_columns"
                                   name="imco_cat_option_columns"
                                   min="0"
                                   max="6"
                                   value="<?php echo esc_attr( $form_columns ); ?>"
                                   style="width:80px;" />
                            <p class="description">
                                <?php esc_html_e( 'Número de columnas para las opciones de esta categoría en el frontend. 0 = usar valor por defecto.', 'imco-filter' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="imco_cat_options"><?php esc_html_e( 'Opciones (separadas por comas)', 'imco-filter' ); ?></label>
                        </th>
                        <td>
                            <textarea id="imco_cat_options"
                                      name="imco_cat_options"
                                      rows="4"
                                      class="large-text"
                                      placeholder="<?php esc_attr_e( 'Ejemplo: Negro, Blanco, Plateado, Dorado', 'imco-filter' ); ?>"><?php echo esc_textarea( $form_options ); ?></textarea>
                            <p class="description">
                                <?php esc_html_e( 'Escribe las diferentes opciones que tendrá esta categoría, separadas por comas.', 'imco-filter' ); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if ( $editing_data ) : ?>
                <input type="hidden" name="imco_original_id" value="<?php echo esc_attr( $form_internal_id ); ?>" />
            <?php endif; ?>

            <?php submit_button( $editing_data ? __( 'Guardar cambios', 'imco-filter' ) : __( 'Guardar categoría', 'imco-filter' ) ); ?>
        </form>

        <hr />

        <h2><?php esc_html_e( 'Categorías de atributos existentes', 'imco-filter' ); ?></h2>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'ID', 'imco-filter' ); ?></th>
                    <th><?php esc_html_e( 'Nombre', 'imco-filter' ); ?></th>
                    <th><?php esc_html_e( 'Tipo (frontend)', 'imco-filter' ); ?></th>
                    <th><?php esc_html_e( 'Opciones', 'imco-filter' ); ?></th>
                    <th><?php esc_html_e( 'Columnas', 'imco-filter' ); ?></th>
                    <th><?php esc_html_e( 'Acciones', 'imco-filter' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( empty( $categories ) ) : ?>
                <tr>
                    <td colspan="6">
                        <?php esc_html_e( 'Aún no hay categorías de atributos. Crea la primera con el formulario de arriba.', 'imco-filter' ); ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ( $categories as $cat ) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'imco-attribute-categories', 'edit_attribute' => $cat['id'] ], admin_url( 'admin.php' ) ) ); ?>">
                                <?php echo esc_html( $cat['id'] ); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html( $cat['label'] ); ?></td>
                        <td>
                            <?php
                            switch ( $cat['type'] ) {
                                case 'checkbox':
                                    esc_html_e( 'Selección múltiple (checkbox)', 'imco-filter' );
                                    break;
                                case 'buttons':
                                    esc_html_e( 'Botones (chips)', 'imco-filter' );
                                    break;
                                default:
                                    esc_html_e( 'Lista de selección (select)', 'imco-filter' );
                                    break;
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html( implode( ', ', $cat['options'] ) ); ?></td>
                        <td><?php echo esc_html( (string) $cat['option_columns'] ); ?></td>
                        <td>
    <?php
    // URL para editar esta categoría
    $edit_url = add_query_arg(
        [
            'page'           => 'imco-attribute-categories',
            'edit_attribute' => $cat['id'],
        ],
        admin_url( 'admin.php' )
    );

    // URL para eliminar esta categoría
    $delete_url = wp_nonce_url(
        add_query_arg(
            [
                'page'                 => 'imco-attribute-categories',
                'imco_delete_attribute'=> $cat['id'],
            ],
            admin_url( 'admin.php' )
        ),
        'imco_delete_attribute_' . $cat['id']
    );
    ?>
    <a href="<?php echo esc_url( $edit_url ); ?>">
        <?php esc_html_e( 'Editar', 'imco-filter' ); ?>
    </a>
    &nbsp;|&nbsp;
    <a href="<?php echo esc_url( $delete_url ); ?>"
       onclick="return confirm('<?php echo esc_js( __( '¿Seguro que quieres eliminar esta categoría?', 'imco-filter' ) ); ?>');">
        <?php esc_html_e( 'Eliminar', 'imco-filter' ); ?>
    </a>
</td>

                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
