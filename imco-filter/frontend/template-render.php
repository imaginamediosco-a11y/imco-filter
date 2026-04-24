<?php
/**
 * Render de filtros y productos del FRONTEND para IMCO Filter.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Función auxiliar para traducir nombres de colores a códigos Hexadecimales.
 * Puedes agregar más colores aquí según los que uses en tu tienda.
 */
function imco_get_color_hex($color_name)
{
    $colors = [
        'negro' => '#000000',
        'blanco' => '#ffffff',
        'dorado' => '#d4af37',
        'plateado' => '#c0c0c0',
        'palo de rosa' => '#ffb6c1',
        'azul' => '#1e3a8a',
        'verde' => '#166534',
        'amarillo' => '#eab308',
        'cafe' => '#451a03',
        'café' => '#451a03',
        'rojo' => '#dc2626',
        'lila' => '#d8b4e2',
        'rosado' => '#f472b6',
        'naranja' => '#f97316',
    ];

    $key = strtolower(trim($color_name));
    return isset($colors[$key]) ? $colors[$key] : '#e5e5ea'; // Color por defecto si no se encuentra
}

/**
 * Devuelve, para una categoría dada, qué valores de atributos IMCO
 * están realmente en uso por los productos de esa categoría.
 */
function imco_get_available_attribute_values($category_slug = '')
{

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];

    if (!empty($category_slug)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $category_slug,
            ],
        ];
    }

    $query = new WP_Query($args);
    $used_vals = [];

    if ($query->have_posts()) {
        foreach ($query->posts as $product_id) {
            $attrs = get_post_meta($product_id, '_imco_product_attributes', true);

            if (!is_array($attrs)) {
                continue;
            }

            foreach ($attrs as $attr_key => $values) {
                if (empty($attr_key)) {
                    continue;
                }

                if (!is_array($values)) {
                    $values = [$values];
                }

                foreach ($values as $val) {
                    $val = (string) $val;
                    if ('' === $val) {
                        continue;
                    }

                    if (!isset($used_vals[$attr_key])) {
                        $used_vals[$attr_key] = [];
                    }
                    $used_vals[$attr_key][$val] = true;
                }
            }
        }
    }

    wp_reset_postdata();

    return $used_vals;
}

/**
 * Renderiza el formulario de filtros basado en las categorías registradas.
 */
