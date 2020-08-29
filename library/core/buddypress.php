<?php

namespace q\eud\core;

use q\eud\core\core as core;
use q\eud\core\helper as helper;

// load it up ##
\q\eud\core\buddypress::run();

class buddypress extends \q_export_user_data {

    public static function run()
    {

        if ( \is_admin() ) {

            // load BP ##
            \add_action( 'admin_init', array( get_class(), 'load' ), 1000001 );

        }

    }

    
    /**
    * Get BP fields from DB, if BuddyPress is installed and active
    *
    * @since    2.0.0
    */
    public static function get_fields()
    {

        // buddypress support deprecated for now ##
        return false;

        if ( ! function_exists ('bp_is_active') ) { 

            return false;

        }

        // introduce global class object ##
        global $wpdb;

        // grab all buddypress x profile fields ##
        $bp_fields = $wpdb->get_results( "SELECT distinct(name) FROM ".$wpdb->base_prefix."bp_xprofile_fields WHERE parent_id = 0" );

        // get name value from object ##
        $bp_fields = \wp_list_pluck( $bp_fields, 'name' );

        // test array ##
        #helper::log( $bp_fields );

        // allow array to be filtered ##
        $bp_fields = \apply_filters( 'export_user_data_bp_fields', $bp_fields );

        // kick it back ##
        return $bp_fields;

    }



    /**
    * Load up saved exports for this user
    * Set to public as hooked into action
    *
    * @since       0.9.6
    * @return      Array of saved exports
    */
    public static function load()
    {

        // do we have a bp object in the globals ##
        if (
            \is_plugin_active( 'buddypress/bp-loader.php' ) // plugin active
            && function_exists ( 'buddypress' ) // loader function exists ##
            && ! isset( $GLOBALS['bp'] ) // but global unavailble ##
        ) {

            helper::log( 'BP not loaded - calling buddypress()' );

            // call BP
            buddypress();

            return true;

        }

        #self::log( 'BP loaded' );

        return true;

    }



}
