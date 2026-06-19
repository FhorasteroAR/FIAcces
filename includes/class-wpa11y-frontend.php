<?php
/**
 * Frontend: encolado de assets y renderizado del panel de accesibilidad.
 *
 * @package WP_Accesibilidad_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA11Y_Frontend {

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

        $opts = WPA11Y_Settings::get_options();

        // No cargar en móvil si así se configuró
        if ( ! $opts['show_on_mobile'] && wp_is_mobile() ) {
            return;
        }

        wp_enqueue_style(
            'wpa11y-frontend',
            WPA11Y_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WPA11Y_VERSION
        );

        wp_enqueue_script(
            'wpa11y-frontend',
            WPA11Y_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            WPA11Y_VERSION,
            true
        );

        wp_localize_script(
            'wpa11y-frontend',
            'WPA11Y',
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
        ?>
        <script>
        (function(){
            try {
                var raw = localStorage.getItem('wpa11y_prefs');
                if (!raw) return;
                var p = JSON.parse(raw);
                var b = document.documentElement;
                if (p.textScale) b.style.setProperty('--wpa11y-text-scale', p.textScale);
                if (p.contrast)  b.classList.add('wpa11y-contrast-' + p.contrast);
                if (p.dyslexia)  b.classList.add('wpa11y-dyslexia');
                if (p.underline) b.classList.add('wpa11y-underline-links');
                if (p.pauseAnim) b.classList.add('wpa11y-pause-animations');
                if (p.cursor)    b.classList.add('wpa11y-cursor-' + p.cursor);
            } catch(e) {}
        })();
        </script>
        <?php
    }

    /** Strings traducibles para la UI. */
    private static function get_i18n_strings() {
        return array(
            'open_panel'        => __( 'Abrir herramientas de accesibilidad', 'wp-accesibilidad-a11y' ),
            'close_panel'       => __( 'Cerrar', 'wp-accesibilidad-a11y' ),
            'title'             => __( 'Accesibilidad', 'wp-accesibilidad-a11y' ),
            'text_size'         => __( 'Tamaño del texto', 'wp-accesibilidad-a11y' ),
            'increase'          => __( 'Aumentar', 'wp-accesibilidad-a11y' ),
            'decrease'          => __( 'Disminuir', 'wp-accesibilidad-a11y' ),
            'contrast'          => __( 'Contraste', 'wp-accesibilidad-a11y' ),
            'contrast_normal'   => __( 'Normal', 'wp-accesibilidad-a11y' ),
            'contrast_high'     => __( 'Alto contraste', 'wp-accesibilidad-a11y' ),
            'contrast_inverted' => __( 'Invertir colores', 'wp-accesibilidad-a11y' ),
            'contrast_gray'     => __( 'Escala de grises', 'wp-accesibilidad-a11y' ),
            'readability'       => __( 'Legibilidad', 'wp-accesibilidad-a11y' ),
            'dyslexia_font'     => __( 'Fuente para dislexia', 'wp-accesibilidad-a11y' ),
            'underline_links'   => __( 'Subrayar enlaces', 'wp-accesibilidad-a11y' ),
            'animations'        => __( 'Animaciones', 'wp-accesibilidad-a11y' ),
            'pause_animations'  => __( 'Pausar animaciones', 'wp-accesibilidad-a11y' ),
            'cursor'            => __( 'Cursor', 'wp-accesibilidad-a11y' ),
            'cursor_large'      => __( 'Grande', 'wp-accesibilidad-a11y' ),
            'cursor_xl'         => __( 'Extra grande', 'wp-accesibilidad-a11y' ),
            'reset'             => __( 'Restablecer todo', 'wp-accesibilidad-a11y' ),
            'applied'           => __( 'Ajuste aplicado', 'wp-accesibilidad-a11y' ),
            'announce_open'     => __( 'Panel de accesibilidad abierto', 'wp-accesibilidad-a11y' ),
            'announce_close'    => __( 'Panel de accesibilidad cerrado', 'wp-accesibilidad-a11y' ),
        );
    }

    /** Imprime el HTML del botón flotante y el panel modal en el footer. */
    public static function render_widget() {
        if ( is_admin() ) {
            return;
        }
        $opts = WPA11Y_Settings::get_options();
        if ( ! $opts['show_on_mobile'] && wp_is_mobile() ) {
            return;
        }
        $features = $opts['features'];
        $position = esc_attr( $opts['button_position'] );
        ?>
        <div id="wpa11y-root" class="wpa11y-pos-<?php echo $position; ?>" style="--wpa11y-primary: <?php echo esc_attr( $opts['primary_color'] ); ?>;">

            <button type="button"
                    id="wpa11y-toggle"
                    class="wpa11y-fab"
                    aria-label="<?php esc_attr_e( 'Abrir herramientas de accesibilidad', 'wp-accesibilidad-a11y' ); ?>"
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="wpa11y-panel">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="10"/>
                    <circle cx="12" cy="7" r="1.5" fill="currentColor"/>
                    <path d="M8 11h8M12 11v6M9 17l3-4M15 17l-3-4"/>
                </svg>
                <span class="wpa11y-sr-only"><?php esc_html_e( 'Accesibilidad', 'wp-accesibilidad-a11y' ); ?></span>
            </button>

            <div id="wpa11y-panel"
                 class="wpa11y-panel"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="wpa11y-panel-title"
                 hidden>

                <div class="wpa11y-panel__header">
                    <h2 id="wpa11y-panel-title" class="wpa11y-panel__title">
                        <?php esc_html_e( 'Accesibilidad', 'wp-accesibilidad-a11y' ); ?>
                    </h2>
                    <button type="button"
                            id="wpa11y-close"
                            class="wpa11y-close"
                            aria-label="<?php esc_attr_e( 'Cerrar panel de accesibilidad', 'wp-accesibilidad-a11y' ); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="wpa11y-panel__body">

                    <?php if ( $features['text_size'] ) : ?>
                    <section class="wpa11y-card" aria-labelledby="wpa11y-card-text">
                        <h3 id="wpa11y-card-text" class="wpa11y-card__title">
                            <?php esc_html_e( 'Tamaño del texto', 'wp-accesibilidad-a11y' ); ?>
                        </h3>
                        <div class="wpa11y-card__controls">
                            <button type="button" class="wpa11y-btn" data-action="text-decrease" aria-label="<?php esc_attr_e( 'Disminuir tamaño de texto', 'wp-accesibilidad-a11y' ); ?>">A−</button>
                            <span class="wpa11y-value" data-display="text-scale" aria-live="polite">100%</span>
                            <button type="button" class="wpa11y-btn" data-action="text-increase" aria-label="<?php esc_attr_e( 'Aumentar tamaño de texto', 'wp-accesibilidad-a11y' ); ?>">A+</button>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $features['contrast'] ) : ?>
                    <section class="wpa11y-card" aria-labelledby="wpa11y-card-contrast">
                        <h3 id="wpa11y-card-contrast" class="wpa11y-card__title">
                            <?php esc_html_e( 'Contraste y color', 'wp-accesibilidad-a11y' ); ?>
                        </h3>
                        <div class="wpa11y-card__grid">
                            <button type="button" class="wpa11y-toggle" data-action="contrast" data-value="" aria-pressed="true">
                                <?php esc_html_e( 'Normal', 'wp-accesibilidad-a11y' ); ?>
                            </button>
                            <button type="button" class="wpa11y-toggle" data-action="contrast" data-value="high" aria-pressed="false">
                                <?php esc_html_e( 'Alto contraste', 'wp-accesibilidad-a11y' ); ?>
                            </button>
                            <button type="button" class="wpa11y-toggle" data-action="contrast" data-value="inverted" aria-pressed="false">
                                <?php esc_html_e( 'Invertir colores', 'wp-accesibilidad-a11y' ); ?>
                            </button>
                            <button type="button" class="wpa11y-toggle" data-action="contrast" data-value="gray" aria-pressed="false">
                                <?php esc_html_e( 'Escala de grises', 'wp-accesibilidad-a11y' ); ?>
                            </button>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $features['readability'] ) : ?>
                    <section class="wpa11y-card" aria-labelledby="wpa11y-card-read">
                        <h3 id="wpa11y-card-read" class="wpa11y-card__title">
                            <?php esc_html_e( 'Legibilidad', 'wp-accesibilidad-a11y' ); ?>
                        </h3>
                        <div class="wpa11y-card__list">
                            <label class="wpa11y-switch">
                                <input type="checkbox" data-action="dyslexia">
                                <span class="wpa11y-switch__slider" aria-hidden="true"></span>
                                <span class="wpa11y-switch__label">
                                    <?php esc_html_e( 'Fuente para dislexia', 'wp-accesibilidad-a11y' ); ?>
                                </span>
                            </label>
                            <label class="wpa11y-switch">
                                <input type="checkbox" data-action="underline">
                                <span class="wpa11y-switch__slider" aria-hidden="true"></span>
                                <span class="wpa11y-switch__label">
                                    <?php esc_html_e( 'Subrayar enlaces', 'wp-accesibilidad-a11y' ); ?>
                                </span>
                            </label>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $features['animations'] ) : ?>
                    <section class="wpa11y-card" aria-labelledby="wpa11y-card-anim">
                        <h3 id="wpa11y-card-anim" class="wpa11y-card__title">
                            <?php esc_html_e( 'Animaciones', 'wp-accesibilidad-a11y' ); ?>
                        </h3>
                        <div class="wpa11y-card__list">
                            <label class="wpa11y-switch">
                                <input type="checkbox" data-action="pause-anim">
                                <span class="wpa11y-switch__slider" aria-hidden="true"></span>
                                <span class="wpa11y-switch__label">
                                    <?php esc_html_e( 'Pausar animaciones, sliders y vídeos', 'wp-accesibilidad-a11y' ); ?>
                                </span>
                            </label>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $features['cursor'] ) : ?>
                    <section class="wpa11y-card" aria-labelledby="wpa11y-card-cursor">
                        <h3 id="wpa11y-card-cursor" class="wpa11y-card__title">
                            <?php esc_html_e( 'Cursor', 'wp-accesibilidad-a11y' ); ?>
                        </h3>
                        <div class="wpa11y-card__grid">
                            <button type="button" class="wpa11y-toggle" data-action="cursor" data-value="" aria-pressed="true">
                                <?php esc_html_e( 'Normal', 'wp-accesibilidad-a11y' ); ?>
                            </button>
                            <button type="button" class="wpa11y-toggle" data-action="cursor" data-value="large" aria-pressed="false">
                                <?php esc_html_e( 'Grande', 'wp-accesibilidad-a11y' ); ?>
                            </button>
                            <button type="button" class="wpa11y-toggle" data-action="cursor" data-value="xl" aria-pressed="false">
                                <?php esc_html_e( 'Extra grande', 'wp-accesibilidad-a11y' ); ?>
                            </button>
                        </div>
                    </section>
                    <?php endif; ?>

                </div>

                <div class="wpa11y-panel__footer">
                    <button type="button" id="wpa11y-reset" class="wpa11y-btn wpa11y-btn--secondary">
                        <?php esc_html_e( 'Restablecer todo', 'wp-accesibilidad-a11y' ); ?>
                    </button>
                </div>

                <div id="wpa11y-announce" class="wpa11y-sr-only" role="status" aria-live="polite"></div>
            </div>
        </div>
        <?php
    }
}
