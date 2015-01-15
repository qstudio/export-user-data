<?php

/*
Plugin Name: Export User Data
Plugin URI: http://qstudio.us/plugins/
Description: Export User data, metadata and BuddyPress X-Profile data.
Version: 1.1.1
Author: Q Studio
Author URI: http://qstudio.us
License: GPL2
Text Domain: export-user-data
*/

// quick check :) ##
defined( 'ABSPATH' ) OR exit;

// Increase maximum execution time to prevent "Maximum execution time 
// exceeded" error
ini_set('max_execution_time', 3600); 

/* Check for Class */
if ( ! class_exists( 'Q_Export_User_Data' ) ) 
{
    
    // plugin version
    define( 'Q_EXPORT_USER_DATA_VERSION', '1.1.1' ); // version ##
    
    // instatiate class via hook, only if inside admin
    if ( is_admin() ) {
    
        // instatiate plugin via WP hook - not too early, not too late ##
        add_action( 'init', array ( 'Q_Export_User_Data', 'get_instance' ), 0 ); 

    }
    
    
    /**
     * Main plugin class
     *
     * @since 0.1
     **/
    class Q_Export_User_Data {
        
        
        // Refers to a single instance of this class. ##
        private static $instance = null;
                
        /* properties */
        public $text_domain = 'export-user-data'; // for translation ##
        private $q_eud_exports = ''; // export settings ##
        private $usermeta_saved_fields = array();
        private $bp_fields_saved_fields = array();
        private $bp_fields_update_time_saved_fields = array();
        private $role = '';
        private $roles = '1';
        private $groups = '1';
        private $start_date = '';
        private $end_date = '';
        private $limit_offset = '';
        private $limit_total = '';
        private $format = '';
 
        
        /**
         * Creates or returns an instance of this class.
         *
         * @return  Foo     A single instance of this class.
         */
        public static function get_instance() {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }
        
        
        /**
         * Class contructor
         *
         * @since 0.1
         **/
        private function __construct() 
        {

            if ( is_admin() ) {
                
                add_action( 'init', array( $this, 'load_plugin_textdomain' ), 1 );  
                add_action( 'init', array( $this, 'load_user_options' ), 2 );  
                add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
                add_action( 'init', array( $this, 'generate_data' ), 3 );
                add_filter( 'q_eud_exclude_data', array( $this, 'exclude_data' ) );
                add_action( 'admin_enqueue_scripts', array( $this, 'add_css_and_js' ), 1 );
                add_action( 'admin_footer', array( $this, 'jquery' ), 100000 );
                add_action( 'admin_footer', array( $this, 'css' ), 100000 );
                
            }

        }
        
        
        /**
         * Load plugin text-domain
         * 
         * @since       0.9.0
         * @return      void
         **/
        public function load_plugin_textdomain() 
        {
        
            load_plugin_textdomain( $this->text_domain, false, basename( dirname( __FILE__ ) ) . '/languages' );
            
        }

        /**
         * Add administration menus
         *
         * @since 0.1
         **/
        public function add_admin_pages() 
        {

            add_users_page( __( 'Export User Data', $this->text_domain ), __( 'Export User Data', $this->text_domain ), 'list_users', $this->text_domain, array( $this, 'users_page' ) );

        }


        /**
         * style and interaction 
         */
        public function add_css_and_js( $hook ) 
        {

            // load the scripts on only the plugin admin page ##
            if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->text_domain ) ) {

                wp_register_style( 'q_export_user_data', plugins_url( 'css/export-user-data.css' ,__FILE__ ));
                wp_enqueue_style( 'q_export_user_data' );
                wp_enqueue_script( 'q_eud_multi_select_js', plugins_url( 'js/jquery.multi-select.js', __FILE__ ), array('jquery'), '0.9.8', false );
                
                // add script ##
                wp_enqueue_script('jquery-ui-datepicker');

                // add style ##
                wp_enqueue_style( 'jquery-ui-datepicker' );
                wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
                
            } 

        }
        
        
        /**
         * Return Byte count of $val
         * 
         * @link        http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue?replies=2
         * @since       0.9.6
         */
        public function return_bytes( $val )
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
         * clean that stuff up
         */
        public function sanitize( $value ) 
        {

            $value = str_replace("\r", '', $value);
            $value = str_replace("\n", '', $value);
            $value = str_replace("\t", '', $value);
            return $value;

        }

        
        /**
         * Load up saved exports for this user
         *
         * @since       0.9.6
         * @return      Array of saved exports
         */
        public function load_user_options()
        {
            
            
            $this->q_eud_exports = get_user_meta( get_current_user_id(), 'q_eud_exports' ) ? get_user_meta( get_current_user_id(), 'q_eud_exports', true ) : array() ; 
            #var_dump( $this->q_eud_exports );
            
        }
        
        
        /**
         * Get list of saved exports for this user
         *
         * @since       0.9.4
         * @return      Array of saved exports
         */
        public function get_user_options()
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
        public function get_user_options_by_export( $export = null )
        {
            
            // sanity check ##
            if ( is_null ( $export ) ) { return false; }
            
            if ( isset( $this->q_eud_exports[$export] ) ) {

                  $this->usermeta_saved_fields = $this->q_eud_exports[$export]['usermeta_saved_fields'];
                  $this->bp_fields_saved_fields = $this->q_eud_exports[$export]['bp_fields_saved_fields'];
                  $this->bp_fields_update_time_saved_fields = $this->q_eud_exports[$export]['bp_fields_update_time_saved_fields'];
                  $this->role = $this->q_eud_exports[$export]['role'];
                  $this->roles = $this->q_eud_exports[$export]['roles'];
                  $this->groups = $this->q_eud_exports[$export]['groups'];
                  $this->start_date = $this->q_eud_exports[$export]['start_date'];
                  $this->end_date = $this->q_eud_exports[$export]['end_date'];
                  $this->limit_offset = $this->q_eud_exports[$export]['limit_offset'];
                  $this->limit_total = $this->q_eud_exports[$export]['limit_total'];
                  $this->format = $this->q_eud_exports[$export]['format'];

            } else {

                  $this->usermeta_saved_fields = array();
                  $this->bp_fields_saved_fields = array();
                  $this->bp_fields_update_time_saved_fields = array();
                  $this->role = '';
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
         * method to store user options
         *
         * @param       string      $save_export        Export Key name
         * @param       array       $save_options       Array of export options to save
         * @since       0.9.3
         * @return      void
         */
        public function set_user_options( $key = null, $options = null )
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
        public function delete_user_options( $key = null )
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
         */
        private static function get_all_for_user( $user_id = null ) {
            
            // sanity check ##
            if ( is_null( $user_id ) ) { return false; }
            
            global $wpdb, $bp;

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
         * Process content of CSV file
         *
         * @since 0.1
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
 
            // check admin referer ##
            check_admin_referer( 'q-eud-export-user-page_export', '_wpnonce-q-eud-export-user-page_export' );
            
            // build argument array ##
            $args = array(
                'fields'    => 'all',
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

            // compile final fields list ##
            $fields = array_merge( $this->get_user_fields(), $this->get_special_fields(), $usermeta_fields, $bp_fields_passed, $bp_fields_update_passed );
            
            // test field array ##
            #$this->pr( $fields );
            
            // build the document headers ##
            $headers = array();
            
            foreach ( $fields as $key => $field ) {

                // rename programs field ##
                if ( $field == 'member_of_club' ){
                    $field = 'Program';
                }
                
                // grab fields to exclude from exports ## 
                if ( in_array( $field, $this->get_exclude_fields() ) ) {

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

            // build row values for each user ##
            foreach ( $users as $user ) {
                
                // check if we're hitting any Memory limits, if so flush them out ##
                // per http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue?replies=2
		if ( memory_get_usage( true ) > $memory_limit ) {
                    wp_cache_flush();
                }

                // open up a new empty array ##
                $data = array();

                // BP loaded ? ##
                if ( function_exists ( 'bp_is_active' ) ) {
                    
                    #$bp_data = BP_XProfile_ProfileData::get_all_for_user( $user->ID );
                    $bp_data = self::get_all_for_user( $user->ID ); // taken from old BP method ##
                    
                }
                
                // single query method - get all user_meta data ##
                $get_user_meta = (array)get_user_meta( $user->ID );
                #wp_die( $this->pr( $get_user_meta ) );
                
                // Filter out empty meta data ##
                $get_user_meta = array_filter( array_map( function( $a ) {
                    return $a[0];
                }, $get_user_meta ) );
                
                // loop over each field ##
                foreach ( $fields as $field ) {
                    
                    // check if this is a BP field ##
                    if ( isset( $bp_data ) && isset( $bp_data[$field] ) && in_array( $field, $bp_fields_passed ) ) 
                    {

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
                        
                        $value = $this->sanitize($value);

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
                    elseif ( isset( $_POST['roles'] ) && $_POST['roles'] == '1' && $field == 'roles' )
                    {
                        
                        // add "Role" as $value ##
                        $value = isset( $user->roles[0] ) ? implode( $user->roles, '|' ) : '' ; // empty value if no role found - or flat array of user roles ##
                    
                    // include the user's BP group in the export ##
                    } 
                    elseif ( isset( $_POST['groups'] ) && $_POST['groups'] == '1' && $field == 'groups' ) 
                    {
                        
                        if ( function_exists( 'groups_get_user_groups' ) ) {
                        
                            // check if user is a member of any groups ##
                            $group_ids = groups_get_user_groups( $user->ID );

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
                    elseif ( $field == 'bp_latest_update' ) 
                    {
                        
                        // https://bpdevel.wordpress.com/2014/02/21/user-last_activity-data-and-buddypress-2-0/ ##
                        $value = bp_get_user_last_activity( $user->ID );
                        
                    // user or usermeta field ##
                    } 
                    else 
                    { 
                        
                        // the user_meta key isset ##
                        if ( isset( $get_user_meta[$field] ) ) {
                            
                            // take from the bulk get_user_meta call ##
                            $value = $get_user_meta[$field];
                        
                        // standard WP_User value ##
                        } else {
                            
                            // use the magically assigned value from WP_Users
                            $value = $user->{$field};
                            
                        }
                        
                       
                        // the $value is serialized ##
                        if ( is_serialized( $value ) ) {
                            
                            // unserliaze to new variable ##
                            $unserialized = @unserialize( $value );
                            
                            // test if unserliazing produced errors ##
                            if ( $unserialized !== false || $value == 'b:0;' ) {
                                
                                #$value = 'UNSERIALIZED_'.$value;
                                $value = $unserialized;
                                
                            } else {
                                
                                // failed to unserialize - data potentially corrupted in db ##
                                $value = $value;
                                
                            }
                            
                        }
                            
                        // the value is an array ##                        
                        if ( is_array ( $value ) ) {

                            // recursive implode it ##
                            $value = self::recursive_implode( $value );

                        }
                            
                    }
                    
                    
                    // correct program value to Program Name ##
                    if ( $field == 'member_of_club' ) {
                        
                        $value = get_the_title($value);
                        
                    }
                    
                    // wrap values in quotes and add to array ##
                    if ( $is_csv ) {
                        
                        $data[] = '"' . str_replace( '"', '""', $value ) . '"';
                        
                    // just add to array ##
                    } else {
                        
                        $data[] = $value;
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
                
                wp_die( __( 'You do not have sufficient permissions to access this page.', $this->text_domain ) );
                
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
                    $groups = isset( $_POST['groups'] ) ? $_POST['groups'] : '0' ;
                    $start_date = isset( $_POST['start_date'] ) ? $_POST['start_date'] : '' ;
                    $end_date = isset( $_POST['end_date'] ) ? $_POST['end_date'] : '' ;
                    $limit_offset = isset( $_POST['limit_offset'] ) ? $_POST['limit_offset'] : '' ;
                    $limit_total = isset( $_POST['limit_total'] ) ? $_POST['limit_total'] : '' ;
                    
                    // assign all values to an array ##
                    $save_array = array (
                        'usermeta_saved_fields' => $usermeta,
                        'bp_fields_saved_fields' => $bp_fields,
                        'bp_fields_update_time_saved_fields' => $bp_fields_update,
                        'role' => $role,
                        'roles' => $roles,
                        'groups' => $groups,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'limit_offset' => $limit_offset,
                        'limit_total' => $limit_total,
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
            
?>
    <div class="wrap">
        <h2><?php _e( 'Export User Data', $this->text_domain ); ?></h2>
<?php

        // nothing happening? ##
        if ( isset( $_GET['error'] ) ) {
            echo '<div class="updated"><p><strong>' . __( 'No users found.', $this->text_domain ) . '</strong></p></div>';
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
                        <label for="q_eud_usermeta"><?php _e( 'User Meta Fields', $this->text_domain ); ?></label>
                        <p class="filter" style="margin: 10px 0 0;">
                            <?php _e('Filter', $this->text_domain); ?>: <a href="#" class="usermeta-all"><?php _e('All', $this->text_domain); ?></a> | <a href="#" class="usermeta-common"><?php _e('Common', $this->text_domain); ?></a>
                        </p>
                        <p class="filter" style="margin: 10px 0 0;">
                            <?php _e('Select', $this->text_domain); ?>: <a href="#" class="select-all"><?php _e('All', $this->text_domain); ?></a> | <a href="#" class="select-none"><?php _e('None', $this->text_domain); ?></a>
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
                                __( 'Select the user meta keys to export, use the filters to simplify the list.', $this->text_domain )
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
                        <label for="q_eud_xprofile"><?php _e( 'BP xProfile Fields', $this->text_domain ); ?></label>
                        <p class="filter" style="margin: 10px 0 0;">
                            <?php _e('Select', $this->text_domain); ?>: <a href="#" class="select-all"><?php _e('All', $this->text_domain); ?></a> | <a href="#" class="select-none"><?php _e('None', $this->text_domain); ?></a>
                        </p>
                    </th>
                    <td>
                        <select multiple="multiple" id="bp_fields" name="bp_fields[]">
<?php

                        foreach ( $bp_fields as $key ) {

                            // tidy up key ##
                            $key_tidy = str_replace( ' ', '__', ($key));

                            #echo "<label for='".esc_attr( $key_tidy )."'><input id='".esc_attr( $key_tidy )."' type='checkbox' name='bp_fields[]' value='".esc_attr( $key_tidy )."'/> $key</label><br />";

                            // print key ##
                            echo "<option value='".esc_attr( $key )."' title='".esc_attr( $key )."'>$key</option>";

                        }

?>
                        </select>
                        <p class="description"><?php 
                            printf( 
                                __( 'Select the BuddyPress XProfile keys to export', $this->text_domain )
                            ); 
                        ?></p>
                    </td>
                </tr>
<?php

                // allow export of update times ##

?>
                <tr valign="top" class="toggleable">
                    <th scope="row">
                        <label for="q_eud_xprofile"><?php _e( 'BP xProfile Fields Update Time', $this->text_domain ); ?></label>
                        <p class="filter" style="margin: 10px 0 0;">
                            <?php _e('Select', $this->text_domain); ?>: <a href="#" class="select-all"><?php _e('All', $this->text_domain); ?></a> | <a href="#" class="select-none"><?php _e('None', $this->text_domain); ?></a>
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
                                __( 'Select the BuddyPress XProfile keys updated dates to export', $this->text_domain )
                            ); 
                        ?></p>
                    </td>
                </tr>

                <tr valign="top" class="toggleable">
                    <th scope="row"><label for="groups"><?php _e( 'BP User Groups', $this->text_domain ); ?></label></th>
                    <td>
                        <input id='groups' type='checkbox' name='groups' value='1' <?php checked( isset ( $this->groups ) ? intval ( $this->groups ) : '', 1 ); ?> />
                        <p class="description"><?php 
                            printf( 
                                __( 'Include BuddyPress Group Data. <a href="%s" target="_blank">%s</a>', $this->text_domain )
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
                    <th scope="row"><label for="q_eud_users_role"><?php _e( 'Role', $this->text_domain ); ?></label></th>
                    <td>
                        <select name="role" id="q_eud_users_role">
<?php

                            echo '<option value="">' . __( 'All Roles', $this->text_domain ) . '</option>';
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
                                __( 'Filter the exported users by a WordPress Role. <a href="%s" target="_blank">%s</a>', $this->text_domain )
                                ,   esc_html('http://codex.wordpress.org/Roles_and_Capabilities')
                                ,   'Codex'
                            ); 
                        ?></p>
                    </td>
                </tr>
                
                <tr valign="top" class="toggleable">
                    <th scope="row"><label for="roles"><?php _e( 'User Roles', $this->text_domain ); ?></label></th>
                    <td>
                        <input id='roles' type='checkbox' name='roles' value='1' <?php checked( isset ( $this->roles ) ? intval ( $this->roles ) : '', 1 ); ?> />
                        <p class="description"><?php 
                            printf( 
                                __( 'Include all of the users <a href="%s" target="_blank">%s</a>', $this->text_domain )
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
                    <th scope="row"><label for="q_eud_users_program"><?php _e( 'Programs', $this->text_domain ); ?></label></th>
                    <td>
                        <select name="program" id="q_eud_users_program">
<?php

                            echo '<option value="">' . __( 'All Programs', $this->text_domain ) . '</option>';

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
                    <th scope="row"><label><?php _e( 'Registered', $this->text_domain ); ?></label></th>
                    <td>
                        <input type="text" id="q_eud_users_start_date" name="start_date" value="<?php echo $this->start_date; ?>" class="start-datepicker" />
                        <input type="text" id="q_eud_users_end_date" name="end_date" value="<?php echo $this->end_date; ?>" class="end-datepicker" />
                        <p class="description"><?php 
                            printf( 
                                __( 'Pick a start and end user registration date to limit the results.', $this->text_domain )
                            ); 
                        ?></p>
                    </td>
                </tr>
                
                <tr valign="top" class="toggleable">
                    <th scope="row"><label><?php _e( 'Limit Range', $this->text_domain ); ?></label></th>
                    <td>
                        <input name="limit_offset" type="text" id="q_eud_users_limit_offset" value="<?php echo( $this->limit_offset ); ?>" class="regular-text code numeric" style="width: 136px;" placeholder="<?php _e( 'Offset', $this->text_domain ); ?>">
                        <input name="limit_total" type="text" id="q_eud_users_limit_total" value="<?php echo ( $this->limit_total ); ?>" class="regular-text code numeric" style="width: 136px;" placeholder="<?php _e( 'Total', $this->text_domain ); ?>">
                        <p class="description"><?php 
                            printf( 
                                __( 'Enter an offset start number and a total number of users to export. <a href="%s" target="_blank">%s</a>', $this->text_domain )
                                ,   esc_html('http://codex.wordpress.org/Function_Reference/get_users#Parameters')
                                ,   'Codex'
                            ); 
                        ?></p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="q_eud_users_format"><?php _e( 'Format', $this->text_domain ); ?></label></th>
                    <td>
                        <select name="format" id="q_eud_users_format">
<?php
                            if ( isset ( $this->format ) && ( $this->format == 'excel' ) ) {
                              echo '<option selected value="excel">' . __( 'Excel', $this->text_domain ) . '</option>';
                            } else {
                              echo '<option value="excel">' . __( 'Excel', $this->text_domain ) . '</option>';
                            }
                            if ( isset ( $this->format ) && ( $this->format == 'csv' ) ) {
                              echo '<option selected value="csv">' . __( 'CSV', $this->text_domain ) . '</option>';
                            } else {
                              echo '<option value="csv">' . __( 'CSV', $this->text_domain ) . '</option>';
                            }
?>
                        </select>
                        <p class="description"><?php 
                            printf( 
                                __( 'Select the format for the export file.', $this->text_domain )
                            ); 
                        ?></p>
                    </td>
                </tr>
                
                <tr valign="top" class="remember">
                   <th scope="row"><label for="q_eud_save_options"><?php _e( 'Stored Options', $this->text_domain ); ?></label></th>
                    <td>

                        <div class="row">
                            <input type="text" class="regular-text" name="save_new_export_name" id="q_eud_save_options_new_export" placeholder="<?php _e( 'Export Name', $this->text_domain ); ?>" value="<?php echo isset( $_POST['export_name'] ) ? $_POST['export_name'] : '' ; ?>">
                            <input type="submit" id="save_export" class="button-primary" name="save_export" value="<?php _e( 'Save', $this->text_domain ); ?>" />
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

                            <input type="submit" id="load_export" class="button-primary" name="load_export" value="<?php _e( 'Load', $this->text_domain ); ?>" />
                            <input type="submit" id="delete_export" class="button-primary" name="delete_export" value="<?php _e( 'Delete', $this->text_domain ); ?>" />
<?php

                            }

?>             
                            </div>
                            <p class="description"><?php _e( 'Save, load or delete your stored export options.', $this->text_domain ); ?></p>
                        
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">
                        <label for="q_eud_xprofile"><?php _e( 'Advanced Options', $this->text_domain ); ?></label>
                    </th>
                    <td>
                        <div class="toggle">
                            <a href="#"><?php _e( 'Show', $this->text_domain ); ?></a>
                        </div>
                    </td>
                </tr>
                
            </table>
            <p class="submit">
                <input type="hidden" name="_wp_http_referer" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />
                <input type="submit" class="button-primary" value="<?php _e( 'Run Export', $this->text_domain ); ?>" />
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
            if (isset( $_GET['page'] ) && ( $_GET['page'] == $this->text_domain ) ) {
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
                    jQuery(this).text("<?php _e( 'Hide', $this->text_domain ); ?>");
                } else {
                    jQuery(this).text("<?php _e( 'Show', $this->text_domain ); ?>");
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
            $dates = self::get_user_registered_dates(); 
            
?>
            
            // start date picker ##
            jQuery('.start-datepicker').datepicker( {
                dateFormat  : 'yy-mm-dd',
                minDate     : '<?php echo substr( $dates["0"]->first, 0, 10 ); ?>',
                maxDate     : '<?php echo substr( $dates["0"]->last, 0, 10 ); ?>'
            } );
            
            // end date picker ##
            jQuery('.end-datepicker').datepicker( {
                dateFormat  : 'yy-mm-dd',
                minDate     : '<?php echo substr( $dates["0"]->first, 0, 10 ); ?>',
                maxDate     : '<?php echo substr( $dates["0"]->last, 0, 10 ); ?>'
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
            if (isset( $_GET['page'] ) && ( $_GET['page'] == $this->text_domain ) ) {
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
        public function get_exclude_fields() 
        {
            
            $exclude_fields = array (
                    'user_pass'
                #,   'user_activation_key'
            );
            
            return apply_filters( 'export_user_data_exclude_fields', $exclude_fields );

        }
        
        
        /**
         * Get the array of standard WP_User fields to return
         */
        public function get_user_fields() 
        {
            
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

            return apply_filters( 'export_user_data_user_fields', $user_fields );

        }
        
        
        /**
         * Get the array of special user fields to return
         */
        public function get_special_fields() 
        {
            
            // exportable user data ##
            $special_fields = array(
                    'roles'     // list of WP Roles
                ,   'groups'    // BP Groups
            );

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
                
            if ( ! empty( $where ) ) {
                
                $user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1 $where", $user_search->query_where );
                
            }

            #wp_die( self::pr( $user_search ) );
            return $user_search;

        }


        /**
         * Export Date Options
         * 
         * @since       0.9.6
         * @global      type    $wpdb
         * @return      Array of objects
         */
        private static function get_user_registered_dates()
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
            
            /*
            // invite in global objects ##
            global $wpdb, $wp_locale;

            // grab list of years and months available
            $months = $wpdb->get_results( "
                SELECT DISTINCT YEAR( user_registered ) AS year, MONTH( user_registered ) AS month, DAY( user_registered ) AS day
                FROM $wpdb->users
                ORDER BY user_registered DESC
            " );

            // check if we got a result ##
            $month_count = count( $months );
            
            // nothing cokking ##
            if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
                
                return;
                
            }

            #wp_die( self::pr( $months ) );
            
            // loop over each month ##
            foreach ( $months as $date ) {
                
                // skip if year == '0' ##
                if ( 0 == $date->year ) {
                    continue;
                }
                
                // make sure the month is in a MM two digit format ##
                $month = zeroise( $date->month, 2 );
                
                // build up a tae string - YYYY-MM ##
                $date_string = $date->year . '-' . $month;
                
                // check if passed date matches this string ##
                if ( $selected_date == $date_string ) {
                    
?>
                    <option selected value="<?php echo $date_string; ?>"><?php echo $wp_locale->get_month( $month ); ?> <?php echo $date->year; ?></option>
<?php
                    
                } else {
                    
?>
                    <option value="<?php echo $date_string; ?>"><?php echo $wp_locale->get_month( $month ); ?> <?php echo $date->year; ?></option>
<?php
                    
                }

            }
            */

        }

        
        /**
         * Quote array elements and separate with commas
         *
         * @since       0.9.6
         * @return      String
         */
        private function quote_array( $array )
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
        * @link     https://gist.github.com/jimmygle/2564610
        */ 
        public static function recursive_implode( array $array, $glue = '|', $include_keys = true, $trim_all = true )
        {
            
            $glued_string = '';
            $glue_count = 0;

            // Recursively iterates array and adds key/value to glued string ##
            array_walk_recursive( $array, function( $value, $key ) use ( $glue, $include_keys, &$glued_string, $glue_count )
            {
                $include_keys and $glued_string .= $key.$glue;
                $glued_string .= $value.$glue; //.'GC_'.$glue_count.$glue;
                $glue_count ++;
            });

            // Removes last $glue from string ##
            strlen( $glue) > 0 and $glued_string = substr( $glued_string, 0, -strlen( $glue ) );

            // Trim ALL whitespace ##
            $trim_all and $glued_string = preg_replace( "/(\s)/ixsm", '', $glued_string );

            return (string) $glued_string;
            
        }
         
        
        /**
         * Nicer var_dump
         * 
         * @since       0.9.6
         */
        public function pr ( $variable )
        {
            echo '<pre>';
            print_r ( $variable );
            echo '</pre>';
        }


    }

}
