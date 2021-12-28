<?php

// namespace ##
namespace q\eud\core;

/**
 * helper Class
 * @package   q_eud\core
 */
class helper {

    /**
     * Write to WP Error Log
     *
     * @since       1.5.0
     * @return      void
     */
    public static function log( $log ){

        if ( true === \WP_DEBUG ) {

            $trace = debug_backtrace();
            $caller = $trace[0];

            $suffix = sprintf(
                __( ' - %s%s() %s:%d', 'Q' )
                ,   isset($caller['class']) ? $caller['class'].'::' : ''
                ,   $caller['function']
                ,   isset( $caller['file'] ) ? $caller['file'] : 'n'
                ,   isset( $caller['line'] ) ? $caller['line'] : 'x'
            );

            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ).$suffix );
            } else {
                error_log( $log.$suffix );
            }

        }

    }

	
    /**
    * Return Byte count of $val
    *
    * @link        http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue?replies=2
    * @since       0.9.6
    */
    public static function return_bytes( $val ){

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
    public static function recursive_implode( $array, $return = null, $glue = '|' ){

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
    public static function unserialize( $value = null ){

        // the $value is serialized ##
        if ( \is_serialized( $value ) ) {

            // unserliaze to new variable ##
            $unserialized = @unserialize( $value );

            // test if unserliazing produced errors ##
            if ( 
				$unserialized !== false 
				&& $value !== 'b:0;' 
			){

				// self::log( $unserialized );

                $value = $unserialized;

            } else {

                // failed to unserialize - data potentially corrupted in db ##
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
    public static function format_value( string $value = null )
	{

        // sanity check ##
        if ( is_null( $value ) ) {

            return false;

        }

		if( has_filter( 'q/eud/export/value' ) ){

			$value = \apply_filters( 'q/eud/export/value', $value );

		} else {

			$value = htmlentities( $value, ENT_COMPAT, 'UTF-8' );

		}

        // kick it back via a filter to allow custom formatting ##
        return $value;

    }

	/**
	 * Encode array values to JSON string
	 * 
	 * @since 2.2.1
	 * @param	mixed
	 * @return	mixed	string|null
	*/
	public static function json_encode( array $value ):?string
	{

		if ( ! is_array( $value ) ){

			return $value;

		}

		// cleanup array ##
		$value = array_values( $value );

		// encode and escape ##
		$value = json_encode( $value, JSON_FORCE_OBJECT );

		// kick back JSON encoded string ##
		return $value;

	}

    /**
    * Quote array elements and separate with commas
    *
    * @since       0.9.6
    * @return      String
    */
    public static function quote_array( $array ){

        $prefix = ''; // starts empty ##
        $string = '';

        if ( is_array( $array ) ) {
        
            foreach( $array as $element ) {
        
                $string .= $prefix . "'" . \esc_attr( $element ) . "'";
                $prefix = ','; // prefix all remaining items with a comma ##
        
            }
        
        }

        // kick back string to function caller ##
        return( $string );

    }

    /**
    * Export Date Options
    *
    * @since       0.9.6
    * @global      type    $wpdb
	* @return      Array of objects
	* @todo			Remove max date, as this makes little sense for exports not based on user reg dates.. ??	
    */
    public static function get_user_registered_dates(){

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

    /**
    * Sanitize data
    *
    * @since 1.2.8
    * @return string
    */
    public static function sanitize_value( $value ){

		if( has_filter( 'q/eud/export/value' ) ){

			$value = \apply_filters( 'q/eud/export/value', $value );

		} else {

			$value = htmlentities( $value, ENT_COMPAT|ENT_COMPAT, 'UTF-8' );
			// $value = \esc_attr( $value );

		}

        // return value ##
        return $value;

    }

    public static function sanitize( $value ) {

		if ( is_array( $value ) ) {

            array_walk_recursive( $value, [ __CLASS__, 'sanitize_value' ] ); 

        } else {

            self::sanitize_value( $value );

        }

        return $value;

    }

	/**
	 * Check if a string is JSON
	 * 
	 * @since 2.0.2
	*/
	public static function is_json( $string )
	{
	
		json_decode( $string );

		if ( json_last_error() === JSON_ERROR_NONE ){

			return true;

		}

		return false;
	
	}

    /**
    * Get allowed tags for wp_kses
    *
    * @since  1.2.8
    * @return Array
    */
    public static function get_allowed_tags(){

        $allowed_tags = [
            'a' => [
                'href' => [],
                'title' => []
			],
            'br' => [],
            'em' => [],
            'strong' => [],
		];

        // kick back via filter ##
        return \apply_filters( 'q/eud/export/allowed_tags', $allowed_tags );

    }

}
