<?php
/**
 * Gestor de licencia para IMCO Filter.
 *
 * Formato de licencia: IMCO-dominio.com-YYYYMMDD
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve la información del estado de la licencia.
 *
 * @return array {
 *   @type string $code   Código de estado: valid, missing, invalid_format, expired, domain_mismatch.
 *   @type string $label  Texto corto del estado.
 *   @type string $detail Descripción detallada.
 * }
 */
function imco_get_license_status() {
    $key = trim( (string) get_option( 'imco_license_key', '' ) );

    if ( '' === $key ) {
        return [
            'code'   => 'missing',
            'label'  => __( 'Sin licencia', 'imco-filter' ),
            'detail' => __( 'Introduce una clave de licencia para activar IMCO Filter.', 'imco-filter' ),
        ];
    }

    // Formato esperado: IMCO-dominio.com-YYYYMMDD
    if ( ! preg_match( '/^IMCO-([A-Za-z0-9\.\-]+)-(\d{8})$/', $key, $m ) ) {
        return [
            'code'   => 'invalid_format',
            'label'  => __( 'Licencia no válida', 'imco-filter' ),
            'detail' => __( 'Formato de licencia incorrecto. Debe ser IMCO-dominio.com-YYYYMMDD.', 'imco-filter' ),
        ];
    }

    $license_domain_raw  = strtolower( $m[1] );
    $license_expires_raw = $m[2];

    // Dominio actual del sitio.
    $site_url = home_url();
    $host     = parse_url( $site_url, PHP_URL_HOST );

    if ( ! $host ) {
        $host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
    }

    $host = strtolower( $host );

    if ( 0 === strpos( $host, 'www.' ) ) {
        $host = substr( $host, 4 );
    }

    $license_domain = $license_domain_raw;
    if ( 0 === strpos( $license_domain, 'www.' ) ) {
        $license_domain = substr( $license_domain, 4 );
    }

    // Comparar dominios.
    if ( $license_domain !== $host ) {
        return [
            'code'   => 'domain_mismatch',
            'label'  => __( 'Dominio no autorizado', 'imco-filter' ),
            'detail' => sprintf(
                /* translators: 1: dominio de la licencia, 2: dominio actual */
                __( 'Esta licencia es para %1$s, pero el sitio actual es %2$s.', 'imco-filter' ),
                $license_domain_raw,
                $host
            ),
        ];
    }

    // Comprobar fecha.
    $today       = (int) current_time( 'Ymd' );
    $expires_int = (int) $license_expires_raw;

    if ( $expires_int < $today ) {
        return [
            'code'        => 'expired',
            'label'       => __( 'Licencia expirada', 'imco-filter' ),
            'detail'      => sprintf(
                /* translators: 1: fecha */
                __( 'La licencia expiró el %s.', 'imco-filter' ),
                imco_format_license_date( $license_expires_raw )
            ),
            'expires_raw' => $license_expires_raw,
        ];
    }

    return [
        'code'        => 'valid',
        'label'       => __( 'Licencia válida', 'imco-filter' ),
        'detail'      => sprintf(
            __( 'Licencia para %1$s. Válida hasta el %2$s.', 'imco-filter' ),
            $license_domain_raw,
            imco_format_license_date( $license_expires_raw )
        ),
        'expires_raw' => $license_expires_raw,
    ];
}

/**
 * Devuelve true si la licencia es válida.
 *
 * @return bool
 */
function imco_is_license_valid() {
    $status = imco_get_license_status();
    return isset( $status['code'] ) && 'valid' === $status['code'];
}

/**
 * Formatea fecha YYYYMMDD a DD/MM/YYYY.
 *
 * @param string $raw Fecha cruda.
 * @return string
 */
function imco_format_license_date( $raw ) {
    if ( 8 !== strlen( $raw ) ) {
        return $raw;
    }

    $y = substr( $raw, 0, 4 );
    $m = substr( $raw, 4, 2 );
    $d = substr( $raw, 6, 2 );

    return $d . '/' . $m . '/' . $y;
}

/**
 * Aviso en el admin cuando la licencia no es válida.
 */
function imco_license_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }

    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || false === strpos( $screen->id, 'imco' ) ) {
        // Solo mostrar en pantallas relacionadas con IMCO Filter.
        return;
    }

    $status = imco_get_license_status();

    if ( 'valid' === $status['code'] ) {
        return;
    }

    $class = 'notice notice-error';

    if ( 'missing' === $status['code'] ) {
        $class = 'notice notice-warning';
    }

    echo '<div class="' . esc_attr( $class ) . '">';
    echo '<p><strong>' . esc_html__( 'IMCO Filter - Licencia', 'imco-filter' ) . ':</strong> ';
    echo esc_html( $status['detail'] );
    echo '</p></div>';
}
add_action( 'admin_notices', 'imco_license_admin_notice' );
