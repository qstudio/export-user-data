<?php

// use export_user_data\core;
use export_user_data\core;

/** 
 * export_user_data API 
 *
 * @todo 
 */
if ( ! function_exists( 'export_user_data' ) ) {

	function export_user_data(){

		// sanity ##
		if(
			! class_exists( 'q\eud\plugin' )
		){

			error_log( 'e:>Export User Data is not available to '.__FUNCTION__ );

			return false;

		}

		// cache ##
		$export_user_data = \q\eud\plugin::get_instance();

		// sanity - make sure export_user_data instance returned ##
		if( 
			is_null( $export_user_data )
			|| ! ( $export_user_data instanceof \q\eud\plugin ) 
		) {

			// get stored export_user_data instance from filter ##
			$export_user_data = \apply_filters( 'q/eud/instance', NULL );

			// sanity - make sure export_user_data instance returned ##
			if( 
				is_null( $export_user_data )
				|| ! ( $export_user_data instanceof \q\eud\plugin ) 
			) {

				error_log( 'Error in object instance returned to '.__FUNCTION__ );

				return false;

			}

		}

		// return export_user_data instance ## 
		return $export_user_data;

	}

}
