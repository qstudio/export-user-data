<?php

/*
 * Plugin Name:     Export User Data
 * Description:     Export User data and metadata.
 * Version:         2.1.3
 * Author:          Q Studio
 * Author URI:      http://qstudio.us/
 * License:         GPL2
 * Class:           q_export_user_data
 * Text Domain:     q-export-user-data
 * GitHub Plugin URI: qstudio/export-user-data
*/

defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'q_export_user_data' ) ) {
    
    // instatiate plugin via WP plugins_loaded - init is too late for CPT ##
    add_action( 'init', array ( 'q_export_user_data', 'get_instance' ), 1000000 );
    
    class q_export_user_data {
                
        // Refers to a single instance of this class. ##
        private static $instance = null;
                       
        // Plugin Settings
        const version = '2.1.3';
		static $debug = false;
        const text_domain = 'q-export-user-data'; // for translation ##
        
        /* properties */
        public static $q_eud_exports = ''; // export settings ##
        public static $usermeta_saved_fields = array();
        public static $bp_fields_saved_fields = array();
        public static $bp_fields_update_time_saved_fields = array();
        public static $role = '';
        public static $roles = '0';
		public static $user_fields = '1';
        public static $groups = '0';
        public static $start_date = '';
        public static $end_date = '';
        public static $limit_offset = '';
        public static $limit_total = '';
		public static $updated_since_date = '';
		public static $field_updated_since = '';
        public static $format = '';
        public static $bp_data_available = false;
        public static $allowed_tags = '';

        // api ##
        public static $api_admin_fields = false;

        /**
         * Creates or returns an instance of this class.
         *
         * @return  Foo     A single instance of this class.
         */
        public static function get_instance() 
        {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }
        
        
        /**
         * Instatiate Class
         * 
         * @since       0.2
         * @return      void
         */
        private function __construct() 
        {
            
            // activation ##
            register_activation_hook( __FILE__, array ( $this, 'register_activation_hook' ) );

            // deactvation ##
            register_deactivation_hook( __FILE__, array ( $this, 'register_deactivation_hook' ) );

            // set text domain ##
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 1 );
            
            // load libraries ##
            self::load_libraries();

        }


        // the form for sites have to be 1-column-layout
        public function register_activation_hook() {

            \add_option( 'q_eud_configured' );

            // flush rewrites ##
            #global $wp_rewrite;
            #$wp_rewrite->flush_rules();

        }


        public function register_deactivation_hook() {

            \delete_option( 'q_eud_configured' );

        }


        
        /**
         * Load Text Domain for translations
         * 
         * @since       1.7.0
         * 
         */
        public function load_plugin_textdomain() 
        {
            
            // set text-domain ##
            $domain = self::text_domain;
            
            // The "plugin_locale" filter is also used in load_plugin_textdomain()
            $locale = apply_filters('plugin_locale', get_locale(), $domain);

            // try from global WP location first ##
            load_textdomain( $domain, WP_LANG_DIR.'/plugins/'.$domain.'-'.$locale.'.mo' );
            
            // try from plugin last ##
            load_plugin_textdomain( $domain, FALSE, plugin_dir_path( __FILE__ ).'library/languages/' );
            
        }
        
        
        
        /**
         * Get Plugin URL
         * 
         * @since       0.1
         * @param       string      $path   Path to plugin directory
         * @return      string      Absoulte URL to plugin directory
         */
        public static function get_plugin_url( $path = '' ) 
        {

            return plugins_url( $path, __FILE__ );

        }
        
        
        /**
         * Get Plugin Path
         * 
         * @since       0.1
         * @param       string      $path   Path to plugin directory
         * @return      string      Absoulte URL to plugin directory
         */
        public static function get_plugin_path( $path = '' ) 
        {

            return plugin_dir_path( __FILE__ ).$path;

        }
        

        /**
        * Load Libraries
        *
        * @since        2.0
        */
		private static function load_libraries()
        {

			// vendor ##
            require_once self::get_plugin_path( 'vendor/PHP_XLSXWriter/xlsxwriter.class.php' );

            // methods ##
            require_once self::get_plugin_path( 'library/core/helper.php' );
            require_once self::get_plugin_path( 'library/core/core.php' );
            require_once self::get_plugin_path( 'library/core/user.php' );
            require_once self::get_plugin_path( 'library/core/buddypress.php' );
            // require_once self::get_plugin_path( 'library/core/excel2003.php' );
            require_once self::get_plugin_path( 'library/core/export.php' );
            require_once self::get_plugin_path( 'library/core/filters.php' );

            // api ##
            require_once self::get_plugin_path( 'library/api/admin.php' );

            // backend ##
            require_once self::get_plugin_path( 'library/admin/admin.php' );
            
        }

    }

}
