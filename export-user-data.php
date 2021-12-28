<?php

/**
 * Export User Data
 *
 * @package         Export User Data
 * @author          Q Studio <social@qstudio.us>
 * @license         GPL-2.0+
 * @copyright       2020 Q Studio
 *
 * @wordpress-plugin
 * Plugin Name:     Export User Data
 * Plugin URI:      http://qstudio.us/releases/export-user-data
 * Description:     Export User data and metadata.
 * Version:         2.2.6
 * Author:          Q Studio
 * Author URI:      https://qstudio.us
 * License:         GPL-2.0+
 * Requires PHP:    7.0 
 * Copyright:       Q Studio
 * Namespace:		q\eud
 * API:        		export_user_data
 * Text Domain:     export-user-data
 * Domain Path:     /languages
 * GitHub Plugin URI: qstudio/export-user-data
*/

// namespace plugin ##
namespace q\eud;

// import ##
use q\eud;
use q\eud\core\helper as h;

// If this file is called directly, Bulk!
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// plugin activation hook to store current application and plugin state ##
\register_activation_hook( __FILE__, [ '\\q\\eud\\plugin', 'activation_hook' ] );

// plugin deactivation hook - clear stored data ##
\register_deactivation_hook( __FILE__, [ '\\q\\eud\\plugin', 'deactivation_hook' ] );

// required bits to get set-up ##
require_once __DIR__ . '/library/api/function.php';
require_once __DIR__ . '/autoload.php'; 
require_once __DIR__ . '/plugin.php';
require_once __DIR__ . '/vendor/PHP_XLSXWriter/xlsxwriter.class.php';

// get plugin instance ##
$_plugin = plugin::get_instance();

// validate instance ##
if( ! ( $_plugin instanceof \q\eud\plugin ) ) {

	error_log( 'Error in Export User Data plugin instance' );

	// nothing else to do here ##
	return;

}

// fire hooks - build log, helper and config objects and translations ## 
\add_action( 'init', function() use( $_plugin ){

	// set text domain on init hook ##
	\add_action( 'init', [ $_plugin, 'load_plugin_textdomain' ], 1 );
	
	// check debug settings ##
	\add_action( 'plugins_loaded', [ $_plugin, 'debug' ], 11 );

}, 0 );

// build export object ##
$_export = new eud\core\export( $_plugin );

// build filters object ##
$_filters = new eud\core\filters( $_plugin );

// build user object ##
$_user = new eud\core\user( $_plugin );

// build admin object ##
$_admin = new eud\admin\render( $_plugin, $_user );

// build buddypress object ##
// $_buddypress = new eud\core\buddypress( $_plugin );

if ( \is_admin() ){

	// run export ##
	\add_action( 'admin_init', [ $_export, 'render' ], 1000003 );

	// load BP ##
	// \add_action( 'admin_init', [ $_buddypress, 'load' ], 1000001 );

	// EUD - filter key shown ##
	\add_filter( 'q/eud/admin/display_key', [ $_filters, 'display_key' ], 1, 1 );

	// user option ##
	\add_action( 'admin_init', [ $_user, 'load' ], 1000002 );

	// add export menu inside admin ##
	\add_action( 'admin_menu', [ $_admin, 'add_menu' ] );

	// UI style and functionality ##
	\add_action( 'admin_enqueue_scripts', [ $_admin, 'admin_enqueue_scripts' ], 1 );
	\add_action( 'admin_footer', [ $_admin, 'jquery' ], 100000 );
	\add_action( 'admin_footer', [ $_admin, 'css' ], 100000 );

}
