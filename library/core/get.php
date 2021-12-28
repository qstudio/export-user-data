<?php

namespace q\eud\core;

// import classes ##
use q\eud;
use q\eud\plugin as plugin;
use q\eud\core\helper as h;

class get {

    /**
    * Get the array of standard WP_User fields to return
    */
    public static function user_fields(){

        // standard wp_users fields ##
        if ( isset( $_POST['user_fields'] ) && '1' == $_POST['user_fields'] ) {

            // exportable user data ##
            $user_fields = array(
                    'ID'
                ,   'user_login'
                ,   'user_nicename'
                ,   'user_email'
                ,   'user_url'
                ,   'user_registered'
                ,   'user_status'
                ,   'display_name'
            );

        } else {

            // just return the user ID
            $user_fields = array(
				'ID'
            );

        }

        // kick back values via filter ##
        return \apply_filters( 'q/eud/export/user_fields', $user_fields );

    }

    /**
    * Get the array of special user fields to return
    */
    public static function special_fields(){

        // exportable user data ##
        $special_fields = [];

        // should we allow groups ##
        if ( isset( $_POST['groups'] ) && '1' == $_POST['groups'] ) {

            $special_fields[] = 'groups'; // add groups ##

        }

        // should we allow roles ##
        if ( isset( $_POST['roles'] ) && '1' == $_POST['roles'] ) {

            $special_fields[] = 'roles'; // add groups ##

        }

        // kick back the array via filter ##
        return \apply_filters( 'q/eud/export/special_fields', $special_fields );

    }

    /**
    * Data to exclude from export
    */
    public static function exclude_fields(){

        $exclude_fields = array (
                'user_pass'
            ,   'q_eud_exports'
        );

        // kick back array via filter ##
        return \apply_filters( 'q/eud/export/exclude_fields', $exclude_fields );

    }
    
    /**
    * Export Date Options
    *
    * @since       	0.9.6
    * @global      	type    $wpdb
	* @return      	Array of objects
	* @todo			Remove max date, as this makes little sense for exports not based on user reg dates.. ??	
    */
    public static function user_registered_dates(){

        // invite in global objects ##
        global $wpdb;

        // query user table for oldest and newest registration ##
        $range =
            $wpdb->get_results (
				"
				SELECT
					MIN( user_registered ) AS first,
					MAX( user_registered ) AS last
				FROM
					{$wpdb->users}
				"
            );

        return $range;

    }

}
