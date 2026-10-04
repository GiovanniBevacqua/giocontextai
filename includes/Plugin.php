<?php
namespace GioContextAi;

class Plugin {
    protected static $instance = null;
    public $generator;
    public $settings;
    public $metaboxes;
    public $analytics;
    public $products;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {        
        $this->generator = new Generator();
        $this->settings  = new Settings();
        $this->metaboxes = new Metaboxes();
        $this->analytics = new Analytics();
        $this->products = new Products();

        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_menu', [ $this, 'add_menu' ] );
    }

    public static function enqueue_assets() {
        wp_enqueue_style(
            'giocontextai-admin-style',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . '../assets/css/admin.css')
        );
    }

    public function add_menu() {
        $menu_title = __('GioContextAi', 'giocontextai');
        $menu_title = apply_filters('giocontextai/menu_title', $menu_title, $this);

        add_menu_page(
            __('GioContextAi', 'giocontextai'),
            $menu_title,
            'manage_options',
            'giocontextai',
            [ $this->settings, 'settings_page' ],
            'dashicons-rest-api',
            60
        );

        add_submenu_page(
            'giocontextai',
            __('Settings & Configuration', 'giocontextai'),
            __('Settings', 'giocontextai'),
            'manage_options',
            'giocontextai',
            [ $this->settings, 'settings_page' ]
        );

        add_submenu_page(
            'giocontextai',
            __('AI Analytics & Readiness', 'giocontextai'),
            __('AI Analytics', 'giocontextai'),
            'manage_options',
            'giocontextai-analytics',
            [ $this->analytics, 'render_analytics_page' ]
        );
    }
}