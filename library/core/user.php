<?php

namespace q\report\core;

use q\report\core\core as core;
use q\report\core\helper as helper;

// load it up ##
\q\report\core\user::run();

class user extends \q_export_user_data {

    public static function run()
    {

        if ( \is_admin() ) {

            // load user options ##
            \add_action( 'admin_init', array( get_class(), 'load' ), 1000002 );

        }

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

        return self::$q_report_exports =
            \get_user_meta( \get_current_user_id(), 'q_report_exports' ) ?
            \get_user_meta( \get_current_user_id(), 'q_report_exports', true ) :
            array() ;

        #self::log( $this->q_report_exports );

    }



    /**
    * Get list of saved exports for this user
    *
    * @since       0.9.4
    * @return      Array of saved exports
    */
    public static function get_user_options()
    {

        // get the stored options - filter empty array items ##
        $q_report_exports = array_filter( self::$q_report_exports );

        // quick check if the array is empty ##
        if ( empty ( $q_report_exports ) ) {

            return false;

        }

        // test the array of saved exports ##
        #$this->pr( $q_report_exports );

        // start with an empty array ##
        $exports = array();

        // loop over each saved export and grab each key ##
        foreach ( $q_report_exports as $key => $value ) {

            $exports[] = $key;

        }

        // kick back array ##
        return $exports;

    }


    /**
    * Check for and load stored user options
    *
    * @since       0.9.3
    * @return      void
    */
    public static function get_user_options_by_export( $export = null )
    {

        // sanity check ##
        if ( is_null ( $export ) ) { return false; }

        if ( isset( self::$q_report_exports[$export] ) ) {

            self::$usermeta_saved_fields = self::$q_report_exports[$export]['usermeta_saved_fields'];
            self::$bp_fields_saved_fields = self::$q_report_exports[$export]['bp_fields_saved_fields'];
            self::$bp_fields_update_time_saved_fields = self::$q_report_exports[$export]['bp_fields_update_time_saved_fields'];
            self::$updated_since_date = isset( self::$q_report_exports[$export]['updated_since_date'] ) ? self::$q_report_exports[$export]['updated_since_date'] : null ;
            self::$field_updated_since = isset( self::$q_report_exports[$export]['field_updated_since'] ) ? self::$q_report_exports[$export]['field_updated_since'] : null ;
            self::$role = self::$q_report_exports[$export]['role'];
            self::$roles = self::$q_report_exports[$export]['roles'];
            self::$groups = self::$q_report_exports[$export]['groups'];
            self::$user_fields = isset( self::$q_report_exports[$export]['user_fields'] ) ? self::$q_report_exports[$export]['user_fields'] : null ;
            self::$start_date = self::$q_report_exports[$export]['start_date'];
            self::$end_date = self::$q_report_exports[$export]['end_date'];
            self::$limit_offset = self::$q_report_exports[$export]['limit_offset'];
            self::$limit_total = self::$q_report_exports[$export]['limit_total'];
            self::$format = self::$q_report_exports[$export]['format'];

        } else {

            self::$usermeta_saved_fields = array();
            self::$bp_fields_saved_fields = array();
            self::$bp_fields_update_time_saved_fields = array();
            self::$updated_since_date = '';
            self::$field_updated_since = '';
            self::$role = '';
            self::$user_fields = '1';
            self::$roles = '1';
            self::$groups = '1';
            self::$start_date = '';
            self::$end_date = '';
            self::$limit_offset = '';
            self::$limit_total = '';
            self::$format = '';

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
    public static function set_user_options( $key = null, $options = null )
    {

        // sanity check ##
        if ( is_null ( $key ) || is_null ( $options ) ) {

            #$this->pr( 'missing save values' );
            return false;

        }

        #$this->pr( $key );
        #$this->pr( $options );

        // for now, I'm simply allowing keys to be resaved - but this is not so logical ##
        if ( array_key_exists( $key, self::$q_report_exports ) ) {

            #$this->pr( 'key exists, skipping save' );
            #return false;

        }

        if ( isset( $options ) && is_array( $options ) ) {

            // update_option sanitizes the option name but not the option value ##
            foreach ( $options as $field_name => $field_value ) {

                // so do that here. ##
                if ( is_array( $field_value ) ) {

                    foreach ( $field_value as $field_array_key => $field_array_value ) {

                        $options[$field_name][$field_array_key] = \sanitize_text_field( $field_array_value );

                    }

                } else {

                    $options[$field_name] = \sanitize_text_field( $field_value );

                }

            }

            // assign the sanitized array of values to the class property $q_report_exports as a new array with key $key ##
            self::$q_report_exports[$key] = $options;

            // update stored user_meta values, if previous key found ##
            if ( \get_user_meta( \get_current_user_id(), 'q_report_exports' ) !== false ) {

                #update_option( 'q_report_exports', $this->q_report_exports );
                \update_user_meta( \get_current_user_id(), 'q_report_exports', self::$q_report_exports );

            // create new user meta key ##
            } else {

                #add_option( 'q_report_exports', $this->q_report_exports, $deprecated, $autoload );
                \add_user_meta( \get_current_user_id(), 'q_report_exports', self::$q_report_exports );

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
    public static function delete_user_options( $key = null )
    {

        // sanity check ##
        if ( is_null ( $key ) || ! array_key_exists( $key, self::$q_report_exports ) ) { return false; }

        // clean it up ##
        $key = \sanitize_text_field( $key );

        // check it out ##
        #$this->pr( $key );

        // drop the array by it's key name from the class property ##
        unset( self::$q_report_exports[$key] );

        // update the saved data ##
        \update_user_meta( \get_current_user_id(), 'q_report_exports', self::$q_report_exports );

    }



}