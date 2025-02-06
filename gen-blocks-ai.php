<?php

 /**
 * Gen Blocks - AI block builder for WordPress
 *
 * @package       Gen Blocks
 * @author        Max Moss
 *
 * @wordpress-plugin
 * Plugin Name:       Gen Blocks - AI block builder for WordPress
 * Plugin URI:        https://github.com/max-moss-dev/gen-blocks-ai
 * Description:       Create editable Gutenberg blocks with AI.
 * Version:           1.0.0
 * Author:            Max Moss
 * Text Domain:       genb
 * Requires PHP:      7.4
 * Requires at least: 6.0
 */

namespace GENB;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

define('GENB_VERSION', '1.0.0');
define('GENB_PLUGIN_FILE', __FILE__);
define('GENB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GENB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required files
require_once GENB_PLUGIN_DIR . 'includes/class-block-storage.php';
require_once GENB_PLUGIN_DIR . 'includes/class-template-parser.php';
require_once GENB_PLUGIN_DIR . 'includes/class-block-registrar.php';
require_once GENB_PLUGIN_DIR . 'includes/class-admin-ui.php';
require_once GENB_PLUGIN_DIR . 'includes/config.php';
require_once GENB_PLUGIN_DIR . 'includes/prompts.php';

/**
 * Initialize plugin components
 *
 * @return void
 */
function initialize() {
    $storage = new Block_Storage();
    $storage->init();
    
    $template_parser = new Template_Parser();
    $block_registrar = new Block_Registrar($storage, $template_parser);
    $block_registrar->init();

    $admin_ui = new Admin_UI($storage);
    $admin_ui->init();
}

add_action('plugins_loaded', __NAMESPACE__ . '\initialize');