<?php
/**
 * Frontend: encolado de assets y renderizado del panel de accesibilidad.
 *
 * @package FIAcces
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FIAcces_Frontend {

    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_footer',          array( __CLASS__, 'render_widget' ) );
        add_action( 'wp_head',            array( __CLASS__, 'inline_settings' ), 5 );
    }

    /** Encola CSS y JS del frontend. */
    public static function enqueue_assets() {
        if ( is_admin() ) {
            return;
        }

        $opts = FIAcces_Settings::get_options();

        // No cargar en móvil si así se configuró
        if ( ! $opts['show_on_mobile'] && wp_is_mobile() ) {
            return;
        }

        wp_enqueue_style(
            'fiacces-frontend',
            FIACCES_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            FIACCES_VERSION
        );

        wp_enqueue_script(
            'fiacces-frontend',
            FIACCES_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            FIACCES_VERSION,
            true
        );

        wp_localize_script(
            'fiacces-frontend',
            'FIAcces',
            array(
                'settings' => array(
                    'position'     => $opts['button_position'],
                    'primaryColor' => $opts['primary_color'],
                    'shortcutKey'  => $opts['shortcut_key'],
                    'features'     => $opts['features'],
                ),
                'i18n'     => self::get_i18n_strings(),
            )
        );
    }

    /**
     * Inserta una mini-script en el <head> que aplica las preferencias guardadas
     * ANTES de que se pinte el contenido (evita "flash of unstyled content").
     */
    public static function inline_settings() {
        if ( is_admin() ) {
            return;
        }

        $js = <<<'JS'
        (function(){
            try {
                var raw = localStorage.getItem('fiacces_prefs');
                if (!raw) return;
                var p = JSON.parse(raw);
                var b = document.documentElement;
                if (p.textScale) b.style.setProperty('--fiacces-text-scale', p.textScale);
                if (p.contrast)  b.classList.add('fiacces-contrast-' + p.contrast);
                if (p.dyslexia)  b.classList.add('fiacces-dyslexia');
                if (p.underline) b.classList.add('fiacces-underline-links');
                if (p.pauseAnim) b.classList.add('fiacces-pause-animations');
                if (p.cursor)    b.classList.add('fiacces-cursor-' + p.cursor);
            } catch(e) {}
        })();
        JS;

        // wp_print_inline_script_tag aplica el filtro 'wp_inline_script_attributes',
        // que permite a plugins de seguridad inyectar un nonce de CSP (WP 5.7+).
        if ( function_exists( 'wp_print_inline_script_tag' ) ) {
            wp_print_inline_script_tag( $js );
        } else {
            echo '<script>' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }

    /** Strings traducibles para la UI. */
    private static function get_i18n_strings() {
        return array(
            'open_panel'        => __( 'Abrir herramientas de accesibilidad', 'fiacces' ),
            'close_panel'       => __( 'Cerrar', 'fiacces' ),
            'title'             => __( 'Accesibilidad', 'fiacces' ),
            'text_size'         => __( 'Tamaño del texto', 'fiacces' ),
            'increase'          => __( 'Aumentar', 'fiacces' ),
            'decrease'          => __( 'Disminuir', 'fiacces' ),
            'contrast'          => __( 'Contraste', 'fiacces' ),
            'contrast_normal'   => __( 'Normal', 'fiacces' ),
            'contrast_high'     => __( 'Alto contraste', 'fiacces' ),
            'contrast_inverted' => __( 'Invertir colores', 'fiacces' ),
            'contrast_gray'     => __( 'Escala de grises', 'fiacces' ),
            'readability'       => __( 'Legibilidad', 'fiacces' ),
            'dyslexia_font'     => __( 'Fuente para dislexia', 'fiacces' ),
            'underline_links'   => __( 'Subrayar enlaces', 'fiacces' ),
            'animations'        => __( 'Animaciones', 'fiacces' ),
            'pause_animations'  => __( 'Pausar animaciones', 'fiacces' ),
            'cursor'            => __( 'Cursor', 'fiacces' ),
            'cursor_large'      => __( 'Grande', 'fiacces' ),
            'cursor_xl'         => __( 'Extra grande', 'fiacces' ),
            'reset'             => __( 'Restablecer todo', 'fiacces' ),
            'applied'           => __( 'Ajuste aplicado', 'fiacces' ),
            'announce_open'     => __( 'Panel de accesibilidad abierto', 'fiacces' ),
            'announce_close'    => __( 'Panel de accesibilidad cerrado', 'fiacces' ),
        );
    }

    /** Imprime el HTML del botón flotante y el panel modal en el footer. */
    public static function render_widget() {
        if ( is_admin() ) {
            return;
        }
        $opts = FIAcces_Settings::get_options();
        if ( ! $opts['show_on_mobile'] && wp_is_mobile() ) {
            return;
        }
        $features = $opts['features'];
        $position = esc_attr( $opts['button_position'] );
        ?>
        <div id="fiacces-root" class="fiacces-pos-<?php echo $position; ?>" style="--fiacces-primary: <?php echo esc_attr( $opts['primary_color'] ); ?>;">

            <button type="button"
                    id="fiacces-toggle"
                    class="fiacces-fab"
                    aria-label="<?php esc_attr_e( 'Abrir herramientas de accesibilidad', 'fiacces' ); ?>"
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="fiacces-panel">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="10"/>
                    <circle cx="12" cy="7" r="1.5" fill="currentColor"/>
                    <path d="M8 11h8M12 11v6M9 17l3-4M15 17l-3-4"/>
                </svg>
                <span class="fiacces-sr-only"><?php esc_html_e( 'Accesibilidad', 'fiacces' ); ?></span>
            </button>

            <div id="fiacces-panel"
                 class="fiacces-panel"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="fiacces-panel-title"
                 hidden>

                <div class="fiacces-panel__header">
                    <h2 id="fiacces-panel-title" class="fiacces-panel__title">
                        <?php esc_html_e( 'Accesibilidad', 'fiacces' ); ?>
                    </h2>
                    <button type="button"
                            id="fiacces-close"
                            class="fiacces-close"
                            aria-label="<?php esc_attr_e( 'Cerrar panel de accesibilidad', 'fiacces' ); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="fiacces-panel__body">

                    <?php if ( $features['text_size'] ) : ?>
                    <section class="fiacces-card" aria-labelledby="fiacces-card-text">
                        <h3 id="fiacces-card-text" class="fiacces-card__title">
                            <?php esc_html_e( 'Tamaño del texto', 'fiacces' ); ?>
                        </h3>
                        <div class="fiacces-card__controls">
                            <button type="button" class="fiacces-btn" data-action="text-decrease" aria-label="<?php esc_attr_e( 'Disminuir tamaño de texto', 'fiacces' ); ?>">A−</button>
                            <span class="fiacces-value" data-display="text-scale" aria-live="polite">100%</span>
                            <button type="button" class="fiacces-btn" data-action="text-increase" aria-label="<?php esc_attr_e( 'Aumentar tamaño de texto', 'fiacces' ); ?>">A+</button>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $features['contrast'] ) : ?>
                    <section class="fiacces-card" aria-labelledby="fiacces-card-contrast">
                        <h3 id="fiacces-card-contrast" class="fiacces-card__title">
                            <?php esc_html_e( 'Contraste y color', 'fiacces' ); ?>
                        </h3>
                        <div class="fiacces-card__grid">
                            <button type="button" class="fiacces-toggle" data-action="contrast" data-value="" aria-pressed="true">
                                <?php esc_html_e( 'Normal', 'fiacces' ); ?>
                            </button>
                            <button type="button" class="fiacces-toggle" data-action="contrast" data-value="high" aria-pressed="false">
                                <?php esc_html_e( 'Alto contraste', 'fiacces' ); ?>
                            </button>
                            <button type="button" class="fiacces-toggle" data-action="contrast" data-value="inverted" aria-pressed="false">
                                <?php esc_html_e( 'Invertir colores', 'fiacces' ); ?>
                            </button>
                            <button type="button" class="fiacces-toggle" data-action="contrast" data-value="gray" aria-pressed="false">
                                <?php esc_html_e( 'Escala de grises', 'fiacces' ); ?>
                            </button>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $features['readability'] ) : ?>
                    <section class="fiacces-card" aria-labelledby="fiacces-card-read">
                        <h3 id="fiacces-card-read" class="fiacces-card__title">
                            <?php esc_html_e( 'Legibilidad', 'fiacces' ); ?>
                        </h3>
                        <div class="fiacces-card__list">
                            <label class="fiacces-switch">
                                <input type="checkbox" data-action="dyslexia">
                                <span class="fiacces-switch__slider" aria-hidden="true"></span>
                                <span class="fiacces-switch__label">
                                    <?php esc_html_e( 'Fuente para dislexia', 'fiacces' ); ?>
                                </span>
                            </label>
                            <label class="fiacces-switch">
                                <input type="checkbox" data-action="underline">
                                <span class="fiacces-switch__slider" aria-hidden="true"></span>
                                <span class="fiacces-switch__label">
                                    <?php esc_html_e( 'Subrayar enlaces', 'fiacces' ); ?>
                                </span>
                            </label>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $features['animations'] ) : ?>
                    <section class="fiacces-card" aria-labelledby="fiacces-card-anim">
                        <h3 id="fiacces-card-anim" class="fiacces-card__title">
                            <?php esc_html_e( 'Animaciones', 'fiacces' ); ?>
                        </h3>
                        <div class="fiacces-card__list">
                            <label class="fiacces-switch">
                                <input type="checkbox" data-action="pause-anim">
                                <span class="fiacces-switch__slider" aria-hidden="true"></span>
                                <span class="fiacces-switch__label">
                                    <?php esc_html_e( 'Pausar animaciones, sliders y vídeos', 'fiacces' ); ?>
                                </span>
                            </label>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $features['cursor'] ) : ?>
                    <section class="fiacces-card" aria-labelledby="fiacces-card-cursor">
                        <h3 id="fiacces-card-cursor" class="fiacces-card__title">
                            <?php esc_html_e( 'Cursor', 'fiacces' ); ?>
                        </h3>
                        <div class="fiacces-card__grid">
                            <button type="button" class="fiacces-toggle" data-action="cursor" data-value="" aria-pressed="true">
                                <?php esc_html_e( 'Normal', 'fiacces' ); ?>
                            </button>
                            <button type="button" class="fiacces-toggle" data-action="cursor" data-value="large" aria-pressed="false">
                                <?php esc_html_e( 'Grande', 'fiacces' ); ?>
                            </button>
                            <button type="button" class="fiacces-toggle" data-action="cursor" data-value="xl" aria-pressed="false">
                                <?php esc_html_e( 'Extra grande', 'fiacces' ); ?>
                            </button>
                        </div>
                    </section>
                    <?php endif; ?>

                </div>

                <div class="fiacces-panel__footer">
                    <button type="button" id="fiacces-reset" class="fiacces-btn fiacces-btn--secondary">
                        <?php esc_html_e( 'Restablecer todo', 'fiacces' ); ?>
                    </button>
                </div>

                <div id="fiacces-announce" class="fiacces-sr-only" role="status" aria-live="polite"></div>
            </div>
        </div>
        <?php
    }
}
