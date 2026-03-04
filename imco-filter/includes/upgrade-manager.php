<?php
/**
 * Gestor de actualización / instalación de IMCO Filter.
 *
 * Muestra un aviso tipo "El plugin se ha instalado / actualizado"
 * cuando cambia la versión (IMCO_PLUGIN_VERSION).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Comprueba si la versión del plugin ha cambiado.
 * Si es la primera vez o si se ha actualizado, guarda la versión
 * y marca un transient para mostrar un aviso en el admin.
 */
function imco_maybe_flag_plugin_update() {

    if ( ! is_admin() ) {
        return;
    }

    // Versión actual del código (definida en el archivo principal).
    if ( ! defined( 'IMCO_PLUGIN_VERSION' ) ) {
        return;
    }

    $current_version = IMCO_PLUGIN_VERSION;

    // Versión que está guardada en la BD.
    $stored_version  = get_option( 'imco_filter_version', '' );

    // Si son iguales, no hacemos nada.
    if ( $stored_version === $current_version ) {
        return;
    }

    // Guardamos la nueva versión.
    update_option( 'imco_filter_version', $current_version );

    // Guardamos un transient para mostrar el aviso solo una vez.
    set_transient(
        'imco_filter_just_updated',
        [
            'from' => $stored_version,
            'to'   => $current_version,
        ],
        5 * MINUTE_IN_SECONDS
    );
}
add_action( 'plugins_loaded', 'imco_maybe_flag_plugin_update' );

/**
 * Muestra un aviso en el admin cuando el plugin se acaba de instalar
 * o actualizar (solo una vez, mientras exista el transient).
 */
function imco_plugin_update_admin_notice() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }

    $data = get_transient( 'imco_filter_just_updated' );
    if ( false === $data || ! is_array( $data ) ) {
        return;
    }

    // Lo eliminamos para que solo se vea una vez.
    delete_transient( 'imco_filter_just_updated' );

    $from = isset( $data['from'] ) ? (string) $data['from'] : '';
    $to   = isset( $data['to'] ) ? (string) $data['to'] : '';

    // Texto diferente si es primera instalación o actualización.
    if ( '' === $from ) {
        $message = sprintf(
            /* translators: 1: version */
            __( 'IMCO Filter se ha instalado correctamente. Versión actual: %s.', 'imco-filter' ),
            $to
        );
    } else {
        $message = sprintf(
            /* translators: 1: old version, 2: new version */
            __( 'IMCO Filter se ha actualizado de la versión %1$s a la versión %2$s.', 'imco-filter' ),
            $from,
            $to
        );
    }

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>' . esc_html__( 'IMCO Filter', 'imco-filter' ) . ':</strong> ' . esc_html( $message ) . '</p>';
    echo '</div>';
}
add_action( 'admin_notices', 'imco_plugin_update_admin_notice' );
