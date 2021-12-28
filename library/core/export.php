<?php

namespace q\eud\core;

// import classes ##
use q\eud;
use q\eud\plugin as plugin;
use q\eud\core\helper as h;
use q\eud\core\method;
use XLSXWriter;

class export {

	private $plugin;

	function __construct( \q\eud\plugin $plugin ){

		$this->plugin = $plugin; 

	}

    /**
     * Attempt to generate the export file based on the passed arguements
     *
     * @since 	0.1
     * @return 	mixed
     */
    public function render(){

		// h::log( $_POST );

        // Check if the user clicked on the Save, Load, or Delete Settings buttons ##
        if (
            ! isset( $_POST['_wpnonce-q-eud-admin-page'] )
		){

            return;

        }

		// Check if the user clicked on the Save, Load, or Delete Settings buttons ##
        if (
            isset( $_POST['load_export'] )
            || isset( $_POST['save_export'] )
            || isset( $_POST['delete_export'] ) )
        {

            return;

        }

        // check admin referer ##
        \check_admin_referer( 'q-eud-admin-page', '_wpnonce-q-eud-admin-page' );

		// Increase maximum execution time to prevent "Maximum execution time exceeded" error ##
        ini_set( 'max_execution_time', -1 );
        ini_set( 'memory_limit', -1 );

        // build argument array ##
        $args = array(
            'fields'    => ( isset( $_POST['user_fields'] ) && '1' == $_POST['user_fields'] ) ? 
                            'all' : 
                            [ 'ID' ], // exclude standard wp_users fields from get_users query ##
            'role'      => \sanitize_text_field( $_POST['role'] )
        );

        // is there a range limit in place for the export ? ##
        if ( isset( $_POST['limit_total'] ) && '' != $_POST['limit_total'] ) {

            // let's just make sure they are integer values ##
            $limit_offset = isset( $_POST['limit_offset'] ) ? (int)$_POST['limit_offset'] : 0 ;
            $limit_total = (int)$_POST['limit_total'];

            if ( is_int( $limit_offset ) && is_int( $limit_total ) ) { // confirm we have integer values ##

                $args['offset'] = $limit_offset;
                $args['number'] = $limit_total; // number - Limit the total number of users returned ##

            }

        }

        // add custom args via filters ##
        $args = \apply_filters( 'q/eud/export/args', $args );

        // pre_user query ##
        \add_action( 'pre_user_query', [ $this, 'pre_user_query' ] );

		// run WP_User_Query ##
        $users = \get_users( $args );

		// remove pre_user_query again ##
        \remove_action( 'pre_user_query', [ $this, 'pre_user_query' ] );

        // no users found, so chuck an error into the args array and exit the export ##
        if ( ! $users ) {

            \wp_redirect( \add_query_arg( 'qeud_error', 'empty', \wp_get_referer() ) );

            exit;

        }

        // get sitename and clean it up ##
        $sitename = \sanitize_key( \get_bloginfo( 'name' ) );
        if ( ! empty( $sitename ) ) {
            $sitename .= '.';
        }

        // export method ? ##
        $export_method = 'excel2007'; // default to Excel export ##
        if ( isset( $_POST['format'] ) && $_POST['format'] != '' ) {

            $export_method = \sanitize_text_field( $_POST['format'] );

        }

        // set export filename structure ##
        $filename = $sitename . 'report.' . date( 'Y-m-d-H-i-s' );

		// switch of export methods - csv / excel ##
        switch ( $export_method ) {

			// CSV ##
            case ( 'csv' ):

                // to csv ##
                header( 'Content-Description: File Transfer' );
                header( 'Content-Disposition: attachment; filename='.\esc_attr( $filename ).'.csv' );
                header( 'Content-Type: text/csv; charset=' . \esc_attr( \get_option( 'blog_charset' ) ), true );

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

			// Excel ##
            case ( 'excel2007' ):

                // to xlsx ##
                header( 'Content-Description: File Transfer' );
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition: attachment; filename=".\esc_attr( $filename ).".xlsx");
                header('Content-Transfer-Encoding: binary');
                header("Pragma: no-cache");
                header("Expires: 0");

                // set a csv check flag
                $is_csv = false;

                // open xml
                $doc_begin  = "";
                $pre        = "";
                $seperator  = "";
                $breaker    = "";
                $doc_end    = "";

                $writer = new \XLSXWriter();
				
			break;

        }

        // check for selected usermeta fields ##
        $usermeta_fields = 
			isset( $_POST['usermeta'] ) && is_array( $_POST['usermeta'] ) ? 
			array_map( 'sanitize_text_field', $_POST['usermeta'] ) : 
			[];

        // global wpdb object ##
        global $wpdb;

        // compile final fields list ##
        $fields = array_merge(
				get::user_fields() // standard wp_user fields ##
            ,	get::special_fields() // 'special' fields - which are controlled via dedicated checks ##
            ,	$usermeta_fields // wp_user_meta fields ##
        );

        // test field array ##
        #h::log( $fields );

        // build the document headers ##
        $headers = [];

        foreach ( $fields as $key => $field ) {

            #h::log( 'Field: '. $field );

            // filter field name ##
            $field = \apply_filters( 'q/eud/export/field', $field );

            // grab fields to exclude from exports - filterable ##
            if ( in_array( $fields[$key], get::exclude_fields() ) ) {

                // ditch 'em ##
                unset( $fields[$key] );

				continue;

            } else {

				$headers[] = $field;

            }

        }

        // quick check ##
        #h::log( $fields );

        // no more buffering while spitting back the export data ##
        if( ob_get_level() > 0 ) ob_end_flush();

        // get the value in bytes allocated for Memory via php.ini ##
        // @link http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue
        $memory_limit = h::return_bytes( ini_get('memory_limit') ) * .75;

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

		// escape each header value ##
		$headers = array_map( function( $header ){
			return esc_attr( $header );
		}, $headers );

		// h::log( $headers );

        if ( 
			$is_csv
		){
			
            // open doc wrapper.. ##
            \esc_html_e( $doc_begin );

			// get header string ##
			$headers_string = $pre.implode( $seperator, $headers ).$breaker;

            // echo headers ##
            \esc_html_e( $headers_string );

        } else {

            $xlsx_header = array_flip( $headers );

            foreach( $xlsx_header as $k => $v ) {
                $xlsx_header[$k] = "string";
            }

			$writer->writeSheetHeader( 'Sheet1', $xlsx_header );
			
        }

        // build row values for each user ##
        foreach ( $users as $user ) {

            #h::log( $user );

            // check if we're hitting any Memory limits, if so flush them out ##
            // per http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue?replies=2
            if ( memory_get_usage( true ) > $memory_limit ) {
                \wp_cache_flush();
            }

            // open up a new empty array ##
            $data = [];

            // single query method - get all user_meta data ##
            $get_user_meta = (array)\get_user_meta( $user->ID );

            // loop over each field ##
            foreach ( $fields as $field ) {

				if ( isset( $_POST['roles'] ) && '1' == $_POST['roles'] && $field == 'roles' ) {

					// empty array ##
					$user_roles = [];
					
					// get usermeta data
					$userdata = \get_userdata( $user->ID );
						
					// loop over roles, taking the name ##
					foreach( $userdata->roles as $role ) {

						$user_roles[] = \translate_user_role( $role );
						
					}

					// test ##
					// h::log( $user_roles );

					// empty value if no role found - or flat array of user roles ##
                    $value = 
						! empty( $user_roles ) ? 
						h::json_encode( $user_roles ) /*implode( '|', $user_roles )*/ : 
						'';

                // user or usermeta field ##
                } else {

                    // the user_meta key isset ##
                    if ( 
						isset( $get_user_meta[$field] ) 
						&& is_array( $get_user_meta[$field] )
					){

                        // take from the bulk get_user_meta call - this returns an array in all cases, so we take the first key ##
                        $value = $get_user_meta[$field][0];

                    // standard WP_User value ##
                    } else {

                        // use the magically assigned value from WP_Users
                        $value = isset( $user->{$field} ) ? $user->{$field} : null ;

                    }

                }

				// ---------- cleanup and format the value, before exporting ##

				// the $value might be serialized, so try to unserialize ##
				$value = h::unserialize( $value );

				// the value is an array ##
				if ( 
					is_array ( $value ) 
					|| is_object ( $value ) 
				){

					// json_encode value to string  ##
					$value = h::json_encode( $value );

				}

				// apply generic filter to value ##
				if( has_filter( 'q/eud/export/value' ) ){

					$value = \apply_filters( 'q/eud/export/value', $value );

				}

				// add value to new key in $data array ##
				$data[] = $value;

            }

			// escape array values ##
			$data = array_map(function($x){
				return esc_attr($x);
			}, $data);

			// h::log( $data );

            if ( $is_csv ) {

				// get row string ##
				$row_string = $pre.implode( $seperator, $data ).$breaker;

				// echo headers ##
				\esc_html_e( $row_string );
				
            } else {

				// each value in the $data array has already been escaped via esc_attr ##
				$writer->writeSheetRow( 'Sheet1', $data );
				
            }

        }

        if ( $is_csv ) {

            // close doc wrapper..
			\esc_html_e( $doc_end );

			// stop PHP, so file can export correctly ##
			exit;
			
        } else {

			// xss: all column headers and data values have been escaped previously ##
			// echo $writer->writeToString();
			$writer->writeToStdOut();

			// stop PHP, so file can export correctly ##
			exit(0);
			
        }

    }
    
    /**
    * Pre User Query
    *
    * @since        2.0.0
    */
    public function pre_user_query( object $user_search = null ):object
	{

		// import WPDB object ##
        global $wpdb;

        $where = '';

        if ( ! empty( $_POST['start_date'] ) ) {

            $date = new \DateTime( \sanitize_text_field ( $_POST['start_date'] ). ' 00:00:00' );
            $date_formatted = $date->format( 'Y-m-d H:i:s' );

            $where .= $wpdb->prepare( " AND $wpdb->users.user_registered >= %s", $date_formatted );

        }
        if ( ! empty( $_POST['end_date'] ) ) {

            $date = new \DateTime( \sanitize_text_field ( $_POST['end_date'] ). ' 00:00:00' );
            $date_formatted = $date->format( 'Y-m-d H:i:s' );

            $where .= $wpdb->prepare( " AND $wpdb->users.user_registered < %s", $date_formatted );

        }

        if ( ! empty( $where ) ) {

            $user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1 $where", $user_search->query_where );

        }

        #h::log( $user_search ) );
        return $user_search;

    }

}
