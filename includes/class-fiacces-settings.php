<?php
/**
 * Gestión de configuración del plugin.
 *
 * @package FIAcces
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FIAcces_Settings {

    public static function init() {
        // Sin hooks por ahora; clase utilitaria.
    }

    /** Valores por defecto de la configuración. */
    public static function get_defaults() {
        return array(
            // Posición del botón flotante
            'button_position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
            // Color primario del panel
            'primary_color'   => '#2563EB',
            // Atajo de teclado (combinación con Alt)
            'shortcut_key'    => 'A',
            // Funcionalidades habilitadas
            'features'        => array(
                'text_size'    => true,
                'contrast'     => true,
                'colorblind'   => true,
                'readability'  => true,
                'animations'   => true,
                'cursor'       => true,
                'keyboard_nav' => true,
            ),
            // Mostrar plugin en dispositivos móviles
            'show_on_mobile'  => true,
        );
    }

    /** Obtiene las opciones guardadas (fusionadas con defaults). */
    public static function get_options() {
        $saved    = get_option( FIACCES_OPTION_KEY, array() );
        $defaults = self::get_defaults();
        return wp_parse_args( $saved, $defaults );
    }

    /** Sanitiza un arreglo de opciones antes de guardarlo. */
    public static function sanitize( $input ) {
        $defaults = self::get_defaults();
        $clean    = array();

        // Posición del botón
        $allowed_positions          = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
        $clean['button_position']   = in_array( $input['button_position'] ?? '', $allowed_positions, true )
            ? $input['button_position']
            : $defaults['button_position'];

        // Color primario (formato #RRGGBB)
        $color                  = sanitize_hex_color( $input['primary_color'] ?? '' );
        $clean['primary_color'] = $color ? $color : $defaults['primary_color'];

        // Atajo de teclado (1 letra A-Z)
        $key                   = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $input['shortcut_key'] ?? '' ), 0, 1 ) );
        $clean['shortcut_key'] = $key ? $key : $defaults['shortcut_key'];

        // Funcionalidades
        $clean['features'] = array();
        foreach ( $defaults['features'] as $key => $default ) {
            $clean['features'][ $key ] = ! empty( $input['features'][ $key ] );
        }

        // Mostrar en móvil
        $clean['show_on_mobile'] = ! empty( $input['show_on_mobile'] );

        return $clean;
    }
}
