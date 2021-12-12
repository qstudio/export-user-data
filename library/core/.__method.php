<?php

namespace q\eud\core;

// import classes ##
use q\eud;
use q\eud\plugin as plugin;
use q\eud\core\helper as h;

class method {

    /**
    * Get the array of standard WP_User fields to return
    */
    public static function get_user_fields(){

        // standard wp_users fields ##
        if ( isset( $_POST['user_fields'] ) && '1' == $_POST['user_fields'] ) {

            // debug ##
            #h::log( 'full' );

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
            #h::log( 'reduced' );

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
    public static function get_special_fields(){

        // exportable user data ##
        $special_fields = array(
            #    'roles'     // list of WP Roles
            #,   'groups'    // BP Groups
        );

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
    public static function get_exclude_fields(){

        $exclude_fields = array (
                'user_pass'
            ,   'q_eud_exports'
            #,   'user_activation_key'
        );

        // kick back array via filter ##
        return \apply_filters( 'q/eud/export/exclude_fields', $exclude_fields );

    }
}
