<?php
/**
 * REST API: endpoints internos del plugin.
 *
 * @package FIAcces
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FIAcces_REST {

    const NAMESPACE_REST = 'fiacces/v1';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {

        // GET /wp-json/fiacces/v1/settings  → exportar configuración (solo admins)
        register_rest_route(
            self::NAMESPACE_REST,
            '/settings',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_settings' ),
                'permission_callback' => array( __CLASS__, 'admin_permission' ),
            )
        );

        // POST /wp-json/fiacces/v1/settings → importar configuración (solo admins)
        register_rest_route(
            self::NAMESPACE_REST,
            '/settings',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'update_settings' ),
                'permission_callback' => array( __CLASS__, 'admin_permission' ),
                'args'                => array(
                    'settings' => array(
                        'required' => true,
                        'type'     => 'object',
                    ),
                ),
            )
        );
    }

    /** Comprueba que el usuario tenga permisos de admin y un nonce válido. */
    public static function admin_permission( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'forbidden', __( 'No tienes permiso.', 'fiacces' ), array( 'status' => 403 ) );
        }
        return true;
    }

    public static function get_settings() {
        return rest_ensure_response(
            array(
                'version'  => FIACCES_VERSION,
                'settings' => FIAcces_Settings::get_options(),
            )
        );
    }

    public static function update_settings( $request ) {
        $input  = (array) $request->get_param( 'settings' );
        $clean  = FIAcces_Settings::sanitize( $input );
        update_option( FIACCES_OPTION_KEY, $clean );
        return rest_ensure_response( array( 'saved' => true, 'settings' => $clean ) );
    }
}
