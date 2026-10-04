<?php
/**
 * Plugin Name:       GioContextAi
 * Plugin URI:        https://giosuite.com/giocontextai
 * Description:       Open your WordPress site to AI crawlers and LLMs. Automatically generates llms.txt, llms-full.txt, and ai.txt files.
 * Version:           1.2.2
 * Requires at least: 6.0
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Giovanni Bevacqua
 * Author URI:        https://www.linkedin.com/in/giovanni-bevacqua/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       giocontextai
 */

namespace GioContextAi;

if ( !defined( 'ABSPATH' ) ) exit;

define( 'GIOCONTEXT_AI_LITE_VERSION', '1.2.2' );
define( 'GIOCONTEXT_AI_SITE_URL', 'https://giosuite.com/giocontextai' );

spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__ . '\\';
    $base_dir = __DIR__ . '/includes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

add_action('plugins_loaded', function() {
    Plugin::instance();
});