<?php
/**
 * Máscara de auditoría del orden de foco (solo administradores).
 *
 * Encola una capa visual en el frontend que numera los elementos enfocables
 * en el orden en que los recorre la tecla Tab y los colorea según una
 * heurística. Es una ayuda de auditoría: no modifica el sitio.
 *
 * @package FIAcces
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FIAcces_Mask {

    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /** Encola la máscara solo para usuarios que pueden gestionar opciones. */
    public static function enqueue_assets() {
        if ( is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_enqueue_style(
            'fiacces-mask',
            FIACCES_PLUGIN_URL . 'assets/css/mask.css',
            array(),
            FIACCES_VERSION
        );

        wp_enqueue_script(
            'fiacces-mask',
            FIACCES_PLUGIN_URL . 'assets/js/mask.js',
            array(),
            FIACCES_VERSION,
            true
        );

        wp_localize_script(
            'fiacces-mask',
            'FIAccesMask',
            array( 'i18n' => self::i18n() )
        );
    }

    /** Cadenas traducibles para la máscara. */
    private static function i18n() {
        return array(
            'toggle'        => __( 'Máscara de foco (Tab)', 'fiacces' ),
            'not_focusable' => __( '⚠ no enfocable', 'fiacces' ),
        );
    }
}
