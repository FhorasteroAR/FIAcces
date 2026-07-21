<?php
/**
 * Desinstalación: elimina opciones del plugin.
 *
 * @package FIAcces
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'fiacces_settings' );
