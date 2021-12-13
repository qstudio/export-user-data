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
 * Version:         2.2.2
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
$plugin = plugin::get_instance();

// validate instance ##
if( ! ( $plugin instanceof \q\eud\plugin ) ) {

	error_log( 'Error in Export User Data plugin instance' );

	// nothing else to do here ##
	return;

}

// fire hooks - build log, helper and config objects and translations ## 
\add_action( 'init', function() use( $plugin ){

	// set text domain on init hook ##
	\add_action( 'init', [ $plugin, 'load_plugin_textdomain' ], 1 );
	
	// check debug settings ##
	\add_action( 'plugins_loaded', [ $plugin, 'debug' ], 11 );

}, 0 );

// build export object ##
$export = new eud\core\export( $plugin );

// build filters object ##
$filters = new eud\core\filters( $plugin );

// build user object ##
$user = new eud\core\user( $plugin );

// build admin object ##
$admin = new eud\admin\render( $plugin, $user );

// build buddypress object ##
// $buddypress = new eud\core\buddypress();

if ( \is_admin() ){

	// run export ##
	\add_action( 'admin_init', [ $export, 'render' ], 1000003 );

	// load BP ##
	// \add_action( 'admin_init', [ $buddypress, 'load' ], 1000001 );

	// EUD - filter key shown ##
	\add_filter( 'q/eud/admin/display_key', [ $filters, 'display_key' ], 1, 1 );

	// user option ##
	\add_action( 'admin_init', [ $user, 'load' ], 1000002 );

	// add export menu inside admin ##
	\add_action( 'admin_menu', [ $admin, 'add_menu' ] );

	// UI style and functionality ##
	\add_action( 'admin_enqueue_scripts', [ $admin, 'admin_enqueue_scripts' ], 1 );
	\add_action( 'admin_footer', [ $admin, 'jquery' ], 100000 );
	\add_action( 'admin_footer', [ $admin, 'css' ], 100000 );

}
