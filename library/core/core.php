<?php

namespace q\eud\core;

use q\eud\core\core as core;
use q\eud\core\helper as helper;

// load it up ##
#\q\eud\core\core::run();

class core extends \q_export_user_data {

    public static function run()
    {

        if ( \is_admin() ) {

            // load user options ##
            #\add_action( 'admin_init', array( get_class(), 'load_user_options' ), 1000002 );

        }

    }


    
    /**
    * Get the array of standard WP_User fields to return
    */
    public static function get_user_fields()
    {

        // standard wp_users fields ##
        if ( isset( $_POST['user_fields'] ) && '1' == $_POST['user_fields'] ) {

            // debug ##
            #self::log( 'full' );

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
            #self::log( 'reduced' );

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
    public static function get_special_fields()
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
    public static function get_exclude_fields()
    {

        $exclude_fields = array (
                'user_pass'
            ,   'q_eud_exports'
            #,   'user_activation_key'
        );

        // kick back array via filter ##
        return \apply_filters( 'q/eud/export/exclude_fields', $exclude_fields );

    }



    
    /**
    * Return Byte count of $val
    *
    * @link        http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue?replies=2
    * @since       0.9.6
    */
    public static function return_bytes( $val )
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
    public static function recursive_implode( $array, $return = null, $glue = '|' )
    {

        // unserialize ##
        $array = self::unserialize( $array );

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
            $value = self::unserialize( $value );

            if( is_array( $value ) ) {

				$return .= $glue . $key . $glue . self::recursive_implode( $value, $return, $glue );
				
			// add @2.1.0 from issue #4 - https://github.com/qstudio/export-user-data/issues/4
			} elseif(is_object($value)) {

                $return .= $glue . $key . $glue . self::recursive_implode( $value, $return, $glue );

            } else {

                $return .= $glue . $key . $glue . $value;

			}
			

        }

        // Removes first $glue from string ##
        if ( $glue && $return && $return[0] == '|' ) {

            $return = ltrim( $return, '|' );

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
    public static function unserialize( $value = null )
    {

        // the $value is serialized ##
        if ( \is_serialized( $value ) ) {

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
    public static function format_value( $string = null )
    {

        // sanity check ##
        if ( is_null( $string ) ) {

            return false;

        }

        // kick it back in a nicer format ##
        #return htmlentities( $string, ENT_COMPAT, 'UTF-8' );
        
        // kick it back via a filter to allow custom formatting ##
        return \apply_filters( 'q/eud/export/value', $string );

    }



    /**
    * Quote array elements and separate with commas
    *
    * @since       0.9.6
    * @return      String
    */
    public static function quote_array( $array )
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
    * Export Date Options
    *
    * @since       0.9.6
    * @global      type    $wpdb
    * @return      Array of objects
    */
    public static function get_user_registered_dates()
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
    * Sanitize data
    *
    * @since 1.2.8
    * @return string
    */
    public static function sanitize( $value )
    {

        // emove line breaks ##
        $value = str_replace("\r", '', $value);
        $value = str_replace("\n", '', $value);
        $value = str_replace("\t", '', $value);

        // with wp_kses ##
        $value = \wp_kses( $value, self::get_allowed_tags() );

        // with esc_html
        $value = \esc_html( $value );

        // return value ##
        return $value;

    }


    /**
    * Get allowed tags for wp_kses
    *
    * @since  1.2.8
    * @return Array
    */
    public static function get_allowed_tags()
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

        // kick back via filter ##
        return \apply_filters( 'q/eud/export/allowed_tags', $allowed_tags );

    }


}
