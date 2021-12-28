<?php

namespace q\eud\core;

// import classes ##
use q\eud;
use q\eud\plugin as plugin;
use q\eud\core\helper as h;

class user {

	private $plugin;

	public function __construct( \q\eud\plugin $plugin ){

		$this->plugin = $plugin; 

	}

    /**
     * Load up saved exports for this user
     * Set to public as hooked into action
     *
     * @since       0.9.6
     * @return      Array of saved exports
     */
    public function load(){

		// convert outdated stored meta from q_report to q_eud_exports ##
		if(
			\get_user_meta( \get_current_user_id(), 'q_report', true )
			&& ! \get_user_meta( \get_current_user_id(), 'q_eud_exports' )
		){

			// get old data ##
			$old_data = \get_user_meta( \get_current_user_id(), 'q_report', true );

			// add to user meta, as they do not have any stored values ##
			\add_user_meta( \get_current_user_id(), 'q_eud_exports', $old_data );

		}

		// get array ##
		$array = 
			\get_user_meta( \get_current_user_id(), 'q_eud_exports' ) ?
			\get_user_meta( \get_current_user_id(), 'q_eud_exports', true ) :
			[] ;

		// set prop ##
		$this->plugin->set( '_q_eud_exports', $array );

		// return bool ##
        return true;
			
	}

    /**
     * Get list of saved exports for this user
     *
     * @since       0.9.4
     * @return      Array of saved exports
     */
    public function get_user_options():array
	{

		// get props ##
		$_q_eud_exports = $this->plugin->get( '_q_eud_exports' );

        // get the stored options - filter empty array items ##
        $_q_eud_exports = array_filter( $_q_eud_exports );

        // quick check if the array is empty ##
        if ( empty ( $_q_eud_exports ) ) {

            return [];

        }

        // start with an empty array ##
        $exports = [];

        // loop over each saved export and grab each key ##
        foreach ( $_q_eud_exports as $key => $value ) {

            $exports[] = $key;

        }

        // kick back array ##
        return $exports;

    }

    /**
     * Check for and load stored user options
     *
     * @since       	0.9.3
	 * @param			string
     * @return      	void
     */
    public function get_user_options_by_export( string $export = null ):void
	{

        // sanity check ##
		if ( is_null ( $export ) ) { 
			
			return; 
		
		}
		
		// get props ##
		$_q_eud_exports = $this->plugin->get( '_q_eud_exports' );

        if ( isset( $_q_eud_exports[$export] ) ) {

            $_usermeta_saved_fields = $_q_eud_exports[$export]['usermeta_saved_fields'];
            $_updated_since_date = $_q_eud_exports[$export]['updated_since_date'] ?? null ;
            $_role = $_q_eud_exports[$export]['role'];
            $_roles = $_q_eud_exports[$export]['roles'];
            $_groups = $_q_eud_exports[$export]['groups'];
            $_user_fields = $_q_eud_exports[$export]['user_fields'] ?? null ;
            $_start_date = $_q_eud_exports[$export]['start_date'];
            $_end_date = $_q_eud_exports[$export]['end_date'];
            $_limit_offset = $_q_eud_exports[$export]['limit_offset'];
            $_limit_total = $_q_eud_exports[$export]['limit_total'];
            $_format = $_q_eud_exports[$export]['format'];

        } else {

            $_usermeta_saved_fields = [];
            $_updated_since_date = '';
            $_role = '';
            $_user_fields = '1';
            $_roles = '1';
            $_groups = '1';
            $_start_date = '';
            $_end_date = '';
            $_limit_offset = '';
            $_limit_total = '';
            $_format = '';

		}
		
		// set props ##
		$this->plugin->set( '_usermeta_saved_fields', $_usermeta_saved_fields );
		$this->plugin->set( '_updated_since_date', $_updated_since_date );
		$this->plugin->set( '_role', $_role );
		$this->plugin->set( '_user_fields', $_user_fields );
		$this->plugin->set( '_roles', $_roles );
		$this->plugin->set( '_groups', $_groups );
		$this->plugin->set( '_start_date', $_start_date );
		$this->plugin->set( '_end_date', $_end_date );
		$this->plugin->set( '_limit_offset', $_limit_offset );
		$this->plugin->set( '_limit_total', $_limit_total );
		$this->plugin->set( '_format', $_format );

    }

    /**
     * Method to store user options
     *
     * @param       string      $save_export        Export Key name
     * @param       array       $save_options       Array of export options to save
     * @since       0.9.3
     * @return      void
     */
    public function set_user_options( $key = null, $options = null ):void
	{

        // sanity check ##
        if ( is_null ( $key ) || is_null ( $options ) ) {

            #h::log( 'missing save values' );
            return;

        }

		// get prop ##
		$_q_eud_exports = $this->plugin->get( '_q_eud_exports' );

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

            // assign the sanitized array of values to the class property $q_eud_exports as a new array with key $key ##
			$_q_eud_exports[$key] = $options;
			
			// set prop ##
			$this->plugin->set( '_q_eud_exports', $_q_eud_exports );

            // update stored user_meta values, if previous key found ##
            if ( false !== \get_user_meta( \get_current_user_id(), 'q_eud_exports' ) ) {

                \update_user_meta( \get_current_user_id(), 'q_eud_exports', $_q_eud_exports );

            // create new user meta key ##
            } else {

                \add_user_meta( \get_current_user_id(), 'q_eud_exports', $_q_eud_exports );

            }

        }

    }

    /**
     * delete user options
     *
     * @param       $key        String      Key name to drop from property
     * @since       0.9.3
     * @return      void
     */
    public function delete_user_options( string $key = null ):bool
	{

		// get prop ##
		$_q_eud_exports = $this->plugin->get( '_q_eud_exports' );

        // sanity check ##
        if ( is_null ( $key ) || ! array_key_exists( $key, $_q_eud_exports ) ) { return false; }

        // clean it up ##
        $key = \sanitize_text_field( $key );

        // drop the array by it's key name from the class property ##
        unset( $_q_eud_exports[$key] );

        // update the saved data ##
		\update_user_meta( \get_current_user_id(), 'q_eud_exports', $_q_eud_exports );
		
		// set prop ##
		$this->plugin->set( '_q_eud_exports', $_q_eud_exports );

		// done ##
		return true;

    }

}
