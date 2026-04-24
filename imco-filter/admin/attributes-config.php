<?php
/**
 * Administración de categorías de atributos IMCO (sistema propio, NO WooCommerce).
 *
 * - Las categorías se guardan en la opción 'imco_attribute_categories'.
 * - Cada categoría tiene:
 *   - id               (slug interno, único)
 *   - label            (nombre visible)
 *   - type             (select | checkbox | buttons | colors)
 *   - options          (array de strings)
 *   - option_columns   (int, columnas para las variaciones de esta categoría)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Devuelve las categorías de atributos IMCO.
 *
 * @return array
 */
function imco_get_attribute_categories()
{
    $stored = get_option('imco_attribute_categories', []);

    if (!is_array($stored)) {
        $stored = [];
    }

    $result = [];

    foreach ($stored as $id => $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = sanitize_key($id);
        if ('' === $id) {
            continue;
        }

        $label = isset($row['label']) ? (string) $row['label'] : $id;
        $type = isset($row['type']) ? (string) $row['type'] : 'select';

        // NUEVO: Agregamos 'colors' a la lista de permitidos
        if (!in_array($type, ['select', 'checkbox', 'buttons', 'colors'], true)) {
            $type = 'select';
        }

        $options = [];
        if (isset($row['options']) && is_array($row['options'])) {
            foreach ($row['options'] as $opt) {
                $opt = trim((string) $opt);
                if ('' !== $opt) {
                    $options[] = $opt;
                }
            }
        }

        $option_columns = isset($row['option_columns']) ? (int) $row['option_columns'] : 0;
        if ($option_columns < 0) {
            $option_columns = 0;
        } elseif ($option_columns > 6) {
            $option_columns = 6;
        }

        $result[] = [
            'id' => $id,
            'label' => $label,
            'type' => $type,
            'options' => $options,
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
function imco_set_attribute_categories($categories)
{
    $to_store = [];

    if (is_array($categories)) {
        foreach ($categories as $cat) {
            if (!is_array($cat)) {
                continue;
            }

            $id = isset($cat['id']) ? sanitize_key($cat['id']) : '';
            if ('' === $id) {
                continue;
            }

            $label = isset($cat['label']) ? sanitize_text_field($cat['label']) : $id;

            $type = isset($cat['type']) ? sanitize_text_field($cat['type']) : 'select';
            // NUEVO: Agregamos 'colors' a la lista de permitidos
            if (!in_array($type, ['select', 'checkbox', 'buttons', 'colors'], true)) {
                $type = 'select';
            }

            $options = [];
            if (isset($cat['options']) && is_array($cat['options'])) {
                foreach ($cat['options'] as $opt) {
                    $opt = trim((string) $opt);
                    if ('' !== $opt) {
                        $options[] = $opt;
                    }
                }
            }

            $option_columns = isset($cat['option_columns']) ? (int) $cat['option_columns'] : 0;
            if ($option_columns < 0) {
                $option_columns = 0;
            } elseif ($option_columns > 6) {
                $option_columns = 6;
            }

            $to_store[$id] = [
                'label' => $label,
                'type' => $type,
                'options' => $options,
                'option_columns' => $option_columns,
            ];
        }
    }

    update_option('imco_attribute_categories', $to_store);
}

/**
 * Renderiza la página de administración de categorías de atributos IMCO.
 */
function imco_render_attribute_categories_page()
{

    // 1) Cargar categorías actuales
    $categories = imco_get_attribute_categories();

    // 2) Borrado
    if (isset($_GET['imco_delete_attribute']) && isset($_GET['_wpnonce'])) {
        $delete_id = sanitize_key(wp_unslash($_GET['imco_delete_attribute']));

        if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'imco_delete_attribute_' . $delete_id)) {

            $new = [];
            foreach ($categories as $cat) {
                if ($cat['id'] !== $delete_id) {
                    $new[] = $cat;
                }
            }

            imco_set_attribute_categories($new);
            $categories = $new;

            echo '<div class="notice notice-success is-dismissible"><p>';
            esc_html_e('Categoría de atributos eliminada correctamente.', 'imco-filter');
            echo '</p></div>';
        }
    }

    // 3) Guardar / crear categoría (formulario superior)
    $editing_id = '';
    $editing_data = null;

    if (
        isset($_POST['imco_attribute_category_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['imco_attribute_category_nonce'])), 'imco_save_attribute_category')
    ) {
        $name = isset($_POST['imco_cat_name']) ? sanitize_text_field(wp_unslash($_POST['imco_cat_name'])) : '';
        $internal_id = isset($_POST['imco_cat_internal_id']) ? sanitize_key(wp_unslash($_POST['imco_cat_internal_id'])) : '';
        $type = isset($_POST['imco_cat_type']) ? sanitize_text_field(wp_unslash($_POST['imco_cat_type'])) : 'select';
        $options_raw = isset($_POST['imco_cat_options']) ? wp_unslash($_POST['imco_cat_options']) : '';
        $opt_columns = isset($_POST['imco_cat_option_columns']) ? (int) $_POST['imco_cat_option_columns'] : 0;
        $original_id = isset($_POST['imco_original_id']) ? sanitize_key(wp_unslash($_POST['imco_original_id'])) : '';

        if ('' === $internal_id && '' !== $name) {
            $internal_id = sanitize_title($name);
        }

        // NUEVO: Agregamos 'colors'
        if (!in_array($type, ['select', 'checkbox', 'buttons', 'colors'], true)) {
            $type = 'select';
        }

        $opt_columns = max(0, min(6, $opt_columns));

        $options_list = [];
        if (is_string($options_raw) && $options_raw !== '') {
            $parts = explode(',', $options_raw);
            foreach ($parts as $part) {
                $part = trim((string) $part);
                if ('' !== $part) {
                    $options_list[] = $part;
                }
            }
        }

        if ('' !== $internal_id && '' !== $name) {
            $found = false;

            if ('' !== $original_id) {
                foreach ($categories as &$cat) {
                    if ($cat['id'] === $original_id) {
                        $cat['id'] = $internal_id;
                        $cat['label'] = $name;
                        $cat['type'] = $type;
                        $cat['options'] = $options_list;
                        $cat['option_columns'] = $opt_columns;
                        $found = true;
                        break;
                    }
                }
                unset($cat);
            }

            if (!$found) {
                foreach ($categories as &$cat) {
                    if ($cat['id'] === $internal_id) {
                        $cat['label'] = $name;
                        $cat['type'] = $type;
                        $cat['options'] = $options_list;
                        $cat['option_columns'] = $opt_columns;
                        $found = true;
                        break;
                    }
                }
                unset($cat);
            }

            if (!$found) {
                $categories[] = [
                    'id' => $internal_id,
                    'label' => $name,
                    'type' => $type,
                    'options' => $options_list,
                    'option_columns' => $opt_columns,
                ];
            }

            imco_set_attribute_categories($categories);

            echo '<div class="notice notice-success is-dismissible"><p>';
            esc_html_e('Categoría de atributos guardada correctamente.', 'imco-filter');
            echo '</p></div>';
        }
    }

    // 4) Modo edición: cargar datos en el formulario
    if (isset($_GET['edit_attribute'])) {
        $editing_id = sanitize_key(wp_unslash($_GET['edit_attribute']));

        foreach ($categories as $cat) {
            if ($cat['id'] === $editing_id) {
                $editing_data = $cat;
                break;
            }
        }
    }

    $form_name = $editing_data ? $editing_data['label'] : '';
    $form_internal_id = $editing_data ? $editing_data['id'] : '';
    $form_type = $editing_data ? $editing_data['type'] : 'select';
    $form_options = $editing_data ? implode(', ', $editing_data['options']) : '';
    $form_columns = $editing_data ? (int) $editing_data['option_columns'] : 0;
    ?>

    <div class="wrap imco-modern-admin">
        <h1><?php esc_html_e('Categorías de atributos - IMCO Filter', 'imco-filter'); ?></h1>

        <div class="imco-modern-card">
            <h2><?php echo $editing_data ? esc_html__('Editar categoría de atributos', 'imco-filter') : esc_html__('Añadir nueva categoría', 'imco-filter'); ?>
            </h2>

            <form method="post">
                <?php wp_nonce_field('imco_save_attribute_category', 'imco_attribute_category_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_cat_name"><?php esc_html_e('Nombre de la categoría', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="imco_cat_name" name="imco_cat_name"
                                    value="<?php echo esc_attr($form_name); ?>"
                                    placeholder="<?php esc_attr_e('Ejemplo: Color, Material de la correa…', 'imco-filter'); ?>" />
                                <p class="description">
                                    <?php esc_html_e('Nombre que verás en el panel de productos y en el filtro del frontend.', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_cat_internal_id"><?php esc_html_e('ID interno (opcional)', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="imco_cat_internal_id" name="imco_cat_internal_id"
                                    value="<?php echo esc_attr($form_internal_id); ?>"
                                    placeholder="<?php esc_attr_e('Ejemplo: color, correa, diametro…', 'imco-filter'); ?>" />
                                <p class="description">
                                    <?php esc_html_e('Si lo dejas vacío, se generará automáticamente a partir del nombre. Solo letras minúsculas, números y guiones.', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_cat_type"><?php esc_html_e('Tipo de selección (frontend)', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <select id="imco_cat_type" name="imco_cat_type">
                                    <option value="select" <?php selected($form_type, 'select'); ?>>
                                        <?php esc_html_e('Lista de selección (una sola opción)', 'imco-filter'); ?>
                                    </option>
                                    <option value="checkbox" <?php selected($form_type, 'checkbox'); ?>>
                                        <?php esc_html_e('Casillas de selección múltiple (Checkbox)', 'imco-filter'); ?>
                                    </option>
                                    <option value="buttons" <?php selected($form_type, 'buttons'); ?>>
                                        <?php esc_html_e('Píldoras / Botones (Chips)', 'imco-filter'); ?>
                                    </option>
                                    <!-- NUEVA OPCIÓN AÑADIDA -->
                                    <option value="colors" <?php selected($form_type, 'colors'); ?>>
                                        <?php esc_html_e('Círculos de Color (Color Swatches)', 'imco-filter'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Esto NO afecta al backend (siempre verás casillas allí). Solo define cómo se verá el filtro para el usuario en la tienda.', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_cat_option_columns"><?php esc_html_e('Variaciones por fila', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="imco_cat_option_columns" name="imco_cat_option_columns" min="0"
                                    max="6" value="<?php echo esc_attr($form_columns); ?>" style="width:100px;" />
                                <p class="description">
                                    <?php esc_html_e('Número de variaciones que se mostrarán por fila en el frontend. 0 = usar valor por defecto.', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label
                                    for="imco_cat_options"><?php esc_html_e('Opciones (separadas por comas)', 'imco-filter'); ?></label>
                            </th>
                            <td>
                                <textarea id="imco_cat_options" name="imco_cat_options" rows="4"
                                    placeholder="<?php esc_attr_e('Ejemplo: Negro, Blanco, Plateado, Dorado', 'imco-filter'); ?>"><?php echo esc_textarea($form_options); ?></textarea>
                                <p class="description">
                                    <?php esc_html_e('Escribe las diferentes opciones que tendrá esta categoría, separadas por comas.', 'imco-filter'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php if ($editing_data): ?>
                    <input type="hidden" name="imco_original_id" value="<?php echo esc_attr($form_internal_id); ?>" />
                <?php endif; ?>

                <p class="submit">
                    <?php submit_button($editing_data ? __('Guardar cambios', 'imco-filter') : __('Guardar categoría', 'imco-filter'), 'primary', 'submit', false); ?>
                    <?php if ($editing_data): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=imco-attribute-categories')); ?>" class="button"
                            style="margin-left: 10px; border-radius: 8px;">Cancelar</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <div class="imco-modern-card" style="padding: 0; overflow: hidden; border: none; box-shadow: none;">
            <h2
                style="padding: 24px 24px 16px; margin: 0; background: #fff; border-radius: 16px 16px 0 0; border: 1px solid #f2f2f7; border-bottom: none;">
                <?php esc_html_e('Categorías de atributos existentes', 'imco-filter'); ?>
            </h2>

            <table class="widefat fixed striped" style="margin: 0; border-radius: 0 0 16px 16px; border-top: none;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'imco-filter'); ?></th>
                        <th><?php esc_html_e('Nombre', 'imco-filter'); ?></th>
                        <th><?php esc_html_e('Tipo (frontend)', 'imco-filter'); ?></th>
                        <th><?php esc_html_e('Opciones', 'imco-filter'); ?></th>
                        <th><?php esc_html_e('Variaciones por fila', 'imco-filter'); ?></th>
                        <th style="width: 150px; text-align: right; padding-right: 24px;">
                            <?php esc_html_e('Acciones', 'imco-filter'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" style="padding: 32px; text-align: center; color: #86868b;">
                                <?php esc_html_e('Aún no hay categorías de atributos. Crea la primera con el formulario de arriba.', 'imco-filter'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(add_query_arg(['page' => 'imco-attribute-categories', 'edit_attribute' => $cat['id']], admin_url('admin.php'))); ?>"
                                            style="color: #1d1d1f; text-decoration: none;">
                                            <?php echo esc_html($cat['id']); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><strong><?php echo esc_html($cat['label']); ?></strong></td>
                                <td>
                                    <?php
                                    switch ($cat['type']) {
                                        case 'checkbox':
                                            echo '<span style="background: #f2f2f7; padding: 4px 8px; border-radius: 6px; font-size: 12px; color: #1d1d1f;">' . esc_html__('Checkbox', 'imco-filter') . '</span>';
                                            break;
                                        case 'buttons':
                                            echo '<span style="background: #e5f0ff; padding: 4px 8px; border-radius: 6px; font-size: 12px; color: #0066cc;">' . esc_html__('Píldoras (Chips)', 'imco-filter') . '</span>';
                                            break;
                                        case 'colors':
                                            echo '<span style="background: #fff0f5; padding: 4px 8px; border-radius: 6px; font-size: 12px; color: #d6006e;">' . esc_html__('Círculos de Color', 'imco-filter') . '</span>';
                                            break;
                                        default:
                                            echo '<span style="background: #f2f2f7; padding: 4px 8px; border-radius: 6px; font-size: 12px; color: #1d1d1f;">' . esc_html__('Select', 'imco-filter') . '</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td><span
                                        style="color: #86868b; font-size: 13px;"><?php echo esc_html(implode(', ', $cat['options'])); ?></span>
                                </td>
                                <td><?php echo esc_html((string) $cat['option_columns']); ?></td>
                                <td class="imco-actions" style="text-align: right; padding-right: 24px;">
                                    <?php
                                    $edit_url = add_query_arg(['page' => 'imco-attribute-categories', 'edit_attribute' => $cat['id']], admin_url('admin.php'));
                                    $delete_url = wp_nonce_url(add_query_arg(['page' => 'imco-attribute-categories', 'imco_delete_attribute' => $cat['id']], admin_url('admin.php')), 'imco_delete_attribute_' . $cat['id']);
                                    ?>
                                    <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Editar', 'imco-filter'); ?></a>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo esc_url($delete_url); ?>" class="delete-link"
                                        onclick="return confirm('<?php echo esc_js(__('¿Seguro que quieres eliminar esta categoría?', 'imco-filter')); ?>');"><?php esc_html_e('Eliminar', 'imco-filter'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}