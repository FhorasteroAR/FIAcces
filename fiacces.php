<?php
/**
 * Plugin Name:       FIAcces
 * Plugin URI:        https://example.com/fiacces
 * Description:       Barra de accesibilidad para el frontend. Permite a cualquier visitante ajustar el tamaño del texto, el contraste, filtros para daltonismo, legibilidad, animaciones y cursor. Sin panel de administración: solo funciones para el usuario final.
 * Version:           2.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            FIA
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fiacces
 * Domain Path:       /languages
 *
 * @package FIAcces
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FIACCES_VERSION', '2.0.0' );
define( 'FIACCES_PLUGIN_FILE', __FILE__ );
define( 'FIACCES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin FIAcces: barra de accesibilidad para el usuario final.
 *
 * Todo el plugin es de cara al visitante; no registra ninguna página ni ajuste
 * en el área de administración. La configuración (posición, color, atajo) es
 * fija y las preferencias del usuario se guardan en su propio navegador.
 */
final class FIAcces {

    /** Configuración fija de la barra. */
    const POSITION     = 'bottom-right'; // bottom-right | bottom-left | top-right | top-left
    const PRIMARY      = '#2563EB';
    const SHORTCUT_KEY = 'A';            // se activa con Alt + esta letra

    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_action( 'wp_head',            array( __CLASS__, 'prepaint' ), 20 );
        add_action( 'wp_footer',          array( __CLASS__, 'render' ) );
        add_action( 'init',               array( __CLASS__, 'load_textdomain' ) );
    }

    public static function load_textdomain() {
        load_plugin_textdomain( 'fiacces', false, dirname( plugin_basename( FIACCES_PLUGIN_FILE ) ) . '/languages' );
    }

    /** Encola CSS y JS del frontend (nunca en el admin). */
    public static function enqueue() {
        if ( is_admin() ) {
            return;
        }

        wp_enqueue_style(
            'fiacces',
            FIACCES_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            FIACCES_VERSION
        );

        wp_enqueue_script(
            'fiacces',
            FIACCES_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            FIACCES_VERSION,
            true
        );

        wp_localize_script(
            'fiacces',
            'FIAcces',
            array(
                'shortcutKey' => self::SHORTCUT_KEY,
                'primary'     => self::PRIMARY,
                'i18n'        => self::i18n(),
            )
        );
    }

    /**
     * Aplica las preferencias guardadas antes de pintar la página, para evitar
     * un parpadeo (FOUC) con la configuración por defecto.
     */
    public static function prepaint() {
        if ( is_admin() ) {
            return;
        }
        $js = <<<'JS'
(function(){try{
var p=JSON.parse(localStorage.getItem('fiacces_prefs')||'{}'),h=document.documentElement;
if(p.contrast)h.classList.add('fiacces-contrast-'+p.contrast);
if(p.colorblind)h.classList.add('fiacces-daltonism-'+p.colorblind);
if(p.dyslexia)h.classList.add('fiacces-dyslexia');
if(p.underline)h.classList.add('fiacces-underline-links');
if(p.pauseAnim)h.classList.add('fiacces-pause-animations');
if(p.cursor)h.classList.add('fiacces-cursor-'+p.cursor);
}catch(e){}})();
JS;
        if ( function_exists( 'wp_print_inline_script_tag' ) ) {
            wp_print_inline_script_tag( $js );
        } else {
            echo '<script>' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }

    /** Cadenas traducibles de la interfaz. */
    private static function i18n() {
        return array(
            'open'            => __( 'Abrir herramientas de accesibilidad', 'fiacces' ),
            'close'           => __( 'Cerrar', 'fiacces' ),
            'title'           => __( 'Accesibilidad', 'fiacces' ),
            'text_size'       => __( 'Tamaño del texto', 'fiacces' ),
            'decrease'        => __( 'Disminuir tamaño de texto', 'fiacces' ),
            'increase'        => __( 'Aumentar tamaño de texto', 'fiacces' ),
            'contrast'        => __( 'Contraste', 'fiacces' ),
            'normal'          => __( 'Normal', 'fiacces' ),
            'high'            => __( 'Alto contraste', 'fiacces' ),
            'inverted'        => __( 'Invertir colores', 'fiacces' ),
            'gray'            => __( 'Escala de grises', 'fiacces' ),
            'colorblind'      => __( 'Daltonismo', 'fiacces' ),
            'protanopia'      => __( 'Protanopia', 'fiacces' ),
            'deuteranopia'    => __( 'Deuteranopia', 'fiacces' ),
            'tritanopia'      => __( 'Tritanopia', 'fiacces' ),
            'readability'     => __( 'Legibilidad', 'fiacces' ),
            'dyslexia'        => __( 'Fuente para dislexia', 'fiacces' ),
            'underline'       => __( 'Subrayar enlaces', 'fiacces' ),
            'animations'      => __( 'Pausar animaciones', 'fiacces' ),
            'cursor'          => __( 'Cursor grande', 'fiacces' ),
            'reset'           => __( 'Restablecer todo', 'fiacces' ),
            'applied'         => __( 'Ajuste aplicado', 'fiacces' ),
            'announce_open'   => __( 'Panel de accesibilidad abierto', 'fiacces' ),
            'announce_close'  => __( 'Panel de accesibilidad cerrado', 'fiacces' ),
        );
    }

    /** Imprime el botón flotante y el panel en el pie de página. */
    public static function render() {
        if ( is_admin() ) {
            return;
        }
        $t = self::i18n();
        ?>
        <div id="fiacces-root" class="fiacces-pos-<?php echo esc_attr( self::POSITION ); ?>"
             style="--fiacces-primary: <?php echo esc_attr( self::PRIMARY ); ?>;">

            <button type="button" id="fiacces-toggle" class="fiacces-fab"
                    aria-expanded="false" aria-controls="fiacces-panel"
                    aria-label="<?php echo esc_attr( $t['open'] ); ?>">
                <span aria-hidden="true">&#9855;</span>
            </button>

            <div id="fiacces-panel" class="fiacces-panel" role="dialog" aria-modal="true"
                 aria-label="<?php echo esc_attr( $t['title'] ); ?>" hidden>

                <div class="fiacces-panel__head">
                    <strong><?php echo esc_html( $t['title'] ); ?></strong>
                    <button type="button" id="fiacces-close" class="fiacces-close"
                            aria-label="<?php echo esc_attr( $t['close'] ); ?>">&times;</button>
                </div>

                <div class="fiacces-panel__body">

                    <section class="fiacces-group">
                        <h3><?php echo esc_html( $t['text_size'] ); ?></h3>
                        <div class="fiacces-row">
                            <button type="button" class="fiacces-btn" data-action="text-decrease"
                                    aria-label="<?php echo esc_attr( $t['decrease'] ); ?>">A&minus;</button>
                            <span class="fiacces-value" data-display="text-scale" aria-live="polite">100%</span>
                            <button type="button" class="fiacces-btn" data-action="text-increase"
                                    aria-label="<?php echo esc_attr( $t['increase'] ); ?>">A+</button>
                        </div>
                    </section>

                    <section class="fiacces-group">
                        <h3><?php echo esc_html( $t['contrast'] ); ?></h3>
                        <div class="fiacces-grid">
                            <button type="button" class="fiacces-toggle" data-action="contrast" data-value="" aria-pressed="true"><?php echo esc_html( $t['normal'] ); ?></button>
                            <button type="button" class="fiacces-toggle" data-action="contrast" data-value="high" aria-pressed="false"><?php echo esc_html( $t['high'] ); ?></button>
                            <button type="button" class="fiacces-toggle" data-action="contrast" data-value="inverted" aria-pressed="false"><?php echo esc_html( $t['inverted'] ); ?></button>
                            <button type="button" class="fiacces-toggle" data-action="contrast" data-value="gray" aria-pressed="false"><?php echo esc_html( $t['gray'] ); ?></button>
                        </div>
                    </section>

                    <section class="fiacces-group">
                        <h3><?php echo esc_html( $t['colorblind'] ); ?></h3>
                        <div class="fiacces-grid">
                            <button type="button" class="fiacces-toggle" data-action="colorblind" data-value="" aria-pressed="true"><?php echo esc_html( $t['normal'] ); ?></button>
                            <button type="button" class="fiacces-toggle" data-action="colorblind" data-value="protanopia" aria-pressed="false"><?php echo esc_html( $t['protanopia'] ); ?></button>
                            <button type="button" class="fiacces-toggle" data-action="colorblind" data-value="deuteranopia" aria-pressed="false"><?php echo esc_html( $t['deuteranopia'] ); ?></button>
                            <button type="button" class="fiacces-toggle" data-action="colorblind" data-value="tritanopia" aria-pressed="false"><?php echo esc_html( $t['tritanopia'] ); ?></button>
                        </div>
                    </section>

                    <section class="fiacces-group">
                        <h3><?php echo esc_html( $t['readability'] ); ?></h3>
                        <label class="fiacces-switch">
                            <input type="checkbox" data-action="dyslexia">
                            <span><?php echo esc_html( $t['dyslexia'] ); ?></span>
                        </label>
                        <label class="fiacces-switch">
                            <input type="checkbox" data-action="underline">
                            <span><?php echo esc_html( $t['underline'] ); ?></span>
                        </label>
                        <label class="fiacces-switch">
                            <input type="checkbox" data-action="pause-anim">
                            <span><?php echo esc_html( $t['animations'] ); ?></span>
                        </label>
                        <label class="fiacces-switch">
                            <input type="checkbox" data-action="cursor">
                            <span><?php echo esc_html( $t['cursor'] ); ?></span>
                        </label>
                    </section>

                    <button type="button" id="fiacces-reset" class="fiacces-reset" data-action="reset">
                        <?php echo esc_html( $t['reset'] ); ?>
                    </button>
                </div>
            </div>

            <div id="fiacces-announce" class="fiacces-sr-only" aria-live="polite" role="status"></div>

            <!-- Filtros SVG para simulación de daltonismo -->
            <svg class="fiacces-sr-only" aria-hidden="true" focusable="false">
                <defs>
                    <filter id="fiacces-protanopia"><feColorMatrix type="matrix" values="0.567 0.433 0 0 0  0.558 0.442 0 0 0  0 0.242 0.758 0 0  0 0 0 1 0"/></filter>
                    <filter id="fiacces-deuteranopia"><feColorMatrix type="matrix" values="0.625 0.375 0 0 0  0.7 0.3 0 0 0  0 0.3 0.7 0 0  0 0 0 1 0"/></filter>
                    <filter id="fiacces-tritanopia"><feColorMatrix type="matrix" values="0.95 0.05 0 0 0  0 0.433 0.567 0 0  0 0.475 0.525 0 0  0 0 0 1 0"/></filter>
                </defs>
            </svg>
        </div>
        <?php
    }
}

FIAcces::init();
