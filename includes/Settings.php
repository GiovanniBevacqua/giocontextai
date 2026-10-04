<?php
namespace GioContextAi;

class Settings {
    public function __construct() {
        add_action('admin_init', [ $this, 'register_settings' ]);
    }

    public function register_settings() {
        register_setting('giocontextai_settings', 'giocontextai_included_post_types', [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_post_types'],
            'default'           => ['post', 'page']
        ]);
        
        do_action('giocontextai_register_pro_settings');
    }

    public function sanitize_post_types($input) {
        if (!is_array($input)) {
            return ['post', 'page'];
        }
        return array_map('sanitize_text_field', $input);
    }

    public function settings_page() {
        $included_types = get_option('giocontextai_included_post_types', ['post', 'page']);
        $post_types = get_post_types(['public' => true], 'objects');
        unset($post_types['attachment']);
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab navigation, not a state-changing request.
        $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
        $feed_url = home_url( '/products.json' );

        ?>
        <div class="wrap giocontextai-settings">
            <?php do_action('giocontextai/lite_notice'); ?>
            
            <div>
                <h1><?php esc_html_e('GioContextAi Settings', 'giocontextai'); ?></h1>
            </div>

            <div class="notice notice-info inline giocontextai-settings-notice">
                <p>
                    <strong><?php esc_html_e('Important Note on Endpoints:', 'giocontextai'); ?></strong> 
                    <?php esc_html_e('If your virtual files return a 404 error, please go to', 'giocontextai'); ?> 
                    <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>"><?php esc_html_e('Settings > Permalinks', 'giocontextai'); ?></a> 
                    <?php esc_html_e('and click "Save Changes" to flush the rewrite rules.', 'giocontextai'); ?>
                </p>
            </div>

            <?php do_action('giocontextai_before_settings_tabs'); ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=giocontextai&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General', 'giocontextai'); ?>
                </a>
                <a href="?page=giocontextai&tab=context" class="nav-tab <?php echo $active_tab === 'context' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Context Files', 'giocontextai'); ?>
                </a>
                <a href="?page=giocontextai&tab=commerce" class="nav-tab <?php echo $active_tab === 'commerce' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('AI Product Feed', 'giocontextai'); ?>
                </a>
                <?php do_action('giocontextai_settings_tabs', $active_tab); ?>
            </h2>

            <div class="card">

            <?php if ( $active_tab === 'general' ) : ?>
                <h3><?php esc_html_e('General Settings', 'giocontextai'); ?></h3>
                <p><?php esc_html_e('Configure core plugin options and select which content types to include in the AI context.', 'giocontextai'); ?></p>
                <hr/>
                <form method="post" action="options.php">
                    <?php
                        settings_fields('giocontextai_settings');
                        do_settings_sections('giocontextai_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Included Post Types', 'giocontextai'); ?></th>
                            <td>
                                <?php foreach ( $post_types as $type ) : ?>
                                    <label class="giocontextai-checkbox-label">
                                        <input type="checkbox" name="giocontextai_included_post_types[]" 
                                               value="<?php echo esc_attr($type->name); ?>" 
                                               <?php checked(in_array($type->name, (array)$included_types)); ?>>
                                        <?php echo esc_html($type->label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <?php submit_button(__('Save Changes', 'giocontextai'), 'primary', 'submit', false); ?>
                    </p>
                </form>

            <?php elseif ( $active_tab === 'context' ) : ?>
                <h3><?php esc_html_e('AI Context Files Preview', 'giocontextai'); ?></h3>
                <p><?php esc_html_e('Quickly access and verify the context files exposed to AI crawlers on your site.', 'giocontextai'); ?></p>
                <p>
                    <a href="<?php echo esc_url(home_url('/llms.txt')); ?>" target="_blank" class="button button-secondary">
                        <?php esc_html_e('Preview llms.txt', 'giocontextai'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/llms-full.txt')); ?>" target="_blank" class="button button-secondary">
                        <?php esc_html_e('Preview llms-full.txt', 'giocontextai'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/ai.txt')); ?>" target="_blank" class="button button-secondary">
                        <?php esc_html_e('Preview ai.txt', 'giocontextai'); ?>
                    </a>
                </p>

            <?php elseif ( $active_tab === 'commerce' ) : ?>
                <h3><?php esc_html_e( 'AI Product Feed Status', 'giocontextai' ); ?></h3>
                <p>
                    <?php 
                    $pro_feed_desc = apply_filters( 
                        'giocontextai/commerce_feed_description', 
                        __( 'Your WooCommerce catalog is automatically compiled into a machine-readable format designed for AI commerce platforms and tools.', 'giocontextai' ) 
                    );
                    echo esc_html( $pro_feed_desc ); 
                    ?>
                </p>
                
                <div class="gca-feed-actions">
                    <input type="text" value="<?php echo esc_url( $feed_url ); ?>" readonly class="large-text code">
                    <a href="<?php echo esc_url( $feed_url ); ?>" target="_blank" class="button button-primary">
                        <?php esc_html_e( 'Preview JSON', 'giocontextai' ); ?>
                    </a>
                </div>
                
                <p class="description">
                    <?php 
                    $pro_feed_note = apply_filters( 
                        'giocontextai/commerce_feed_note', 
                        __( 'Note: This feed requires WooCommerce to be active on your site. No additional configuration is required for the Lite version.', 'giocontextai' ) 
                    );
                    echo esc_html( $pro_feed_note ); 
                    ?>
                </p>
            <?php endif; ?>
            <?php do_action('giocontextai_settings_tab_content', $active_tab); ?>
            </div>
        </div>
        <?php
    }
}