<?php

namespace q\eud;

// import classes ##
use q\eud;
use q\eud\plugin as plugin;
use q\eud\core\helper as h;

// If this file is called directly, Bulk!
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/*
* Main Plugin Class
*/
final class plugin {

    /**
     * Instance
     *
     * @var     Object      $_instance
     */
	private static $_instance;

	public static 
	
		// current tag ##
		$_version = '2.2.6',

		// debugging control ##
		$_debug = \WP_DEBUG
	
	;

	/**
	 * Props
	 * 
	 * @var		$props
	*/
	private 

		$_q_eud_exports = '', // export settings ##
		$_usermeta_saved_fields = [],
		$_role = '',
		$_roles = '0',
		$_user_fields = '1',
		$_groups = '0',
		$_start_date = '',
		$_end_date = '',
		$_limit_offset = '',
		$_limit_total = '',
		$_updated_since_date = '',
		$_format = '',
		$_allowed_tags = '',
 
		// api ##
		$_api_admin_fields = false

	;

    /**
     * Initiator
     *
     * @since   0.0.2
     * @return  Object    
     */
    public static function get_instance() {

        // object defined once --> singleton ##
        if ( 
            isset( self::$_instance ) 
            && NULL !== self::$_instance
        ){

            return self::$_instance;

        }

        // create an object, if null ##
        self::$_instance = new self;

        // store instance in filter, for potential external access ##
        \add_filter( __NAMESPACE__.'/instance', function() {

            return self::$_instance;
            
        });

        // return the object ##
        return self::$_instance; 

    }

    /**
     * Class constructor to define object props --> empty
     * 
     * @since   0.0.1
     * @return  void
    */
    private function __construct() {

		// empty ...

	}
	
    /**
     * Get stored object property
	 * 
     * @todo	Make this work with single props, not from an array 
     * @param   $key    string
     * @since   0.0.2
     * @return  Mixed
    */
    public function get( string $key = null )
	{

        // check if key set ##
        if( is_null( $key ) ){

            // return instance ##
			return self::get_instance();

        }
        
        // return if isset ##
        return $this->{$key} ?? false ;

    }

    /**
     * Set stored object properties 
     * 
	 * @todo	Make this work with single props, not from an array
     * @param   $key    string
     * @param   $value  Mixed
     * @since   0.0.2
     * @return  Mixed
    */
    public function set( string $key = null, $value = null )
	{

        // sanity ##
        if( 
            is_null( $key ) 
        ){

            return false;

        }

        // set new value ##
		return $this->{$key} = $value;

    }

    /**
     * Load Text Domain for translations
     *
     * @since       0.0.1
     * @return      Void
     */
    public function load_plugin_textdomain(){

        // The "plugin_locale" filter is also used in load_plugin_textdomain()
        $locale = apply_filters( 'plugin_locale', \get_locale(), 'export-user-data' );

        // try from global WP location first ##
        \load_textdomain( 'export-user-data', WP_LANG_DIR.'/plugins/export-user-data-'.$locale.'.mo' );

        // try from plugin last ##
        \load_plugin_textdomain( 'export-user-data', FALSE, \plugin_dir_path( __FILE__ ).'library/languages/' );

    }

    /**
     * Plugin activation
     *
     * @since   0.0.1
     * @return  void
     */
    public static function activation_hook(){

        // check user caps ##
        if ( ! \current_user_can( 'activate_plugins' ) ) {
            
            return;

        }

        $_plugin = isset( $_REQUEST['plugin'] ) ? \sanitize_text_field( $_REQUEST['plugin'] ) : '';
        \check_admin_referer( "activate-plugin_{$_plugin}" );

        // store data about the current plugin state at activation point ##
        $_config = [
            'configured'            => true , 
            'version'               => self::$_version ,
            'wp'                    => \get_bloginfo( 'version' ) ?? null ,
			'timestamp'             => time(),
		];
		
        // activation running, so update configuration flag ##
        \update_option( 'plugin_export_user_data', $_config, true );

    }

    /**
     * Plugin deactivation
     *
     * @since   0.0.1
     * @return  void
     */
    public static function deactivation_hook(){

        // check user caps ##
        if ( ! \current_user_can( 'activate_plugins' ) ) {
        
            return;
        
        }

        $_plugin = isset( $_REQUEST['plugin'] ) ? \sanitize_text_field($_REQUEST['plugin'] ) : '';
        \check_admin_referer( "deactivate-plugin_{$_plugin}" );

        // de-configure plugin ##
        \delete_option('plugin_export_user_data');

	}
	
	/**
	 * We want the debugging to be controlled in global and local steps
	 * If Q debug is true -- all debugging is true
	 * else follow settings in Q, or this plugin $debug variable
	 */
	public function debug(){

		// define debug ##
		$this->_debug = 
			( 
				class_exists( 'Q' )
				&& true === \Q::$debug
			) ?
			true :
			$this->_debug;

		return $this->_debug;

	}

    /**
     * Get Plugin URL
     *
     * @since       0.1
     * @param       string      $path   Path to plugin directory
     * @return      string      Absoulte URL to plugin directory
     */
    public static function get_plugin_url( $path = '' ){

        return \plugins_url( $path, __FILE__ );

    }

    /**
     * Get Plugin Path
     *
     * @since       0.1
     * @param       string      $path   Path to plugin directory
     * @return      string      Absoulte URL to plugin directory
     */
    public static function get_plugin_path( $path = '' ){

        return \plugin_dir_path( __FILE__ ).$path;

    }
    
}