function imco_render_filter_form()
{

    if (!function_exists('imco_get_attribute_categories')) {
        return;
    }

    global $imco_current_category_slug;

    $category_slug = '';
    if (isset($_GET['imco_category']) && '' !== $_GET['imco_category']) {
        $category_slug = sanitize_title(wp_unslash($_GET['imco_category']));
    } elseif (!empty($imco_current_category_slug)) {
        $category_slug = sanitize_title($imco_current_category_slug);
    }

    $available_values = imco_get_available_attribute_values($category_slug);
    $categories = imco_get_attribute_categories();
    $current_filters = isset($_GET['imco_filter']) && is_array($_GET['imco_filter'])
        ? wp_unslash($_GET['imco_filter'])
        : [];

    if (empty($categories)) {
        echo '<p>' . esc_html__('No hay categorías de atributos configuradas para filtrar.', 'imco-filter') . '</p>';
        return;
    }

    $current_page = isset($_GET['imco_page']) ? max(1, (int) $_GET['imco_page']) : 1;
    ?>

    <form method="get" class="imco-filter-form" id="imco-filter-form">
        <?php if (!empty($category_slug)): ?>
            <input type="hidden" name="imco_category" value="<?php echo esc_attr($category_slug); ?>" />
        <?php endif; ?>

        <input type="hidden" name="imco_page" id="imco-page-input" value="<?php echo esc_attr($current_page); ?>" />

        <?php foreach ($categories as $cat):

            $cat_id = isset($cat['id']) ? $cat['id'] : '';
            $cat_label = isset($cat['label']) ? $cat['label'] : $cat_id;
            $cat_type = isset($cat['type']) ? $cat['type'] : 'select';
            $options = (isset($cat['options']) && is_array($cat['options'])) ? $cat['options'] : [];

            if (empty($cat_id)) {
                continue;
            }

            $option_columns = 0;
            if (isset($cat['option_columns']) && (int) $cat['option_columns'] > 0) {
                $option_columns = (int) $cat['option_columns'];
            } else {
                $option_columns = (int) get_option('imco_attribute_options_columns', 4);
            }

            $style_attr = '';
            if ($option_columns > 0) {
                $option_columns = max(1, min(6, $option_columns));
                $style_attr = ' style="--imco-attr-option-cols:' . esc_attr($option_columns) . ';"';
            }

            $selected_values = [];
            $selected_value = '';

            if (isset($current_filters[$cat_id])) {
                if (is_array($current_filters[$cat_id])) {
                    $selected_values = array_map('sanitize_text_field', $current_filters[$cat_id]);
                } else {
                    $selected_value = sanitize_text_field($current_filters[$cat_id]);
                }
            }

            $used_for_cat = isset($available_values[$cat_id]) ? $available_values[$cat_id] : null;

            if (null === $used_for_cat && empty($selected_values) && '' === $selected_value) {
                continue;
            }

            $collapsed_class = (empty($selected_values) && '' === $selected_value) ? 'imco-collapsed' : '';
            ?>
            <div class="imco-filter-group imco-filter-type-<?php echo esc_attr($cat_type); ?> <?php echo esc_attr($collapsed_class); ?>"
                <?php echo $style_attr; ?>>
                <h4 class="imco-filter-title">
                    <span><?php echo esc_html($cat_label); ?></span>
                    <svg class="imco-filter-toggle-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </h4>

                <?php if ('checkbox' === $cat_type): ?>

                    <div class="imco-filter-options imco-filter-options-checkbox">
                        <?php foreach ($options as $option): ?>
                            <?php
                            $is_selected = in_array($option, $selected_values, true);
                            $is_available = (null === $used_for_cat) ? true : isset($used_for_cat[$option]);

                            if (!$is_available && !$is_selected) {
                                continue;
                            }
                            $checked = $is_selected ? 'checked="checked"' : '';
                            ?>
                            <label class="imco-custom-checkbox">
                                <input type="checkbox" name="imco_filter[<?php echo esc_attr($cat_id); ?>][]"
                                    value="<?php echo esc_attr($option); ?>" <?php echo $checked; ?> />
                                <span class="imco-checkbox-box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                                <span class="imco-checkbox-label"><?php echo esc_html($option); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ('buttons' === $cat_type): ?>

                    <div class="imco-filter-options imco-filter-options-buttons">
                        <?php foreach ($options as $option): ?>
                            <?php
                            // En botones (píldoras) permitimos selección múltiple igual que en checkboxes
                            $is_selected = in_array($option, $selected_values, true);
                            $is_available = (null === $used_for_cat) ? true : isset($used_for_cat[$option]);

                            if (!$is_available && !$is_selected) {
                                continue;
                            }
                            $checked = $is_selected ? 'checked="checked"' : '';
                            ?>
                            <label class="imco-filter-pill">
                                <input type="checkbox" name="imco_filter[<?php echo esc_attr($cat_id); ?>][]"
                                    value="<?php echo esc_attr($option); ?>" <?php echo $checked; ?> />
                                <span><?php echo esc_html($option); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ('colors' === $cat_type): ?>

                    <div class="imco-filter-options imco-filter-options-colors">
                        <?php foreach ($options as $option): ?>
                            <?php
                            $is_selected = in_array($option, $selected_values, true);
                            $is_available = (null === $used_for_cat) ? true : isset($used_for_cat[$option]);

                            if (!$is_available && !$is_selected) {
                                continue;
                            }
                            $checked = $is_selected ? 'checked="checked"' : '';
                            $hex_color = imco_get_color_hex($option);
                            $is_white = (strtolower($hex_color) === '#ffffff' || strtolower($hex_color) === '#fff');
                            ?>
                            <label class="imco-filter-color" title="<?php echo esc_attr($option); ?>">
                                <input type="checkbox" name="imco_filter[<?php echo esc_attr($cat_id); ?>][]"
                                    value="<?php echo esc_attr($option); ?>" <?php echo $checked; ?> />
                                <span class="imco-color-swatch <?php echo $is_white ? 'imco-color-white' : ''; ?>"
                                    style="background-color: <?php echo esc_attr($hex_color); ?>;">
                                    <svg class="imco-color-check" viewBox="0 0 24 24" fill="none"
                                        stroke="<?php echo $is_white ? '#000' : '#fff'; ?>" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <?php else:  // 'select' por defecto ?>

                    <select name="imco_filter[<?php echo esc_attr($cat_id); ?>]" class="imco-filter-select">
                        <option value=""><?php esc_html_e('Todos', 'imco-filter'); ?></option>
                        <?php foreach ($options as $option): ?>
                            <?php
                            $is_selected = ($selected_value === $option);
                            $is_available = (null === $used_for_cat) ? true : isset($used_for_cat[$option]);

                            if (!$is_available && !$is_selected) {
                                continue;
                            }
                            ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($selected_value, $option); ?>>
                                <?php echo esc_html($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="imco-filter-actions">
            <button type="submit" class="imco-filter-submit">
                <?php esc_html_e('Aplicar Filtros', 'imco-filter'); ?>
            </button>
            <a href="#" class="imco-filter-reset">
                <?php esc_html_e('Limpiar todo', 'imco-filter'); ?>
            </a>
        </div>
    </form>
    <?php
}

/**
 * Devuelve los filtros activos en un formato amigable.
 */
function imco_get_active_filters()
{
    if (!function_exists('imco_get_attribute_categories')) {
        return [];
    }
    if (!isset($_GET['imco_filter']) || !is_array($_GET['imco_filter'])) {
        return [];
    }
    $raw_filters = wp_unslash($_GET['imco_filter']);
    $categories = imco_get_attribute_categories();
    if (empty($categories)) {
        return [];
    }
    $indexed = [];
    foreach ($categories as $cat) {
        if (!empty($cat['id'])) {
            $indexed[$cat['id']] = $cat;
        }
    }
    $active = [];
    foreach ($raw_filters as $cat_id => $value) {
        $cat_id = sanitize_key($cat_id);
        if (!isset($indexed[$cat_id])) {
            continue;
        }
        $cat = $indexed[$cat_id];
        if (is_array($value)) {
            $values = array_filter(array_map('sanitize_text_field', $value));
        } else {
            $value = sanitize_text_field($value);
            $values = $value !== '' ? [$value] : [];
        }
        if (empty($values)) {
            continue;
        }
        $active[] = [
            'id' => $cat_id,
            'label' => isset($cat['label']) ? $cat['label'] : $cat_id,
            'values' => $values,
        ];
    }
    return $active;
}

/**
 * Pinta el bloque de filtros activos (chips).
 */
function imco_render_active_filters_block()
{
    $active_filters = imco_get_active_filters();
    if (empty($active_filters)) {
        return;
    }
    ?>
    <div class="imco-active-filters">
        <div class="imco-active-filters-list">
            <?php foreach ($active_filters as $group): ?>
                <?php
                $cat_label = $group['label'];
                $cat_id = $group['id'];
                foreach ($group['values'] as $val):
                    ?>
                    <span class="imco-active-filter-chip" data-imco-cat="<?php echo esc_attr($cat_id); ?>"
                        data-imco-value="<?php echo esc_attr($val); ?>">
                        <span class="imco-active-filter-chip-label"><?php echo esc_html($val); ?></span>
                        <button type="button" class="imco-active-filter-chip-close"
                            aria-label="<?php esc_attr_e('Quitar este filtro', 'imco-filter'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </span>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <a href="#" class="imco-active-filters-clear"><?php esc_html_e('Limpiar todo', 'imco-filter'); ?></a>
    </div>
    <?php
}

/**
 * Construye el meta_query de WP_Query a partir de los filtros del usuario.
 */
function imco_build_meta_query_from_filters()
{
    if (!function_exists('imco_get_attribute_categories')) {
        return array();
    }
    if (!isset($_GET['imco_filter']) || !is_array($_GET['imco_filter'])) {
        return array();
    }
    $raw_filters = wp_unslash($_GET['imco_filter']);
    $categories = imco_get_attribute_categories();
    if (empty($categories)) {
        return array();
    }

    $meta_key = '_imco_product_attributes';
    $meta_query = array('relation' => 'AND');

    foreach ($categories as $cat) {
        $cat_id = isset($cat['id']) ? $cat['id'] : '';
        $cat_type = isset($cat['type']) ? $cat['type'] : 'select';

        if (empty($cat_id) || !isset($raw_filters[$cat_id])) {
            continue;
        }

        if (in_array($cat_type, ['checkbox', 'colors', 'buttons'], true)) {
            $values = $raw_filters[$cat_id];
            if (!is_array($values)) {
                $values = array($values);
            }
            $values = array_filter(array_map('sanitize_text_field', $values));
            if (empty($values)) {
                continue;
            }

            if (1 === count($values)) {
                $meta_query[] = array('key' => $meta_key, 'value' => $values[0], 'compare' => 'LIKE');
            } else {
                $or_group = array('relation' => 'OR');
                foreach ($values as $v) {
                    $or_group[] = array('key' => $meta_key, 'value' => $v, 'compare' => 'LIKE');
                }
                $meta_query[] = $or_group;
            }
        } else {
            $value = $raw_filters[$cat_id];
            if (is_array($value)) {
                $value = reset($value);
            }
            $value = sanitize_text_field($value);
            if ('' === $value) {
                continue;
            }
            $meta_query[] = array('key' => $meta_key, 'value' => $value, 'compare' => 'LIKE');
        }
    }

    if (1 === count($meta_query)) {
        return array();
    }
    return $meta_query;
}

/**
 * Renderiza el grid de productos con paginación.
 */
function imco_render_products_grid()
{
    if (!class_exists('WooCommerce')) {
        echo '<p>' . esc_html__('WooCommerce no está activo.', 'imco-filter') . '</p>';
        return;
    }

    global $imco_current_category_slug;

    // FORZAMOS LA LECTURA DE LA OPCIÓN DEL ADMINISTRADOR
    $per_page = (int) get_option('imco_products_per_page', 12);
    if ($per_page < 1) {
        $per_page = 12;
    }

    $pagination_mode = get_option('imco_pagination_mode', 'pages');
    if (!in_array($pagination_mode, ['pages', 'load_more'], true)) {
        $pagination_mode = 'pages';
    }

    $paged = 1;
    if (isset($_GET['imco_page']) && (int) $_GET['imco_page'] > 1) {
        $paged = (int) $_GET['imco_page'];
    } elseif (isset($_GET['paged']) && (int) $_GET['paged'] > 1) {
        $paged = (int) $_GET['paged'];
    } elseif (get_query_var('paged')) {
        $paged = get_query_var('paged');
    } elseif (get_query_var('page')) {
        $paged = get_query_var('page');
    }

    $args = [
        'post_type' => 'product',
        'posts_per_page' => $per_page, // Aquí aplicamos estrictamente el número
        'post_status' => 'publish',
        'paged' => $paged,
        // Ignorar sticky posts para no alterar la paginación
        'ignore_sticky_posts' => 1,
        // Bandera personalizada para identificar nuestra consulta
        'imco_custom_query' => true,
        // Evitar que otros plugins alteren la consulta
        'suppress_filters' => true,
    ];

    $category_slug = '';
    if (isset($_GET['imco_category']) && '' !== $_GET['imco_category']) {
        $category_slug = sanitize_title(wp_unslash($_GET['imco_category']));
    } elseif (!empty($imco_current_category_slug)) {
        $category_slug = sanitize_title($imco_current_category_slug);
    }

    if (!empty($category_slug)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $category_slug,
            ],
        ];
    }

    $meta_query = imco_build_meta_query_from_filters();
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    $args = apply_filters('imco_products_query_args', $args);

    // FILTRO AGRESIVO: Evitar que el tema (Astra) o WooCommerce sobreescriban la paginación
    $force_pagination = function ($query) use ($per_page, $paged) {
        if ($query->get('imco_custom_query')) {
            $query->set('posts_per_page', $per_page);
            $query->set('paged', $paged);
        }
    };
    add_action('pre_get_posts', $force_pagination, 99999);

    // Ejecutamos la consulta
    $loop = new WP_Query($args);

    // Quitamos el filtro para no afectar otras consultas de la página
    remove_action('pre_get_posts', $force_pagination, 99999);

    $max = (int) $loop->max_num_pages;

    echo '<!-- IMCO HACK ACTIVE: per_page=' . esc_html($per_page) . ' -->';

    if ($loop->have_posts()) {
        $count = 0; // Iniciamos un contador

        // Iniciamos el loop de WooCommerce para que el tema sepa que estamos en un loop
        woocommerce_product_loop_start();

        while ($loop->have_posts()) {
            // HACK ANTI-ASTRA: Forzamos el corte exacto
            if ($count >= $per_page) {
                break;
            }

            $loop->the_post();
            wc_get_template_part('content', 'product');
            $count++;
        }

        // Cerramos el loop de WooCommerce
        woocommerce_product_loop_end();

        wp_reset_postdata();
    } else {
        echo '<div class="imco-products-items">';
        echo '<p class="imco-no-products">' . esc_html__('No se encontraron productos con los filtros seleccionados.', 'imco-filter') . '</p>';
        echo '</div>';
    }

    // Renderizar Paginación
    if ('pages' === $pagination_mode && $max > 1) {
        echo '<div class="imco-pagination imco-pagination-pages">';

        // Botón Anterior
        if ($paged > 1) {
            printf('<a href="#" class="imco-page-link prev" data-imco-page="%d">&laquo;</a>', $paged - 1);
        }

        for ($i = 1; $i <= $max; $i++) {
            $classes = 'imco-page-link';
            if ($i === $paged) {
                $classes .= ' is-active';
            }
            printf('<a href="#" class="%1$s" data-imco-page="%2$d">%2$d</a>', esc_attr($classes), (int) $i);
        }

        // Botón Siguiente
        if ($paged < $max) {
            printf('<a href="#" class="imco-page-link next" data-imco-page="%d">&raquo;</a>', $paged + 1);
        }

        echo '</div>';
    } elseif ('load_more' === $pagination_mode && $max > $paged) {
        echo '<div class="imco-pagination imco-pagination-load-more">';
        printf('<button type="button" class="imco-load-more-button" data-imco-next-page="%d">%s</button>', (int) ($paged + 1), esc_html__('Cargar más', 'imco-filter'));
        echo '</div>';
    }
}
