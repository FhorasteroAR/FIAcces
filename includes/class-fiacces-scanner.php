<?php
/**
 * Analizador de accesibilidad (solo administradores).
 *
 * Detecta en el frontend los incumplimientos WCAG automatizables y permite
 * al administrador corregir el texto alt de las imágenes, guardándolo para
 * todos los visitantes. El resto de hallazgos se reportan con guía de arreglo.
 *
 * @package FIAcces
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FIAcces_Scanner {

    /** Opción donde se guardan los arreglos persistentes. */
    const FIXES_KEY = 'fiacces_a11y_fixes';

    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'rest_api_init',      array( __CLASS__, 'register_routes' ) );
    }

    /** Devuelve los arreglos guardados (estructura: ['img_alt' => [ruta => alt]]). */
    public static function get_fixes() {
        $fixes = get_option( self::FIXES_KEY, array() );
        return is_array( $fixes ) ? $fixes : array();
    }

    /** Normaliza una URL a su ruta (sin host ni query) para usarla como clave estable. */
    public static function normalize_path( $url ) {
        $path = wp_parse_url( (string) $url, PHP_URL_PATH );
        return is_string( $path ) ? $path : '';
    }

    /** Encola el analizador solo para usuarios que pueden gestionar opciones. */
    public static function enqueue_assets() {
        if ( is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_enqueue_style(
            'fiacces-scanner',
            FIACCES_PLUGIN_URL . 'assets/css/scanner.css',
            array(),
            FIACCES_VERSION
        );

        wp_enqueue_script(
            'fiacces-scanner',
            FIACCES_PLUGIN_URL . 'assets/js/scanner.js',
            array(),
            FIACCES_VERSION,
            true
        );

        wp_localize_script(
            'fiacces-scanner',
            'FIAccesScanner',
            array(
                'restUrl' => esc_url_raw( rest_url( FIAcces_REST::NAMESPACE_REST . '/fixes' ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
                'i18n'    => self::i18n(),
            )
        );
    }

    /** Cadenas traducibles para la interfaz del analizador. */
    private static function i18n() {
        return array(
            'panel_title'   => __( 'Analizador de accesibilidad', 'fiacces' ),
            'open'          => __( 'Analizar accesibilidad', 'fiacces' ),
            'close'         => __( 'Cerrar', 'fiacces' ),
            'rescan'        => __( 'Volver a analizar', 'fiacces' ),
            'no_issues'     => __( '¡Sin problemas automáticos detectados en esta página!', 'fiacces' ),
            'issues_found'  => __( 'problemas detectados', 'fiacces' ),
            'highlight'     => __( 'Resaltar', 'fiacces' ),
            'save'          => __( 'Guardar', 'fiacces' ),
            'saved'         => __( 'Guardado', 'fiacces' ),
            'save_error'    => __( 'Error al guardar', 'fiacces' ),
            'alt_placeholder' => __( 'Describe la imagen…', 'fiacces' ),
            'admin_only'    => __( 'Solo tú (administrador) ves este panel.', 'fiacces' ),
            'sev_error'     => __( 'Error', 'fiacces' ),
            'sev_warning'   => __( 'Advertencia', 'fiacces' ),
            'sev_review'    => __( 'Revisar', 'fiacces' ),
        );
    }

    public static function register_routes() {
        register_rest_route(
            FIAcces_REST::NAMESPACE_REST,
            '/fixes',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'save_fix' ),
                'permission_callback' => array( 'FIAcces_REST', 'admin_permission' ),
                'args'                => array(
                    'type'  => array( 'required' => true, 'type' => 'string' ),
                    'key'   => array( 'required' => true, 'type' => 'string' ),
                    'value' => array( 'required' => true, 'type' => 'string' ),
                ),
            )
        );
    }

    /** Guarda un arreglo (por ahora, solo texto alt de imágenes). */
    public static function save_fix( $request ) {
        $type = sanitize_key( $request->get_param( 'type' ) );

        if ( 'img_alt' !== $type ) {
            return new WP_Error( 'unsupported', __( 'Tipo de arreglo no soportado.', 'fiacces' ), array( 'status' => 400 ) );
        }

        $path = self::normalize_path( $request->get_param( 'key' ) );
        if ( '' === $path ) {
            return new WP_Error( 'bad_key', __( 'Imagen no válida.', 'fiacces' ), array( 'status' => 400 ) );
        }

        $value = sanitize_text_field( (string) $request->get_param( 'value' ) );

        $fixes = self::get_fixes();
        if ( ! isset( $fixes['img_alt'] ) || ! is_array( $fixes['img_alt'] ) ) {
            $fixes['img_alt'] = array();
        }
        $fixes['img_alt'][ $path ] = $value;
        update_option( self::FIXES_KEY, $fixes );

        return rest_ensure_response( array( 'saved' => true ) );
    }
}
