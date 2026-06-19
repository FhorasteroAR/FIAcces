<?php
/**
 * Desinstalación: elimina opciones del plugin.
 *
 * @package WP_Accesibilidad_A11y
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'wpa11y_settings' );
