<?php
/**
 * Admin: página de configuración en wp-admin.
 *
 * @package FIAcces
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FIAcces_Admin {

    public static function init() {
        add_action( 'admin_menu',          array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init',          array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . FIACCES_PLUGIN_BASENAME, array( __CLASS__, 'add_settings_link' ) );
    }

    /** Agrega enlace "Configuración" en la lista de plugins. */
    public static function add_settings_link( $links ) {
        $url           = admin_url( 'options-general.php?page=fiacces-settings' );
        $settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Configuración', 'fiacces' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /** Añade la página de ajustes bajo Ajustes > FIAcces. */
    public static function add_menu() {
        add_options_page(
            __( 'FIAcces', 'fiacces' ),
            __( 'FIAcces', 'fiacces' ),
            'manage_options',
            'fiacces-settings',
            array( __CLASS__, 'render_page' )
        );
    }

    /** Registra la opción del plugin con su sanitización. */
    public static function register_settings() {
        register_setting(
            'fiacces_settings_group',
            FIACCES_OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( 'FIAcces_Settings', 'sanitize' ),
                'default'           => FIAcces_Settings::get_defaults(),
            )
        );
    }

    /** Encola assets en la página del plugin. */
    public static function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_fiacces-settings' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_style(
            'fiacces-admin',
            FIACCES_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FIACCES_VERSION
        );

        wp_enqueue_script(
            'fiacces-admin',
            FIACCES_PLUGIN_URL . 'assets/js/admin.js',
            array( 'wp-color-picker', 'jquery' ),
            FIACCES_VERSION,
            true
        );
    }

    /** Renderiza la página de ajustes. */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $opts = FIAcces_Settings::get_options();
        ?>
        <div class="wrap fiacces-admin-wrap">
            <h1><?php esc_html_e( 'FIAcces — Configuración', 'fiacces' ); ?></h1>
            <p class="fiacces-admin-intro">
                <?php esc_html_e( 'Configura cómo aparece la barra de accesibilidad WCAG 2.1 en tu sitio.', 'fiacces' ); ?>
            </p>

            <form action="options.php" method="post" class="fiacces-admin-form">
                <?php settings_fields( 'fiacces_settings_group' ); ?>

                <div class="fiacces-admin-grid">

                    <div class="fiacces-admin-card">
                        <h2><?php esc_html_e( 'Apariencia', 'fiacces' ); ?></h2>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="fiacces_button_position"><?php esc_html_e( 'Posición del botón flotante', 'fiacces' ); ?></label>
                                </th>
                                <td>
                                    <select name="<?php echo esc_attr( FIACCES_OPTION_KEY ); ?>[button_position]" id="fiacces_button_position">
                                        <option value="bottom-right" <?php selected( $opts['button_position'], 'bottom-right' ); ?>><?php esc_html_e( 'Abajo a la derecha', 'fiacces' ); ?></option>
                                        <option value="bottom-left"  <?php selected( $opts['button_position'], 'bottom-left' ); ?>><?php esc_html_e( 'Abajo a la izquierda', 'fiacces' ); ?></option>
                                        <option value="top-right"    <?php selected( $opts['button_position'], 'top-right' ); ?>><?php esc_html_e( 'Arriba a la derecha', 'fiacces' ); ?></option>
                                        <option value="top-left"     <?php selected( $opts['button_position'], 'top-left' ); ?>><?php esc_html_e( 'Arriba a la izquierda', 'fiacces' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="fiacces_primary_color"><?php esc_html_e( 'Color primario', 'fiacces' ); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           name="<?php echo esc_attr( FIACCES_OPTION_KEY ); ?>[primary_color]"
                                           id="fiacces_primary_color"
                                           value="<?php echo esc_attr( $opts['primary_color'] ); ?>"
                                           class="fiacces-color-picker"
                                           data-default-color="#2563EB">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="fiacces_shortcut_key"><?php esc_html_e( 'Atajo de teclado', 'fiacces' ); ?></label>
                                </th>
                                <td>
                                    <code>Alt +</code>
                                    <input type="text"
                                           name="<?php echo esc_attr( FIACCES_OPTION_KEY ); ?>[shortcut_key]"
                                           id="fiacces_shortcut_key"
                                           value="<?php echo esc_attr( $opts['shortcut_key'] ); ?>"
                                           maxlength="1"
                                           style="width: 50px; text-align: center; text-transform: uppercase;">
                                    <p class="description"><?php esc_html_e( 'Una sola letra (A-Z). Combina con la tecla Alt para abrir/cerrar el panel.', 'fiacces' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Dispositivos móviles', 'fiacces' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( FIACCES_OPTION_KEY ); ?>[show_on_mobile]"
                                               value="1"
                                               <?php checked( $opts['show_on_mobile'] ); ?>>
                                        <?php esc_html_e( 'Mostrar también en móviles', 'fiacces' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="fiacces-admin-card">
                        <h2><?php esc_html_e( 'Funcionalidades', 'fiacces' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Activa o desactiva qué tarjetas verán los usuarios en el panel de accesibilidad.', 'fiacces' ); ?></p>

                        <table class="form-table" role="presentation">
                            <?php
                            $feature_labels = array(
                                'text_size'    => __( 'Ajuste de tamaño de texto', 'fiacces' ),
                                'contrast'     => __( 'Modos de contraste', 'fiacces' ),
                                'readability'  => __( 'Legibilidad (dislexia, subrayado)', 'fiacces' ),
                                'animations'   => __( 'Pausar animaciones', 'fiacces' ),
                                'cursor'       => __( 'Cursor grande', 'fiacces' ),
                                'keyboard_nav' => __( 'Navegación con teclado mejorada', 'fiacces' ),
                            );
                            foreach ( $feature_labels as $key => $label ) :
                                ?>
                                <tr>
                                    <th scope="row"><label for="fiacces_feat_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                                    <td>
                                        <label class="fiacces-admin-switch">
                                            <input type="checkbox"
                                                   id="fiacces_feat_<?php echo esc_attr( $key ); ?>"
                                                   name="<?php echo esc_attr( FIACCES_OPTION_KEY ); ?>[features][<?php echo esc_attr( $key ); ?>]"
                                                   value="1"
                                                   <?php checked( ! empty( $opts['features'][ $key ] ) ); ?>>
                                            <?php esc_html_e( 'Activada', 'fiacces' ); ?>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                </div>

                <div class="fiacces-admin-actions">
                    <?php submit_button( __( 'Guardar cambios', 'fiacces' ), 'primary large' ); ?>
                    <button type="button" class="button button-secondary" id="fiacces-export-btn">
                        <?php esc_html_e( 'Exportar configuración', 'fiacces' ); ?>
                    </button>
                </div>
            </form>

            <div class="fiacces-admin-info">
                <h2><?php esc_html_e( '¿Cómo se ve?', 'fiacces' ); ?></h2>
                <p>
                    <?php
                    printf(
                        wp_kses(
                            /* translators: %s: URL del sitio */
                            __( 'Visita la <a href="%s" target="_blank" rel="noopener">página principal</a> de tu sitio para ver el botón de accesibilidad en acción.', 'fiacces' ),
                            array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
                        ),
                        esc_url( home_url() )
                    );
                    ?>
                </p>
                <p class="description">
                    <?php esc_html_e( 'Cumplimiento: WCAG 2.1 nivel AA. Versión:', 'fiacces' ); ?>
                    <code><?php echo esc_html( FIACCES_VERSION ); ?></code>
                </p>
            </div>
        </div>
        <?php
    }
}
