<?php
/**
 * Shortcode [imco_specs_table]
 * Muestra una tabla de especificaciones del producto usando diseño moderno (Apple-Style).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Función auxiliar para obtener un icono SVG basado en el nombre del atributo.
 */
function imco_get_spec_icon($label)
{
    $label_lower = strtolower(trim($label));

    // Icono por defecto (Info)
    $default_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';

    // Mapeo exacto de nombres de atributos a iconos
    $icon_map = [
        // Relojes
        'tipo de correa' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>',
        'pulso' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>',
        'tamaño de caja' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
        'movimiento' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'cristal' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>',
        'resistencia al agua' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"></path></svg>',

        // Generales
        'color' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path></svg>',
        'garantía' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
        'garantia' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
        'marca' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>',
        'material' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
        'peso' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
        'dimensiones' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>',
    ];

    // Buscar coincidencia exacta primero
    if (isset($icon_map[$label_lower])) {
        return $icon_map[$label_lower];
    }

    // Si no hay coincidencia exacta, buscar por palabras clave (fallback)
    foreach ($icon_map as $key => $icon) {
        if (strpos($label_lower, $key) !== false) {
            return $icon;
        }
    }

    return $default_icon;
}

/**
 * Devuelve un array de pares [label, value] con los atributos IMCO
 */
function imco_get_product_imco_attribute_pairs($product_id)
{
    $raw = get_post_meta($product_id, '_imco_product_attributes', true);
    if (!is_array($raw) || empty($raw)) {
        return [];
    }

    // CORRECCIÓN: Si la función del admin no está cargada en el frontend, leemos directamente de la base de datos
    $cats = [];
    if (function_exists('imco_get_attribute_categories')) {
        $cats = imco_get_attribute_categories();
    } else {
        $cats = get_option('imco_attributes_config', []);
    }

    if (empty($cats) || !is_array($cats)) {
        return [];
    }

    $indexed = [];
    foreach ($cats as $cat) {
        if (empty($cat['id'])) {
            continue;
        }
        $id = $cat['id'];
        $indexed[$id] = $cat;
    }

    $result = [];

    foreach ($raw as $cat_id => $values) {
        $cat_id = sanitize_key($cat_id);

        if (!isset($indexed[$cat_id])) {
            continue;
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        $values = array_filter(
            array_map('sanitize_text_field', $values),
            function ($v) {
                return '' !== $v;
            }
        );

        if (empty($values)) {
            continue;
        }

        $label = !empty($indexed[$cat_id]['label'])
            ? $indexed[$cat_id]['label']
            : $cat_id;

        $value_str = implode(', ', $values);

        $result[] = [
            'id' => $cat_id,
            'label' => $label,
            'value' => $value_str,
        ];
    }

    return $result;
}

/**
 * Genera el HTML de la tabla de especificaciones.
 */
function imco_generate_specs_table()
{
    if (!function_exists('wc_get_product')) {
        return '';
    }

    global $product;

    if (!$product instanceof WC_Product) {
        $product = wc_get_product(get_the_ID());
    }

    if (!$product instanceof WC_Product) {
        return '';
    }

    $product_id = $product->get_id();

    // ---- Atributos IMCO ----.
    $imco_pairs = imco_get_product_imco_attribute_pairs($product_id);

    // CORRECCIÓN: Si el producto NO tiene atributos IMCO, no mostramos la tabla vacía
    if (empty($imco_pairs)) {
        return '';
    }

    // ---- Filas base: nombre y precio ----.
    $rows = [];

    $rows[] = [
        'id' => 'producto',
        'label' => __('Producto', 'imco-filter'),
        'value' => $product->get_name(),
    ];

    $price = $product->get_price();
    if ('' !== $price) {
        $rows[] = [
            'id' => 'precio',
            'label' => __('Precio', 'imco-filter'),
            // Usamos strip_tags para quitar el HTML extra que a veces mete WooCommerce en el precio
            'value' => strip_tags(wc_price($price)),
        ];
    }

    // Unimos las filas base con los atributos IMCO
    $rows = array_merge($rows, $imco_pairs);

    ob_start();
    ?>
    <div class="imco-modern-specs-container">
        <div class="imco-modern-specs-header">
            <div class="imco-modern-specs-icon">
                <!-- NUEVO ICONO DE RELOJ AQUÍ -->
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="7"></circle>
                    <polyline points="12 9 12 12 13.5 13.5"></polyline>
                    <path
                        d="M16.51 17.35l-.35 3.83a2 2 0 0 1-2 1.82H9.83a2 2 0 0 1-2-1.82l-.35-3.83m.01-10.7l.35-3.83A2 2 0 0 1 9.83 1h4.35a2 2 0 0 1 2 1.82l.35 3.83">
                    </path>
                </svg>
            </div>
            <h3 class="imco-modern-specs-title"><?php esc_html_e('Detalles Técnicos', 'imco-filter'); ?></h3>
        </div>

        <div class="imco-modern-specs-grid">
            <?php foreach ($rows as $row):
                // Determinar si es una fila "básica" (ocupa todo el ancho)
                $is_basic = in_array($row['id'], ['producto', 'precio', 'genero', 'generodereloj']);
                $item_class = $is_basic ? 'imco-spec-item imco-spec-full' : 'imco-spec-item';
                ?>
                <div class="<?php echo esc_attr($item_class); ?>">
                    <div class="imco-spec-label-wrap">
                        <?php if (!$is_basic): ?>
                            <span class="imco-spec-icon">
                                <?php echo imco_get_spec_icon($row['label']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="imco-spec-label"><?php echo esc_html($row['label']); ?></span>
                    </div>
                    <div class="imco-spec-value"><?php echo esc_html($row['value']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

// Registrar el shortcode.
add_shortcode('imco_specs_table', 'imco_generate_specs_table');

/**
 * Mostrar la tabla automáticamente según los ajustes del plugin.
 */
function imco_display_specs_table_automatically()
{
    // Verificar si la inyección automática está activada
    $auto_insert = get_option('imco_auto_insert_specs', 'no');
    if ($auto_insert !== 'yes') {
        return;
    }

    $table_html = imco_generate_specs_table();
    if (!empty($table_html)) {
        echo '<div class="imco-automatic-specs-container" style="margin-top: 2em; margin-bottom: 2em; clear: both;">';
        echo $table_html;
        echo '</div>';
    }
}

// Obtener la posición configurada en los ajustes
$table_position = get_option('imco_specs_position', 'after_summary');

if ($table_position === 'before_summary') {
    // Antes de la descripción larga (pero fuera de las pestañas, justo después del resumen corto)
    add_action('woocommerce_after_single_product_summary', 'imco_display_specs_table_automatically', 5);
} else {
    // Después de la descripción larga (al final de todo el producto)
    add_action('woocommerce_after_single_product', 'imco_display_specs_table_automatically', 5);
}

