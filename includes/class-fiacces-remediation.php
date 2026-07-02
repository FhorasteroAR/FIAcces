<?php
/**
 * Ayudas de conformidad WCAG: correcciones automáticas y seguras aplicadas
 * al frontend del sitio (no a la barra de FIAcces).
 *
 * IMPORTANTE: estas ayudas cubren solo los criterios "mecánicos" que pueden
 * resolverse sin juicio humano. NO garantizan conformidad WCAG 2.1 AA; el
 * contenido (alt significativos, subtítulos, contraste de marca, encabezados
 * con sentido) debe revisarse manualmente. Ver docs/wcag-2.1-fia.md.
 *
 * @package FIAcces
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FIAcces_Remediation {

    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_body_open',       array( __CLASS__, 'render_skip_link' ) );
        add_action( 'wp_head',            array( __CLASS__, 'inline_styles' ), 4 );
        add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'ensure_attachment_alt' ), 10, 1 );
    }

    /** Devuelve el arreglo de ayudas activadas. */
    private static function flags() {
        $opts = FIAcces_Settings::get_options();
        return isset( $opts['remediation'] ) && is_array( $opts['remediation'] )
            ? $opts['remediation']
            : array();
    }

    /** Estilos del skip link y del foco visible (se imprimen pronto en el head). */
    public static function inline_styles() {
        if ( is_admin() ) {
            return;
        }
        $flags = self::flags();
        $css   = '';

        if ( ! empty( $flags['skip_link'] ) ) {
            $css .= '.fiacces-skip-link{position:absolute;left:-9999px;top:0;z-index:1000000;'
                 . 'background:#000;color:#fff;padding:10px 16px;border-radius:0 0 6px 0;'
                 . 'font:600 14px/1.4 sans-serif;text-decoration:underline;}'
                 . '.fiacces-skip-link:focus{left:0;}';
        }

        if ( ! empty( $flags['focus_visible'] ) ) {
            $css .= 'a:focus-visible,button:focus-visible,input:focus-visible,'
                 . 'select:focus-visible,textarea:focus-visible,[tabindex]:focus-visible,'
                 . 'summary:focus-visible{outline:3px solid #1d4ed8 !important;'
                 . 'outline-offset:2px !important;}';
        }

        if ( '' === $css ) {
            return;
        }

        if ( function_exists( 'wp_print_inline_style_tag' ) ) {
            wp_print_inline_style_tag( $css, array( 'id' => 'fiacces-remediation-inline' ) );
        } else {
            echo '<style id="fiacces-remediation-inline">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }

    /** Enlace "Saltar al contenido" al inicio del body. */
    public static function render_skip_link() {
        if ( is_admin() ) {
            return;
        }
        $flags = self::flags();
        if ( empty( $flags['skip_link'] ) ) {
            return;
        }
        printf(
            '<a class="fiacces-skip-link" href="#fiacces-main-content">%s</a>',
            esc_html__( 'Saltar al contenido', 'fiacces' )
        );
    }

    /** Añade alt="" a las imágenes de la biblioteca que no tengan alt (evita leer el nombre del archivo). */
    public static function ensure_attachment_alt( $attr ) {
        $flags = self::flags();
        if ( ! empty( $flags['img_alt'] ) && ! isset( $attr['alt'] ) ) {
            $attr['alt'] = '';
        }
        return $attr;
    }

    /** Encola el JS que aplica las correcciones del lado del cliente. */
    public static function enqueue_assets() {
        if ( is_admin() ) {
            return;
        }

        $flags = self::flags();
        // Si no hay ninguna ayuda de cliente activa, no cargar nada.
        $client_flags = array( 'lang_attr', 'img_alt', 'external_links', 'nav_labels', 'skip_link' );
        $any          = false;
        foreach ( $client_flags as $f ) {
            if ( ! empty( $flags[ $f ] ) ) {
                $any = true;
                break;
            }
        }
        if ( ! $any ) {
            return;
        }

        wp_enqueue_script(
            'fiacces-remediation',
            FIACCES_PLUGIN_URL . 'assets/js/remediation.js',
            array(),
            FIACCES_VERSION,
            true
        );

        wp_localize_script(
            'fiacces-remediation',
            'FIAccesRemediation',
            array(
                'flags' => array(
                    'skipLink'      => ! empty( $flags['skip_link'] ),
                    'langAttr'      => ! empty( $flags['lang_attr'] ),
                    'imgAlt'        => ! empty( $flags['img_alt'] ),
                    'externalLinks' => ! empty( $flags['external_links'] ),
                    'navLabels'     => ! empty( $flags['nav_labels'] ),
                ),
                'lang'  => get_bloginfo( 'language' ),
                'i18n'  => array(
                    'newTab' => __( '(abre en una nueva pestaña)', 'fiacces' ),
                    'nav'    => __( 'Navegación', 'fiacces' ),
                ),
            )
        );
    }
}
