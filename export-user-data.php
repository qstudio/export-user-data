<?php

/*
Plugin Name: Export User Data
Plugin URI: http://qstudio.us/plugins/
Description: Export User data, metadata and BuddyPress X-Profile data.
Version: 1.3.1
Author: Q Studio
Author URI: http://qstudio.us
License: GPL2
Text Domain: export-user-data
*/

// quick check :) ##
defined( 'ABSPATH' ) OR exit;

/* Check for Class */
if ( ! class_exists( 'Q_Export_User_Data' ) )
{

    // plugin version
    define( 'Q_EUD_HOOK', 'init' ); // wp action to hook to ##
    define( 'Q_EUD_HOOK_ADMIN', 'admin_init' ); // wp action to hook to ##
    define( 'Q_EUD_PRIORITY', '1000000' ); // priority ##
    define( 'Q_LOG_PREFIX', 'EUD' ); // wp action to hook to ##

    // plugin version
    define( 'Q_EUD', '1.3.0' ); // version ##

    // on activate ##
    #register_activation_hook( __FILE__, 'function' );

    // on deactivate ##
    #register_deactivation_hook( __FILE__, 'function' );

    /**
     * Main plugin class
     *
     * @since 0.1
     **/
    class Q_Export_User_Data {


        #private static $instance = null;

        /* properties */
        #protected $text_domain = 'export-user-data'; // for translation ##
        protected $debug = true; // debug ##
        protected $q_eud_exports = ''; // export settings ##
        protected $usermeta_saved_fields = array();
        protected $bp_fields_saved_fields = array();
        protected $bp_fields_update_time_saved_fields = array();
        protected $role = '';
        protected $roles = '0';
		protected $user_fields = '1';
        protected $groups = '0';
        protected $start_date = '';
        protected $end_date = '';
        protected $limit_offset = '';
        protected $limit_total = '';
		protected $updated_since_date = '';
		protected $field_updated_since = '';
        protected $format = '';
        protected $bp_data_available = false;
        protected $allowed_tags = '';



        /**
         * Class contructor
         *
         * @since 0.1
         **/
        public function __construct()
        {

            // silence is golden ##

        }


        /**
         * Load plugin text-domain
         *
         * @since       0.9.0
         * @return      void
         **/
        public function load_plugin_textdomain()
        {

            // The "plugin_locale" filter is also used in load_plugin_textdomain()
            $locale = apply_filters( 'plugin_locale', get_locale(), 'export-user-data' );

            // try from global WP location first ##
            load_textdomain( 'export-user-data', WP_LANG_DIR.'/plugins/export-user-data-'.$locale.'.mo' );

            // try from plugin last ##
            load_plugin_textdomain( 'export-user-data', false, basename( dirname( __FILE__ ) ) . '/languages' );

        }


        /**
        * Hook intro WP filters and actions
        *
        * @since 1.2.8
        * @return void
        */
        public function run_hooks()
        {

            // set text domain ##
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 1 );

            if ( is_admin() ) {

                // load BP ##
                add_action( Q_EUD_HOOK_ADMIN, array( $this, 'load_buddypress' ), Q_EUD_PRIORITY+1 );

                // load user options ##
                add_action( Q_EUD_HOOK_ADMIN, array( $this, 'load_user_options' ), Q_EUD_PRIORITY+2 );

                // run export ##
                add_action( Q_EUD_HOOK_ADMIN, array( $this, 'generate_data' ), Q_EUD_PRIORITY+3 );

                // filter exported data - perhaps unused ##
                #add_filter( 'q_eud_exclude_data', array( $this, 'exclude_data' ) );

                // add export page inside admin ##
                add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );

                // UI style and functionality ##
                add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 1 );
                add_action( 'admin_footer', array( $this, 'jquery' ), 100000 );
                add_action( 'admin_footer', array( $this, 'css' ), 100000 );

            }

        }


		/**
         * Write to WP Error Log
         *
         * @since       1.5.0
         * @return      void
         */
        protected function log( $log )
        {

            if ( $this->debug && true === WP_DEBUG ) {

                $trace = debug_backtrace();
                $caller = $trace[1];

                $suffix = sprintf(
                    __( ' - %s%s() %s:%d', 'Q_Scrape_Wordpress' )
                    ,   isset($caller['class']) ? $caller['class'].'::' : ''
                    ,   $caller['function']
                    ,   isset( $caller['file'] ) ? $caller['file'] : 'n'
                    ,   isset( $caller['line'] ) ? $caller['line'] : 'x'
                );

                $prefix = Q_LOG_PREFIX.' ';

                if ( is_array( $log ) || is_object( $log ) ) {
                    error_log( $prefix.print_r( $log, true ).$suffix );
                } else {
                    error_log( $prefix.$log.$suffix );
                }

            }

        }


        /**
         * Nicer var_dump
         *
         * @since       0.9.6
         */
        protected function pr ( $variable )
        {

            echo '<pre>';
            print_r ( $variable );
            echo '</pre>';

        }


        /**
         * Add administration menus
         *
         * @since 0.1
         **/
        public function add_admin_pages()
        {

            add_users_page( __( 'Export User Data', 'export-user-data' ), __( 'Export User Data', 'export-user-data' ), 'list_users', 'export-user-data', array( $this, 'users_page' ) );

        }


        /**
         * style and interaction
         */
        public function admin_enqueue_scripts( $hook )
        {

            // load the scripts on only the plugin admin page ##
            if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'export-user-data' ) ) {

                wp_register_style( 'css-q_export_user_data', plugins_url( 'css/export-user-data.css' ,__FILE__ ), '', Q_EUD );
                wp_enqueue_style( 'css-q_export_user_data' );
                wp_enqueue_script( 'q_eud_multi_select_js', plugins_url( 'js/jquery.multi-select.js', __FILE__ ), array('jquery'), '0.9.8', false );

                // add script ##
                wp_enqueue_script('jquery-ui-datepicker');

                // add style ##
                wp_enqueue_style( 'jquery-ui-datepicker' );
                wp_enqueue_style('jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

            }

        }


        /**
         * Return Byte count of $val
         *
         * @link        http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue?replies=2
         * @since       0.9.6
         */
        protected function return_bytes( $val )
        {

            $val = trim( $val );
            $last = strtolower($val[strlen($val)-1]);
            switch( $last ) {

                // The 'G' modifier is available since PHP 5.1.0
                case 'g':

                    $val *= 1024;

                case 'm':

                    $val *= 1024;

                case 'k':

                    $val *= 1024;

            }

            return $val;
        }


        /**
         * Sanitize data
         *
         * @since 1.2.8
         * @return string
         */
        protected function sanitize( $value )
        {

            // emove line breaks ##
            $value = str_replace("\r", '', $value);
            $value = str_replace("\n", '', $value);
            $value = str_replace("\t", '', $value);

            // with wp_kses ##
            $value = wp_kses( $value, $this->get_allowed_tags() );

            // with esc_html
            $value = esc_html( $value );

            // return value ##
            return $value;

        }


        /**
         * Get allowed tags for wp_kses
         *
         * @since  1.2.8
         * @return Array
         */
        protected function get_allowed_tags()
        {

            $allowed_tags = array(
                'a' => array(
                    'href' => array(),
                    'title' => array()
                ),
                'br' => array(),
                'em' => array(),
                'strong' => array(),
            );

            return apply_filters( 'export_user_data_allowed_tags', $allowed_tags );

        }



        /**
         * Load up saved exports for this user
         * Set to public as hooked into action
         *
         * @since       0.9.6
         * @return      Array of saved exports
         */
        public function load_buddypress()
        {

            // do we have a bp object in the globals ##
            if (
                is_plugin_active( 'buddypress/bp-loader.php' ) // plugin active
                && function_exists ( 'buddypress' ) // loader function exists ##
                && ! isset( $GLOBALS['bp'] ) // but global unavailble ##
            ) {

                $this->log( 'BP not loaded - calling buddpress()' );

                // call BP
                buddypress();

                return true;

            }

            #$this->log( 'BP loaded' );

            return true;

        }


        /**
         * Load up saved exports for this user
         * Set to public as hooked into action
         *
         * @since       0.9.6
         * @return      Array of saved exports
         */
        public function load_user_options()
        {


            $this->q_eud_exports =
                get_user_meta( get_current_user_id(), 'q_eud_exports' ) ?
                get_user_meta( get_current_user_id(), 'q_eud_exports', true ) :
                array() ;
            #var_dump( $this->q_eud_exports );

        }


        /**
         * Get list of saved exports for this user
         *
         * @since       0.9.4
         * @return      Array of saved exports
         */
        protected function get_user_options()
        {

            // get the stored options - filter empty array items ##
            $q_eud_exports = array_filter( $this->q_eud_exports );

            // quick check if the array is empty ##
            if ( empty ( $q_eud_exports ) ) {

                return false;

            }

            // test the array of saved exports ##
            #$this->pr( $q_eud_exports );

            // start with an empty array ##
            $exports = array();

            // loop over each saved export and grab each key ##
            foreach ( $q_eud_exports as $key => $value ) {

                $exports[] = $key;

            }

            // kick back array ##
            return( $exports );

        }


        /**
         * Check for and load stored user options
         *
         * @since       0.9.3
         * @return      void
         */
        protected function get_user_options_by_export( $export = null )
        {

            // sanity check ##
            if ( is_null ( $export ) ) { return false; }

            if ( isset( $this->q_eud_exports[$export] ) ) {

                  $this->usermeta_saved_fields = $this->q_eud_exports[$export]['usermeta_saved_fields'];
                  $this->bp_fields_saved_fields = $this->q_eud_exports[$export]['bp_fields_saved_fields'];
                  $this->bp_fields_update_time_saved_fields = $this->q_eud_exports[$export]['bp_fields_update_time_saved_fields'];
				  $this->updated_since_date = isset( $this->q_eud_exports[$export]['updated_since_date'] ) ? $this->q_eud_exports[$export]['updated_since_date'] : null ;
				  $this->field_updated_since = isset( $this->q_eud_exports[$export]['field_updated_since'] ) ? $this->q_eud_exports[$export]['field_updated_since'] : null ;
                  $this->role = $this->q_eud_exports[$export]['role'];
                  $this->roles = $this->q_eud_exports[$export]['roles'];
                  $this->groups = $this->q_eud_exports[$export]['groups'];
				  $this->user_fields = isset( $this->q_eud_exports[$export]['user_fields'] ) ? $this->q_eud_exports[$export]['user_fields'] : null ;
                  $this->start_date = $this->q_eud_exports[$export]['start_date'];
                  $this->end_date = $this->q_eud_exports[$export]['end_date'];
                  $this->limit_offset = $this->q_eud_exports[$export]['limit_offset'];
                  $this->limit_total = $this->q_eud_exports[$export]['limit_total'];
                  $this->format = $this->q_eud_exports[$export]['format'];

            } else {

                  $this->usermeta_saved_fields = array();
                  $this->bp_fields_saved_fields = array();
                  $this->bp_fields_update_time_saved_fields = array();
				  $this->updated_since_date = '';
				  $this->field_updated_since = '';
                  $this->role = '';
				  $this->user_fields = '1';
                  $this->roles = '1';
                  $this->groups = '1';
                  $this->start_date = '';
                  $this->end_date = '';
                  $this->limit_offset = '';
                  $this->limit_total = '';
                  $this->format = '';

            }

        }


        /**
         * Method to store user options
         *
         * @param       string      $save_export        Export Key name
         * @param       array       $save_options       Array of export options to save
         * @since       0.9.3
         * @return      void
         */
        protected function set_user_options( $key = null, $options = null )
        {

            // sanity check ##
            if ( is_null ( $key ) || is_null ( $options ) ) {

                #$this->pr( 'missing save values' );
                return false;

            }

            #$this->pr( $key );
            #$this->pr( $options );

            // for now, I'm simply allowing keys to be resaved - but this is not so logical ##
            if ( array_key_exists( $key, $this->q_eud_exports ) ) {

                #$this->pr( 'key exists, skipping save' );
                #return false;

            }

            if ( isset( $options ) && is_array( $options ) ) {

                // update_option sanitizes the option name but not the option value ##
                foreach ( $options as $field_name => $field_value ) {

                    // so do that here. ##
                    if ( is_array( $field_value ) ) {

                        foreach ( $field_value as $field_array_key => $field_array_value ) {

                            $options[$field_name][$field_array_key] = sanitize_text_field( $field_array_value );

                        }

                    } else {

                        $options[$field_name] = sanitize_text_field( $field_value );

                    }

                }

                // assign the sanitized array of values to the class property $q_eud_exports as a new array with key $key ##
                $this->q_eud_exports[$key] = $options;

                // update stored user_meta values, if previous key found ##
                if ( get_user_meta( get_current_user_id(), 'q_eud_exports' ) !== false ) {

                    #update_option( 'q_eud_exports', $this->q_eud_exports );
                    update_user_meta( get_current_user_id(), 'q_eud_exports', $this->q_eud_exports );

                // create new user meta key ##
                } else {

                    #add_option( 'q_eud_exports', $this->q_eud_exports, $deprecated, $autoload );
                    add_user_meta( get_current_user_id(), 'q_eud_exports', $this->q_eud_exports );

                }

            }

	   }


        /**
         * method to delete user options
         *
         * @param       $key        String      Key name to drop from property
         * @since       0.9.3
         * @return      void
         */
        protected function delete_user_options( $key = null )
        {

            // sanity check ##
            if ( is_null ( $key ) || ! array_key_exists( $key, $this->q_eud_exports ) ) { return false; }

            // clean it up ##
            $key = sanitize_text_field( $key );

            // check it out ##
            #$this->pr( $key );

            // drop the array by it's key name from the class property ##
            unset( $this->q_eud_exports[$key] );

            // update the saved data ##
            update_user_meta( get_current_user_id(), 'q_eud_exports', $this->q_eud_exports );

        }


        /**
         * Copy of BP_XProfile_ProfileData::get_all_for_user() from BP version 2.0?
         * Get all of the profile information for a specific user.
         *
         * @param       $user_id        Integer      ID of specific user
         * @since       0.9.6
         * @return      Array           User profile fields
		 * @deprecated	since 1.2.1
         */
        protected function get_all_for_user( $user_id = null )
		{

            // sanity check ##
            if ( is_null( $user_id ) ) { return false; }

            global $wpdb, $bp;

			$bp = buddypress();

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "
                        SELECT g.id as field_group_id, g.name as field_group_name, f.id as field_id, f.name as field_name, f.type as field_type, d.value as field_data, u.user_login, u.user_nicename, u.user_email
                        FROM {$bp->profile->table_name_groups} g
                            LEFT JOIN {$bp->profile->table_name_fields} f ON g.id = f.group_id
                            INNER JOIN {$bp->profile->table_name_data} d ON f.id = d.field_id LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
                        WHERE d.user_id = %d AND d.value != ''
                    "
                    , $user_id
                )
            );

            $profile_data = array();

            if ( ! empty( $results ) ) {

                $profile_data['user_login']    = $results[0]->user_login;
                $profile_data['user_nicename'] = $results[0]->user_nicename;
                $profile_data['user_email']    = $results[0]->user_email;

                foreach( (array) $results as $field ) {

                    $profile_data[$field->field_name] = array(
                        'field_group_id'   => $field->field_group_id,
                        'field_group_name' => $field->field_group_name,
                        'field_id'         => $field->field_id,
                        'field_type'       => $field->field_type,
                        'field_data'       => $field->field_data
                    );

                }

            }

            return $profile_data;

        }


        /**
         * Attempt to generate the export file based on the passed arguements
         *
         * @since 0.1
         * @return Mixes
         **/
        public function generate_data()
        {

            // Check if the user clicked on the Save, Load, or Delete Settings buttons ##
            if (
                ! isset( $_POST['_wpnonce-q-eud-export-user-page_export'] )
                || isset( $_POST['load_export'] )
                || isset( $_POST['save_export'] )
                || isset( $_POST['delete_export'] ) )
            {

                return false;

            }

            // Increase maximum execution time to prevent "Maximum execution time exceeded" error ##
            ini_set( 'max_execution_time', -1 );
            ini_set( 'memory_limit', -1 ); // looks like a bad idea ##

            // check admin referer ##
            check_admin_referer( 'q-eud-export-user-page_export', '_wpnonce-q-eud-export-user-page_export' );

            // build argument array ##
            $args = array(
                'fields'    => ( isset( $_POST['user_fields'] ) && '1' == $_POST['user_fields'] ) ? 'all' : array( 'ID' ), // exclude standard wp_users fields from get_users query ##
                'role'      => sanitize_text_field( $_POST['role'] )
            );

            // did they request a specific program ? ##
            if ( isset( $_POST['program'] ) && $_POST['program'] != '' ) {

                $args['meta_key'] = 'member_of_club';
                $args['meta_value'] = (int)$_POST['program'];
                $args['meta_compare'] = '=';

            }

            // is there a range limit in place for the export ? ##
            if ( isset( $_POST['limit_total'] ) && $_POST['limit_total'] != '' ) {

                // let's just make sure they are integer values ##
                $limit_offset = isset( $_POST['limit_offset'] ) ? (int)$_POST['limit_offset'] : 0 ;
                $limit_total = (int)$_POST['limit_total'];

                if ( is_int( $limit_offset ) && is_int( $limit_total ) ) {

                    $args['offset'] = $limit_offset;
					$args['number'] = $limit_total; // number - Limit the total number of users returned ##

                    // test it ##
                    #wp_die( $this->pr( $args ) );

                }

            }

            // pre_user query ##
            add_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
            $users = get_users( $args );
            remove_action( 'pre_user_query', array( $this, 'pre_user_query' ) );

            // test args ##
            #wp_die( $this->pr ( $users ) );

            // no users found, so chuck an error into the args array and exit the export ##
            if ( ! $users ) {

                wp_redirect( add_query_arg( 'error', 'empty', wp_get_referer() ) );
                exit;

            }

            // get sitename and clean it up ##
            $sitename = sanitize_key( get_bloginfo( 'name' ) );
            if ( ! empty( $sitename ) ) {
                $sitename .= '.';
            }

            // export method ? ##
            $export_method = 'excel'; // default to Excel export ##
            if ( isset( $_POST['format'] ) && $_POST['format'] != '' ) {

                $export_method = sanitize_text_field( $_POST['format'] );

            }

            // set export filename structure ##
            $filename = $sitename . 'users.' . date( 'Y-m-d-H-i-s' );

            switch ( $export_method ) {

                case ( 'csv' ):

                    // to csv ##
                    header( 'Content-Description: File Transfer' );
                    header( 'Content-Disposition: attachment; filename='.$filename.'.csv' );
                    header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );

                    // set a csv check flag
                    $is_csv = true;

                    // nothing here
                    $doc_begin  = '';

                    //preformat
                    $pre        = '';

                    // how to seperate data ##
                    $seperator = ','; // comma for csv ##

                    // line break ##
                    $breaker = "\n";

                    // nothing here
                    $doc_end  = '';

                    break;

                case ( 'excel' ):

                    // to xls ##
                    header( 'Content-Description: File Transfer' );
                    header("Content-Type: application/vnd.ms-excel");
                    header("Content-Disposition: attachment; filename=$filename.xls");
                    header("Pragma: no-cache");
                    header("Expires: 0");

                    // set a csv check flag
                    $is_csv = false;

                    //grab the template file (for tidy formatting)
                    include( 'xml-template.php' );

                    // open xml
                    $doc_begin  = $xml_doc_begin;

                    //preformat
                    $pre        = $xml_pre;

                    // how to seperate data ##
                    $seperator  = $xml_seperator;

                    // line break ##
                    $breaker    = $xml_breaker;

                    // close xml
                    $doc_end    = $xml_doc_end;

                    break;

            }


            // check for selected usermeta fields ##
            $usermeta = isset( $_POST['usermeta'] ) ? $_POST['usermeta']: '';
            #$this->pr( $usermeta );
            $usermeta_fields = array();

            if ( $usermeta && is_array( $usermeta ) ) {
                foreach( $usermeta as $field ) {
                    $usermeta_fields[] = sanitize_text_field ( $field  );
                }
            }

            #$this->pr( $usermeta_fields );
            #exit;

            // check for selected x profile fields ##
            $bp_fields = isset( $_POST['bp_fields'] ) ? $_POST['bp_fields'] : '';
            $bp_fields_passed = array();
            if ( $bp_fields && is_array( $bp_fields ) ) {

                foreach( $bp_fields as $field ) {

                    // reverse tidy ##
                    $field = str_replace( '__', ' ', sanitize_text_field ( $field ) );

                    // add to array ##
                    $bp_fields_passed[] = $field;

                }

            }

            // cwjordan: check for x profile fields we want update time for ##
            $bp_fields_update = isset( $_POST['bp_fields_update_time'] ) ? $_POST['bp_fields_update_time'] : '';
            $bp_fields_update_passed = array();
            if ( $bp_fields_update && is_array( $bp_fields_update ) ) {

                foreach( $bp_fields_update as $field ) {

                    // reverse tidy ##
                    $field = str_replace( '__', ' ', sanitize_text_field ( $field ) );

                    // add to array ##
                    $bp_fields_update_passed[] = $field . " Update Date";

                }

            }

            // global wpdb object ##
            global $wpdb;

			// debug ##
			#$this->log( 'merging array' );

            // compile final fields list ##
            $fields = array_merge(
					$this->get_user_fields() // standard wp_user fields ##
				,	$this->get_special_fields() // 'special' fields - which are controlled via dedicated checks ##
				,	$usermeta_fields // wp_user_meta fields ##
				,	$bp_fields_passed // selected buddypress fields ##
				,	$bp_fields_update_passed // update date for buddypress fields ##
			);

            // test field array ##
            #$this->pr( $fields );

            // build the document headers ##
            $headers = array();

            foreach ( $fields as $key => $field ) {

                #$this->log( 'Field: '. $field );

                // rename programs field ##
                if ( $field == 'member_of_club' ){
                    $field = 'Program';
                }

                // grab fields to exclude from exports ##
                if ( in_array( $fields[$key], $this->get_exclude_fields() ) ) {

                    #$this->log( 'Dump Field: '. $fields[$key] );

					// ditch 'em ##
                    unset( $fields[$key] );

                } else {

                    if ( $is_csv ) {

                        $headers[] = '"' . $field . '"';

                    } else {

                        $headers[] = $field;
                        #echo '<script>console.log("Echoing header cell: '.$field.'")</script>';

                    }

                }

            }

			// quick check ##
			#$this->log( $fields );
			#if ( $this->debug ) $this->log( '$bp_fields_passed: '. var_dump( $bp_fields_passed ) );

            // no more buffering while spitting back the export data ##
            ob_end_flush();

            // get the value in bytes allocated for Memory via php.ini ##
            // @link http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue
            $memory_limit = $this->return_bytes( ini_get('memory_limit') ) * .75;

            // we need to disable caching while exporting because we export so much data that it could blow the memory cache
            // if we can't override the cache here, we'll have to clear it later...
            if ( function_exists( 'override_function' ) ) {

                override_function('wp_cache_add', '$key, $data, $group="", $expire=0', '');
                override_function('wp_cache_set', '$key, $data, $group="", $expire=0', '');
                override_function('wp_cache_replace', '$key, $data, $group="", $expire=0', '');
                override_function('wp_cache_add_non_persistent_groups', '$key, $data, $group="", $expire=0', '');

            } elseif ( function_exists( 'runkit_function_redefine' ) ) {

                runkit_function_redefine('wp_cache_add', '$key, $data, $group="", $expire=0', '');
                runkit_function_redefine('wp_cache_set', '$key, $data, $group="", $expire=0', '');
                runkit_function_redefine('wp_cache_replace', '$key, $data, $group="", $expire=0', '');
                runkit_function_redefine('wp_cache_add_non_persistent_groups', '$key, $data, $group="", $expire=0', '');

            }

            // open doc wrapper.. ##
            echo $doc_begin;

            // echo headers ##
            echo $pre . implode( $seperator, $headers ) . $breaker;

			#wp_die( $this->pr( $users ) );

            // build row values for each user ##
            foreach ( $users as $user ) {

				#wp_die( $this->pr( $user ) );

                // check if we're hitting any Memory limits, if so flush them out ##
                // per http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue?replies=2
				if ( memory_get_usage( true ) > $memory_limit ) {
                    wp_cache_flush();
                }

                // open up a new empty array ##
                $data = array();

                // BP loaded ? ##
                if (
                    ! $this->bp_data_available
                    && function_exists ( 'bp_is_active' )
                    && bp_is_active( 'xprofile' )
                    && class_exists( 'BP_XProfile_ProfileData' )
                    && method_exists( 'BP_XProfile_ProfileData', 'get_all_for_user' )
                    && is_callable ( array( 'BP_XProfile_ProfileData', 'get_all_for_user' ) )
                ) {

                    $this->log( 'XProfile Accessible' );
                    $this->bp_data_available = true; // we only need to check for BP once ##

                }

                // grab all user data ##
                if (
                    $this->bp_data_available
                    && ! $bp_data = BP_XProfile_ProfileData::get_all_for_user( $user->ID )
                ) {

                    // null the data to be sure ##
                    $bp_data = false;

                    $this->log( 'XProfile returned no data ID#: '.$user->ID );

                }

                // single query method - get all user_meta data ##
                $get_user_meta = (array)get_user_meta( $user->ID );
                #wp_die( $this->pr( $get_user_meta ) );

                // Filter out empty meta data ##
                #$get_user_meta = array_filter( array_map( function( $a ) {
                #    return $a[0];
                #}, $get_user_meta ) );

                // loop over each field ##
                foreach ( $fields as $field ) {

                    // check if this is a BP field ##
                    if ( isset( $bp_data ) && isset( $bp_data[$field] ) && in_array( $field, $bp_fields_passed ) )
                    {

                        // old way from single BP query ##
						$value = $bp_data[$field];

                        if ( is_array( $value ) ) {

                            $value = maybe_unserialize( $value['field_data'] ); // suggested by @grexican ##
                            #$value = $value['field_data'];

							/**
							 * cwjordan
							 * after unserializing it we then
							 * need to implode it so
							 * that we have something readable?
							 * Going to use :: as a separator
							 * because that's what Buddypress Members Import
							 * expects, but we might want to make that
							 * configurable.
							*/
							if ( is_array( $value ) ) {
								$value =  implode("::", $value );
							}

                        }

                        // sanitize ##
                        #$value = $this->sanitize($value);

                    // check if this is a BP field we want the updated date for ##
                    }
                    elseif ( in_array( $field, $bp_fields_update_passed ) )
                    {

                        global $bp;

                        $real_field = str_replace(" Update Date", "", $field);
                        $field_id = xprofile_get_field_id_from_name( $real_field );
                        $value = $wpdb->get_var (
                                    $wpdb->prepare(
                                        "
                                            SELECT last_updated
                                            FROM {$bp->profile->table_name_data}
                                            WHERE user_id = %d AND field_id = %d
                                        "
                                        , $user->ID
                                        , $field_id
                                    )
                                );

                    // include the user's role in the export ##
                    }
                    elseif ( isset( $_POST['roles'] ) && '1' == $_POST['roles'] && $field == 'roles' )
                    {

                        // add "Role" as $value ##
                        $value = isset( $user->roles[0] ) ? implode( $user->roles, '|' ) : '' ; // empty value if no role found - or flat array of user roles ##

                    // include the user's BP group in the export ##
                    }
                    elseif ( isset( $_POST['groups'] ) && '1' == $_POST['groups'] && $field == 'groups' )
                    {

                        if ( function_exists( 'groups_get_user_groups' ) ) {

                            // check if user is a member of any groups ##
                            $group_ids = groups_get_user_groups( $user->ID );

							#$this->pr( $group_ids );
							#wp_die( pr( 'loaded group data.' ));

                            if ( ! $group_ids || $group_ids == '' ) {

                                $value = '';

                            } else {

                                // new empty array ##
                                $groups = array();

                                // loop over all groups ##
                                foreach( $group_ids["groups"] as $group_id ) {

                                    $groups[] = groups_get_group( array( 'group_id' => $group_id )) -> name . ( end( $group_ids["groups"] ) == $group_id ? '' : '' );

                                }

                                // implode it ##
                                $value = implode( $groups, '|' );

                            }

                        } else {

                            $value = '';

                        }

                    }
                    elseif ( $field == 'bp_latest_update' || $field == 'last_activity' )
                    {

                        // https://bpdevel.wordpress.com/2014/02/21/user-last_activity-data-and-buddypress-2-0/ ##
                        $value = bp_get_user_last_activity( $user->ID );

                    // user or usermeta field ##
                    }
                    else
                    {

                        // the user_meta key isset ##
                        if ( isset( $get_user_meta[$field] ) ) {

                            // take from the bulk get_user_meta call - this returns an array in all cases, so we take the first key ##
                            $value = $get_user_meta[$field][0];

                        // standard WP_User value ##
                        } else {

                            // use the magically assigned value from WP_Users
                            $value =isset( $user->{$field} ) ? $user->{$field} : null ;

                        }


                        // the $value might be serialized ##
                        $value = $this->unserialize( $value );

                        // the value is an array ##
                        if ( is_array ( $value ) ) {

                            // recursive implode it ##
                            $value = $this->recursive_implode( $value );

                        }

                        // sanitize ##
                        #$value = $this->sanitize($value);

                    }


                    // correct program value to Program Name ##
                    if ( $field == 'member_of_club' ) {

                        $value = get_the_title($value);

                    }

                    // sanitize ##
                    $value = $this->sanitize($value);

                    // wrap values in quotes and add to array ##
                    if ( $is_csv ) {

                        $data[] = '"' . str_replace( '"', '""', $this->special_characters( $value ) ) . '"';

                    // just add to array ##
                    } else {

                        $data[] = $this->special_characters( $value );
                    }

                }

                // echo row data ##
                echo $pre . implode( $seperator, $data ) . $breaker;

            }

            // close doc wrapper..
            echo $doc_end;

            // stop PHP, so file can export correctly ##
            exit;

        }


        /**
         * Content of the settings page
         *
         * @since 0.1
         **/
        public function users_page()
        {

            // quick security check ##
            if ( ! current_user_can( 'list_users' ) ) {

                wp_die( __( 'You do not have sufficient permissions to access this page.', 'export-user-data' ) );

            }

            // Save settings button was pressed ##
            if (
                isset( $_POST['save_export'] )
                && check_admin_referer( 'q-eud-export-user-page_export', '_wpnonce-q-eud-export-user-page_export' )
            ) {

                // start with an empty variable ##
                $save_export = "";

                if ( ! empty( $_POST['save_new_export_name'] ) ) {

                    // assign value ##
                    $save_export = $_POST['save_new_export_name'];

                } elseif ( ! empty( $_POST['export_name'] ) ) {

                    $save_export = $_POST['export_name'];

                }

                // clean up $save_export ##
                $save_export = sanitize_text_field( $save_export );

                // Build array of $options to save and save them ##
                if ( isset( $save_export ) ) {

                    // prepare all array values ##
                    $usermeta = isset( $_POST['usermeta'] ) ? $_POST['usermeta']: '' ;
                    $bp_fields = isset( $_POST['bp_fields'] ) ? $_POST['bp_fields'] : '' ;
                    $bp_fields_update = isset( $_POST['bp_fields_update_time'] ) ? $_POST['bp_fields_update_time'] : '' ;
                    $format = isset( $_POST['format'] ) ? $_POST['format'] : '' ;
                    $role = isset( $_POST['role'] ) ? $_POST['role'] : '' ;
                    $roles = isset( $_POST['roles'] ) ? $_POST['roles'] : '0' ;
					$user_fields = isset( $_POST['user_fields'] ) ? $_POST['user_fields'] : '0' ;
                    $groups = isset( $_POST['groups'] ) ? $_POST['groups'] : '0' ;
                    $start_date = isset( $_POST['start_date'] ) ? $_POST['start_date'] : '' ;
                    $end_date = isset( $_POST['end_date'] ) ? $_POST['end_date'] : '' ;
                    $limit_offset = isset( $_POST['limit_offset'] ) ? $_POST['limit_offset'] : '' ;
                    $limit_total = isset( $_POST['limit_total'] ) ? $_POST['limit_total'] : '' ;
					$updated_since_date = isset ( $_POST['updated_since_date'] ) ? $_POST['updated_since_date'] : '' ;
					$field_updated_since = isset ( $_POST['bp_field_updated_since'] ) ? $_POST['bp_field_updated_since'] : '';

                    // assign all values to an array ##
                    $save_array = array (
                        'usermeta_saved_fields' => $usermeta,
                        'bp_fields_saved_fields' => $bp_fields,
                        'bp_fields_update_time_saved_fields' => $bp_fields_update,
                        'role' => $role,
                        'roles' => $roles,
						'user_fields' => $user_fields,
                        'groups' => $groups,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'limit_offset' => $limit_offset,
                        'limit_total' => $limit_total,
						'updated_since_date' => $updated_since_date,
						'field_updated_since' => $field_updated_since,
                        'format' => $format
                    );

                    // store the options, for next load ##
                    $this->set_user_options( $save_export, $save_array );

                    // Display the settings the user just saved instead of blanking the form ##
                    $_POST['load_export'] = 'Load Settings';
                    $_POST['export_name'] = $save_export;

                }

            }

            // Load settings button was pressed ( or option saved and $_POST variables hijacked )##
            if (
                isset( $_POST['load_export'] )
                && isset( $_POST['export_name'] )
                && check_admin_referer( 'q-eud-export-user-page_export', '_wpnonce-q-eud-export-user-page_export' )
            ) {

                $this->get_user_options_by_export( sanitize_text_field( $_POST['export_name'] ) );

            }

            // Delete settings button was pressed ##
            if (
                isset( $_POST['delete_export'] )
                && isset( $_POST['export_name'] )
                && check_admin_referer( 'q-eud-export-user-page_export', '_wpnonce-q-eud-export-user-page_export' )
            ) {

                $this->delete_user_options( sanitize_text_field( $_POST['export_name'] ) );

            }

			// what's in 'this' ? ##
			#self:pr( $this );

?>
    <div class="wrap">
        <h2><?php _e( 'Export User Data', 'export-user-data' ); ?></h2>
<?php

        // nothing happening? ##
        if ( isset( $_GET['error'] ) ) {
            echo '<div class="updated"><p><strong>' . __( 'No users found.', 'export-user-data' ) . '</strong></p></div>';
        }

?>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field( 'q-eud-export-user-page_export', '_wpnonce-q-eud-export-user-page_export' ); ?>
            <table class="form-table">
<?php

                // allow admin to select user meta fields to export ##
                global $wpdb;
                $meta_keys = $wpdb->get_results( "SELECT distinct(meta_key) FROM $wpdb->usermeta" );

                // get meta_key value from object ##
                $meta_keys = wp_list_pluck( $meta_keys, 'meta_key' );

                // let's note some of them odd keys ##
                $meta_keys_system = array(
                    'metaboxhidden',
                    'activation',
                    'bp_',
                    'nav_',
                    'wp_',
                    'admin_color',
                    'wpmudev',
                    'screen_',
                    'show_',
                    'rich_',
                    'reward_',
                    'meta-box',
                    'manageedit',
                    'edit_',
                    'closedpostboxes_',
                    'dismissed_',
                    'manage',
                    'comment',
                    'current',
                    'incentive_',
                    '_wdp',
                    'ssl',
                    'wdfb',
                    'users_per_page',
                );

                // allow array to be filtered ##
                $meta_keys_system = apply_filters( 'export_user_data_meta_keys_system', $meta_keys_system );

                // test array ##
                #echo '<pre>'; var_dump($meta_keys); echo '</pre>';

                // check if we got anything ? ##
                if ( $meta_keys ) {

?>
                <tr valign="top">
                    <th scope="row">
                        <label for="q_eud_usermeta"><?php _e( 'User Meta Fields', 'export-user-data' ); ?></label>
                        <p class="filter" style="margin: 10px 0 0;">
                            <?php _e('Filter', 'export-user-data'); ?>: <a href="#" class="usermeta-all"><?php _e('All', 'export-user-data'); ?></a> | <a href="#" class="usermeta-common"><?php _e('Common', 'export-user-data'); ?></a>
                        </p>
                        <p class="filter" style="margin: 10px 0 0;">
                            <?php _e('Select', 'export-user-data'); ?>: <a href="#" class="select-all"><?php _e('All', 'export-user-data'); ?></a> | <a href="#" class="select-none"><?php _e('None', 'export-user-data'); ?></a>
                        </p>
                    </th>
                    <td>
                        <select multiple="multiple" id="usermeta" name="usermeta[]">
<?php

                            foreach ( $meta_keys as $key ) {

                                #echo "\n\t<option value='" . esc_attr( $role ) . "'>$name</option>";

                                // display $key ##
                                $display_key = $key;

                                // rename programs field ##
                                if ( $display_key == 'member_of_club' ){
                                    $display_key = 'program';
                                }

                                // tidy ##
                                $display_key = str_replace( "_", " ", ucwords( $display_key ) );

                                #echo "<label for='".esc_attr( $key )."' title='".esc_attr( $key )."'><input id='".esc_attr( $key )."' type='checkbox' name='usermeta[]' value='".esc_attr( $key )."'/> $display_key</label><br />";

                                // class ##
                                $usermeta_class = 'normal';

                                foreach ( $meta_keys_system as $drop ) {

                                    if ( strpos( $key, $drop ) !== false ) {

                                        // https://wordpress.org/support/topic/bugfix-numbers-in-export-headers?replies=1
                                        // removed $key = assignment, as not required ##
                                        if ( ( array_search( $key, $meta_keys ) ) !== false ) {

                                            $usermeta_class = 'system';

                                        }

                                    }

                                }

                                // print key ##
                                echo "<option value='".esc_attr( $key )."' title='".esc_attr( $key )."' class='".$usermeta_class."'>$display_key</option>";

                            }
?>
                        </select>
                        <p class="description"><?php
                            printf(
                                __( 'Select the user meta keys to export, use the filters to simplify the list.', 'export-user-data' )
                            );
                        ?></p>
                    </td>
                </tr>
<?php

                } // meta_keys found ##

?>
<?php

            // buddypress x profile data ##
            if ( function_exists ('bp_is_active') ) {

                // grab all buddypress x profile fields ##
                $bp_fields = $wpdb->get_results( "SELECT distinct(name) FROM ".$wpdb->base_prefix."bp_xprofile_fields WHERE parent_id = 0" );

                // get name value from object ##
                $bp_fields = wp_list_pluck( $bp_fields, 'name' );

                // test array ##
                #echo '<pre>'; var_dump($bp_fields); echo '</pre>';

                // allow array to be filtered ##
                $bp_fields = apply_filters( 'export_user_data_bp_fields', $bp_fields );

?>
                <tr valign="top">
                    <th scope="row">
                        <label for="q_eud_xprofile"><?php _e( 'BP xProfile Fields', 'export-user-data' ); ?></label>
                        <p class="filter" style="margin: 10px 0 0;">
                            <?php _e('Select', 'export-user-data'); ?>: <a href="#" class="select-all"><?php _e('All', 'export-user-data'); ?></a> | <a href="#" class="select-none"><?php _e('None', 'export-user-data'); ?></a>
                        </p>
                    </th>
                    <td>
                        <select multiple="multiple" id="bp_fields" name="bp_fields[]">
<?php

                        foreach ( $bp_fields as $key ) {

                            // tidy up key ##
                            #$key_tidy = str_replace( ' ', '__', $key );

                            #echo "<label for='".esc_attr( $key_tidy )."'><input id='".esc_attr( $key_tidy )."' type='checkbox' name='bp_fields[]' value='".esc_attr( $key_tidy )."'/> $key</label><br />";

                            // print key ##
                            echo "<option value='".esc_attr( $key )."' title='".esc_attr( $key )."'>$key</option>";

                        }

?>
                        </select>
                        <p class="description"><?php
                            printf(
                                __( 'Select the BuddyPress XProfile keys to export', 'export-user-data' )
                            );
                        ?></p>
                    </td>
                </tr>
<?php

                // allow export of update times ##

?>
                <tr valign="top" class="toggleable">
                    <th scope="row">
                        <label for="q_eud_xprofile"><?php _e( 'BP xProfile Fields Update Time', 'export-user-data' ); ?></label>
                        <p class="filter" style="margin: 10px 0 0;">
                            <?php _e('Select', 'export-user-data'); ?>: <a href="#" class="select-all"><?php _e('All', 'export-user-data'); ?></a> | <a href="#" class="select-none"><?php _e('None', 'export-user-data'); ?></a>
                        </p>
                    </th>
                    <td>
                        <select multiple="multiple" id="bp_fields_update_time" name="bp_fields_update_time[]">
<?php

                        foreach ( $bp_fields as $key ) {

                            echo "<option value='".esc_attr( $key )."' title='".esc_attr( $key )."'>$key</option>";

                        }

?>
                        </select>
                        <p class="description"><?php
                            printf(
                                __( 'Select the BuddyPress XProfile keys updated dates to export', 'export-user-data' )
                            );
                        ?></p>
                    </td>
                </tr>

                <tr valign="top" class="toggleable">
                    <th scope="row"><label for="groups"><?php _e( 'BP User Groups', 'export-user-data' ); ?></label></th>
                    <td>
                        <input id='groups' type='checkbox' name='groups' value='1' <?php checked( isset ( $this->groups ) ? intval ( $this->groups ) : '', 1 ); ?> />
                        <p class="description"><?php
                            printf(
                                __( 'Include BuddyPress Group Data. <a href="%s" target="_blank">%s</a>', 'export-user-data' )
                                ,   esc_html('https://codex.buddypress.org/buddypress-components-and-features/groups/')
                                ,   'Codex'
                            );
                        ?></p>
                    </td>
                </tr>
<?php

            } // BP installed and active ##

?>
				<tr valign="top" class="toggleable">
                    <th scope="row"><label for="user_fields"><?php _e( 'Standard User Fields', 'export-user-data' ); ?></label></th>
                    <td>
                        <input id='user_fields' type='checkbox' name='user_fields' value='1' <?php checked( isset ( $this->user_fields ) ? intval ( $this->user_fields ) : '', 1 ); ?> />
                        <p class="description"><?php

							#$this->log( 'user_fields: '.$this->user_fields );
							#echo 'user_fields: '. $this->user_fields;

                            printf(
                                __( 'Include Standard user profile fields, such as user_login. <a href="%s" target="_blank">%s</a>', 'export-user-data' )
                                ,   esc_html('https://codex.wordpress.org/Database_Description#Table:_wp_users')
                                ,   'Codex'
                            );

                        ?></p>
                    </td>
                </tr>

                <tr valign="top" class="toggleable">
                    <th scope="row"><label for="q_eud_users_role"><?php _e( 'Role', 'export-user-data' ); ?></label></th>
                    <td>
                        <select name="role" id="q_eud_users_role">
<?php

                            echo '<option value="">' . __( 'All Roles', 'export-user-data' ) . '</option>';
                            global $wp_roles;

                            foreach ( $wp_roles->role_names as $role => $name ) {

                                if ( isset ( $this->role ) && ( $this->role == $role ) ) {

                                    echo "\n\t<option selected value='" . esc_attr( $role ) . "'>$name</option>";

                                } else {

                                    echo "\n\t<option value='" . esc_attr( $role ) . "'>$name</option>";

                                }
                            }


?>
                        </select>
                        <p class="description"><?php
                            printf(
                                __( 'Filter the exported users by a WordPress Role. <a href="%s" target="_blank">%s</a>', 'export-user-data' )
                                ,   esc_html('http://codex.wordpress.org/Roles_and_Capabilities')
                                ,   'Codex'
                            );
                        ?></p>
                    </td>
                </tr>

                <tr valign="top" class="toggleable">
                    <th scope="row"><label for="roles"><?php _e( 'User Roles', 'export-user-data' ); ?></label></th>
                    <td>
                        <input id='roles' type='checkbox' name='roles' value='1' <?php checked( isset ( $this->roles ) ? intval ( $this->roles ) : '', 1 ); ?> />
                        <p class="description"><?php
                            printf(
                                __( 'Include all of the users <a href="%s" target="_blank">%s</a>', 'export-user-data' )
                                ,   esc_html('http://codex.wordpress.org/Roles_and_Capabilities')
                                ,   'Roles'
                            );
                        ?></p>
                    </td>
                </tr>
<?php

                // clubs ? ##
                if ( post_type_exists( 'club' ) ) {

?>
                <tr valign="top" class="toggleable">
                    <th scope="row"><label for="q_eud_users_program"><?php _e( 'Programs', 'export-user-data' ); ?></label></th>
                    <td>
                        <select name="program" id="q_eud_users_program">
<?php

                            echo '<option value="">' . __( 'All Programs', 'export-user-data' ) . '</option>';

                            $clubs_array = get_posts(array( 'post_type'=> 'club', 'posts_per_page' => -1 )); // grab all posts of type "club" ##

                            foreach ( $clubs_array as $c ) { // loop over all clubs ##

                                #$clubs[$c->ID] = $c; // grab club ID ##
                                echo "\n\t<option value='" . esc_attr( $c->ID ) . "'>$c->post_title</option>";

                            }

?>
                        </select>
                    </td>
                </tr>
<?php

                } // clubs ##

?>
                <tr valign="top" class="toggleable">
                    <th scope="row"><label><?php _e( 'Registered', 'export-user-data' ); ?></label></th>
                    <td>
                        <input type="text" id="q_eud_users_start_date" name="start_date" value="<?php echo $this->start_date; ?>" class="start-datepicker" />
                        <input type="text" id="q_eud_users_end_date" name="end_date" value="<?php echo $this->end_date; ?>" class="end-datepicker" />
                        <p class="description"><?php
                            printf(
                                __( 'Pick a start and end user registration date to limit the results.', 'export-user-data' )
                            );
                        ?></p>
                    </td>
                </tr>

                <tr valign="top" class="toggleable">
                    <th scope="row"><label><?php _e( 'Limit Range', 'export-user-data' ); ?></label></th>
                    <td>
                        <input name="limit_offset" type="text" id="q_eud_users_limit_offset" value="<?php echo( $this->limit_offset ); ?>" class="regular-text code numeric" style="width: 136px;" placeholder="<?php _e( 'Offset', 'export-user-data' ); ?>">
                        <input name="limit_total" type="text" id="q_eud_users_limit_total" value="<?php echo ( $this->limit_total ); ?>" class="regular-text code numeric" style="width: 136px;" placeholder="<?php _e( 'Total', 'export-user-data' ); ?>">
                        <p class="description"><?php
                            printf(
                                __( 'Enter an offset start number and a total number of users to export. <a href="%s" target="_blank">%s</a>', 'export-user-data' )
                                ,   esc_html('http://codex.wordpress.org/Function_Reference/get_users#Parameters')
                                ,   'Codex'
                            );
                        ?></p>
                    </td>
                </tr>

                <tr valign="top" class="toggleable">
                    <th scope="row"><label><?php _e( 'Updated Since', 'export-user-data' ); ?></label></th>
                    <td>
                        <input type="text" id="q_eud_updated_since_date" name="updated_since_date" value="<?php echo $this->updated_since_date; ?>" class="updated-datepicker" />
                        <select id="bp_field_updated_since" name="bp_field_updated_since">
<?php

                        foreach ( $bp_fields as $key ) {
			    if ( $this->field_updated_since == $key ) {
                            	echo "<option value='".esc_attr( $key )."' title='".esc_attr( $key )."' selected>$key</option>";
			    } else {
                            	echo "<option value='".esc_attr( $key )."' title='".esc_attr( $key )."'>$key</option>";
			    }
                        }

?>
                        </select>

                        <p class="description"><?php
                            printf(
                                __( 'Limit the results to users who have updated this extended profile field after this date.', 'export-user-data' )
                            );
                        ?></p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="q_eud_users_format"><?php _e( 'Format', 'export-user-data' ); ?></label></th>
                    <td>
                        <select name="format" id="q_eud_users_format">
<?php
                            if ( isset ( $this->format ) && ( $this->format == 'excel' ) ) {
                              echo '<option selected value="excel">' . __( 'Excel', 'export-user-data' ) . '</option>';
                            } else {
                              echo '<option value="excel">' . __( 'Excel', 'export-user-data' ) . '</option>';
                            }
                            if ( isset ( $this->format ) && ( $this->format == 'csv' ) ) {
                              echo '<option selected value="csv">' . __( 'CSV', 'export-user-data' ) . '</option>';
                            } else {
                              echo '<option value="csv">' . __( 'CSV', 'export-user-data' ) . '</option>';
                            }
?>
                        </select>
                        <p class="description"><?php
                            printf(
                                __( 'Select the format for the export file.', 'export-user-data' )
                            );
                        ?></p>
                    </td>
                </tr>

                <tr valign="top" class="remember">
                   <th scope="row"><label for="q_eud_save_options"><?php _e( 'Stored Options', 'export-user-data' ); ?></label></th>
                    <td>

                        <div class="row">
                            <input type="text" class="regular-text" name="save_new_export_name" id="q_eud_save_options_new_export" placeholder="<?php _e( 'Export Name', 'export-user-data' ); ?>" value="<?php echo isset( $_POST['export_name'] ) ? $_POST['export_name'] : '' ; ?>">
                            <input type="submit" id="save_export" class="button-primary" name="save_export" value="<?php _e( 'Save', 'export-user-data' ); ?>" />
                        </div>
                        <?php

                        // check if the user has any saved exports ##
                        if ( $this->get_user_options() ) {

?>
                        <div class="row">
                            <select name="export_name" id="q_eud_save_options" class="regular-text">
<?php

                                // loop over each saved export ##
                                foreach( $this->get_user_options() as $export ) {

                                    // select Loaded export name, if selected ##
                                    if (
                                        isset( $_POST['load_export'] )
                                        && isset( $_POST['export_name'] )
                                        && ( $_POST['export_name'] == $export )
                                    ) {

                                        echo "<option selected value='$export'>$export</option>";

                                    // just list previous export name ##
                                    } else {

                                        echo "<option value='$export'>$export</option>";

                                    }

                                }

?>
                            </select>

                            <input type="submit" id="load_export" class="button-primary" name="load_export" value="<?php _e( 'Load', 'export-user-data' ); ?>" />
                            <input type="submit" id="delete_export" class="button-primary" name="delete_export" value="<?php _e( 'Delete', 'export-user-data' ); ?>" />
<?php

                            }

?>
                            </div>
                            <p class="description"><?php _e( 'Save, load or delete your stored export options.', 'export-user-data' ); ?></p>

                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="q_eud_xprofile"><?php _e( 'Advanced Options', 'export-user-data' ); ?></label>
                    </th>
                    <td>
                        <div class="toggle">
                            <a href="#"><?php _e( 'Show', 'export-user-data' ); ?></a>
                        </div>
                    </td>
                </tr>

            </table>
            <p class="submit">
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" />
                <input type="submit" class="button-primary" value="<?php _e( 'Run Export', 'export-user-data' ); ?>" />
            </p>
        </form>
        </div>

<?php
        }


        /**
         * Inline jQuery
         * @since       0.8.2
         */
        public function jquery()
        {

            // load the scripts on only the plugin admin page
            if (isset( $_GET['page'] ) && ( $_GET['page'] == 'export-user-data' ) ) {

?>
        <script>

        // lazy load in some jQuery validation ##
        jQuery(document).ready(function($) {

            // build super multiselect ##
            jQuery('#usermeta, #bp_fields, #bp_fields_update_time').multiSelect();

            //Select any fields from saved settings ##
            jQuery('#usermeta').multiSelect('select',([<?php echo( $this->quote_array( $this->usermeta_saved_fields ) ); ?>]));
            jQuery('#bp_fields').multiSelect('select',([<?php echo( $this->quote_array($this->bp_fields_saved_fields ) ); ?>]));
            jQuery('#bp_fields_update_time').multiSelect('select',([<?php echo( $this->quote_array( $this->bp_fields_update_time_saved_fields ) ); ?>]));

            // show only common ##
            jQuery('.usermeta-common').click(function(e){
                e.preventDefault();
                jQuery('#ms-usermeta .ms-selectable li.system').hide();
            });

            // show all ##
            jQuery('.usermeta-all').click(function(e){
                e.preventDefault();
                jQuery('#ms-usermeta .ms-selectable li').show();
            });

            // select all ##
            jQuery('.select-all').click(function(e){
                e.preventDefault();
                jQuery( jQuery(this).parent().parent().parent().find( 'select' ) ).multiSelect( 'select_all' );
            });

            // select none ##
            jQuery('.select-none').click(function(e){
                e.preventDefault();
                jQuery( jQuery(this).parent().parent().parent().find( 'select' ) ).multiSelect( 'deselect_all' );
            });


            // validate number inputs ##
            $("input.numeric").blur(function() {

                //console.log("you entered "+ $(this).val());

                if ( $(this).val() && ! $.isNumeric( $(this).val() ) ) {

                    //console.log("this IS NOT a number");
                    $(this).css({ 'background': 'red', 'color': 'white' }); // highlight error ##
                    $("p.submit .button-primary").attr('disabled','disabled'); // disable submit ##

                } else {

                    $(this).css({ 'background': 'white', 'color': '#333' }); // remove error highlighting ##
                    $("p.submit .button-primary").removeAttr('disabled'); // enable submit ##

                }

            });

            // toggle advanced options ##
            jQuery(".toggle a").click( function(e) {
                e.preventDefault();
                $toggleable = jQuery("tr.toggleable");
                $toggleable.toggle();
                if ( $toggleable.is(":visible") ) {
                    jQuery(this).text("<?php _e( 'Hide', 'export-user-data' ); ?>");
                } else {
                    jQuery(this).text("<?php _e( 'Show', 'export-user-data' ); ?>");
                }
            });

            // validate save button ##
            jQuery("#save_export").click( function(e) {

                // grab the value of the input ##
                var q_eud_save_options_new_export = jQuery('#q_eud_save_options_new_export').val();

                if ( ! q_eud_save_options_new_export || q_eud_save_options_new_export == '' ) {

                    e.preventDefault(); // stop things here ##
                    jQuery('#q_eud_save_options_new_export').addClass("error");

                }

            });

            // remove validation on focus ##
            jQuery("body").on( 'focus', '#q_eud_save_options_new_export', function(e) {

                jQuery(this).removeClass("error");

            });

<?php

            // method returns an object with "first" & "last" keys ##
            $dates = $this->get_user_registered_dates();

            // get date format from WP settings #
            $date_format = 'yy-mm-dd' ; // get_option('date_format') ? get_option('date_format') : 'yy-mm-dd' ;
            $start_of_week = get_option('start_of_week') ? get_option('start_of_week') : 'yy-mm-dd' ;
            #$this->log( 'Date format: '.$date_format );

?>

            // start date picker ##
            jQuery('.start-datepicker').datepicker( {
                dateFormat  : '<?php echo $date_format; ?>',
                minDate     : '<?php echo substr( $dates["0"]->first, 0, 10 ); ?>',
                maxDate     : '<?php echo substr( $dates["0"]->last, 0, 10 ); ?>',
                firstDay    : '<?php echo $start_of_week; ?>'
            } );

            // end date picker ##
            jQuery('.end-datepicker').datepicker( {
                dateFormat  : '<?php echo $date_format; ?>',
                minDate     : '<?php echo substr( $dates["0"]->first, 0, 10 ); ?>',
                maxDate     : '<?php echo substr( $dates["0"]->last, 0, 10 ); ?>',
                firstDay    : '<?php echo $start_of_week; ?>'
            } );

            // end date picker ##
			// might want to set minDate to something else, but not sure
			// what would be best for everyone
			jQuery('.updated-datepicker').datepicker( {
				dateFormat  : '<?php echo $date_format; ?>',
				minDate     : '<?php echo substr( $dates["0"]->first, 0, 10 ); ?>',
				maxDate	    : '0',
                firstDay    : '<?php echo $start_of_week; ?>'
			} );

        });

        </script>
<?php
            }

        }


        /**
         * Inline CSS
         * @since       0.8.2
         */
        public function css()
        {

            // load the scripts on only the plugin admin page
            if (isset( $_GET['page'] ) && ( $_GET['page'] == 'export-user-data' ) ) {
?>
        <style>
            .toggleable { display: none; }
            .hidden { display: none; }
        </style>
<?php
            }

        }


        /**
         * Data to exclude from export
         */
        protected function get_exclude_fields()
        {

            $exclude_fields = array (
                    'user_pass'
                ,   'q_eud_exports'
                #,   'user_activation_key'
            );

            return apply_filters( 'export_user_data_exclude_fields', $exclude_fields );

        }


        /**
         * Get the array of standard WP_User fields to return
         */
        protected function get_user_fields()
        {

			// standard wp_users fields ##
			if ( isset( $_POST['user_fields'] ) && '1' == $_POST['user_fields'] ) {

				// debug ##
				#$this->log( 'full' );

				// exportable user data ##
				$user_fields = array(
						'ID'
					,   'user_login'
					#,  'user_pass'
					,   'user_nicename'
					,   'user_email'
					,   'user_url'
					,   'user_registered'
					#,  'user_activation_key'
					,   'user_status'
					,   'display_name'
				);

			} else {

				// debug ##
				#$this->log( 'reduced' );

				// just return the user ID
				$user_fields = array(
						'ID'
				);

			}

			// kick back values ##
            return apply_filters( 'export_user_data_user_fields', $user_fields );

        }


        /**
         * Get the array of special user fields to return
         */
        protected function get_special_fields()
        {

            // exportable user data ##
            $special_fields = array(
                #    'roles'     // list of WP Roles
                #,   'groups'    // BP Groups
            );

			// should we allow groups ##
			if ( isset( $_POST['groups'] ) && '1' == $_POST['groups'] ) {

				$special_fields[] = 'groups'; // add groups ##

			}

			// should we allow groups ##
			if ( isset( $_POST['roles'] ) && '1' == $_POST['roles'] ) {

				$special_fields[] = 'roles'; // add groups ##

			}

			// kick back the array ##
            return apply_filters( 'export_user_data_special_fields', $special_fields );

        }


        /*
         * Pre User Query
         */
        public function pre_user_query( $user_search )
        {

            global $wpdb;

            $where = '';

            if ( ! empty( $_POST['start_date'] ) ) {

                $date = new DateTime( sanitize_text_field ( $_POST['start_date'] ). ' 00:00:00' );
                $date_formatted = $date->format( 'Y-m-d H:i:s' );

                $where .= $wpdb->prepare( " AND $wpdb->users.user_registered >= %s", $date_formatted );

            }
            if ( ! empty( $_POST['end_date'] ) ) {

                $date = new DateTime( sanitize_text_field ( $_POST['end_date'] ). ' 00:00:00' );
                $date_formatted = $date->format( 'Y-m-d H:i:s' );

                $where .= $wpdb->prepare( " AND $wpdb->users.user_registered < %s", $date_formatted );

            }

			// search by last update time of BP extended fields ##
			if (
                ( isset ($_POST['updated_since_date'] ) && $_POST['updated_since_date'] != '' )
                && (isset ($_POST['bp_field_updated_since'] ) && $_POST['bp_field_updated_since'] != '' )
            ) {

    			$last_updated_date = new DateTime( sanitize_text_field ( $_POST['updated_since_date'] ) . ' 00:00:00' );
    			$this->updated_since_date = $last_updated_date->format( 'Y-m-d H:i:s' );
    			$this->field_updated_since = sanitize_text_field ( $_POST['bp_field_updated_since'] );
    			$field_updated_since_id = BP_Xprofile_Field::get_id_from_name( $this->field_updated_since );
    			$user_search->query_from .=  " JOIN `wp_bp_xprofile_data` XP ON XP.user_id = wp_users.ID ";
    			$where .= $wpdb->prepare( " AND XP.field_id = %s AND XP.last_updated >= %s", $field_updated_since_id, $this->updated_since_date );

			}

            if ( ! empty( $where ) ) {

                $user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1 $where", $user_search->query_where );

            }

            #wp_die( $this->pr( $user_search ) );
            return $user_search;

        }


        /**
         * Export Date Options
         *
         * @since       0.9.6
         * @global      type    $wpdb
         * @return      Array of objects
         */
        protected function get_user_registered_dates()
        {

            // invite in global objects ##
            global $wpdb;

            // query user table for oldest and newest registration ##
            $range =
                $wpdb->get_results (
                    #$wpdb->prepare (
                        "
                        SELECT
                            MIN( user_registered ) AS first,
                            MAX( user_registered ) AS last
                        FROM
                            {$wpdb->users}
                        "
                    #)
                );

            return $range;

        }


        /**
         * Quote array elements and separate with commas
         *
         * @since       0.9.6
         * @return      String
         */
        protected function quote_array( $array )
        {

           $prefix = ''; // starts empty ##
           $elementlist = '';
           if ( is_array( $array ) ) {
               foreach( $array as $element ) {
                   $elementlist .= $prefix . "'" . $element . "'";
                   $prefix = ','; // prefix all remaining items with a comma ##
               }
           }

           // kick back string to function caller ##
           return( $elementlist );

         }


        /**
         * Recursively implodes an array
         *
         * @since    1.0.1
         * @access   public
         * @param    array       $array          multi-dimensional array to recursively implode
         * @param    string      $glue           value that glues elements together
         * @param    bool        $include_keys   include keys before their values
         * @param    bool        $trim_all       trim ALL whitespace from string
         * @return   string      imploded array
         */
        protected function recursive_implode( $array, $return = null, $glue = '|' )
        {

            // unserialize ##
            $array = $this->unserialize( $array );

            // kick it back ##
            if ( is_null ( $return ) && ! is_array( $array ) ) {

                return $array;

            }

            // empty return ##
            if ( is_null ( $return ) ) {

                $return = '';

            } else {

                if ( "||" == $glue ) {

                    $glue = '|||';

                } else if ( "|" == $glue ) {

                    $glue = '||';

                }

            }

            // loop ##
            foreach( $array as $key => $value ) {

                // unserialize ##
                $value = $this->unserialize( $value );

                if( is_array( $value ) ) {

                    $return .= $glue . $key . $glue . $this->recursive_implode( $value, $return, $glue );

                } else {

                    $return .= $glue . $key . $glue . $value;

                }

            }

            // Removes first $glue from string ##
            if ( $glue && $return && $return[0] == '|' ) {

                $return = ltrim ( $return, '|' );

            }

            // Trim ALL whitespace ##
            if ( $return ) {

                $return = preg_replace( "/(\s)/ixsm", '', $return );

            }

            // kick it back ##
            return $return;

        }


        /**
         * Save Unserializer
         *
         * @since       1.1.4
         */
        protected function unserialize( $value )
        {

            // the $value is serialized ##
            if ( is_serialized( $value ) ) {

                // unserliaze to new variable ##
                $unserialized = @unserialize( $value );

                // test if unserliazing produced errors ##
                if ( $unserialized !== false || $value == 'b:0;' ) {

                    #$value = 'UNSERIALIZED_'.$unserialized;
                    $value = $unserialized;

                } else {

                    // failed to unserialize - data potentially corrupted in db ##
                    #$value = 'NOT_SERIALIZED_'.$value;
                    $value = $value;

                }

            }

            // kick it back ##
            return $value;

        }


		/**
		 * Encode special characters
		 *
		 * @param		type		$string
		 * @return		string		Encoding string
		 * @since		1.2.3
		 */
		protected function special_characters( $string = null )
		{

			// sanity check ##
			if ( is_null( $string ) ) {

				return false;

			}

			// kick it back in a nicer format ##
			return htmlentities( $string, ENT_COMPAT, 'UTF-8' );

		}


    }

    // hook plugin to workdpress action - defined in class ##
    add_action( Q_EUD_HOOK, 'q_export_user_data', Q_EUD_PRIORITY );
    /**
     * Instatiate class and load up schortcode
     *
     * @since 1.2.8
     * @return void
     */
    function q_export_user_data()
    {

        // admin only ##
        if ( ! is_admin() ) { return false; }

        // instatiate class ##
        $export_user_data = new Q_Export_User_Data();

        // hook into wp fitlers and actions ##
        $export_user_data->run_hooks();

    }


}
