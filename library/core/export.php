<?php

namespace q\eud\core;

use q\eud\core\core as core;
use q\eud\core\helper as helper;
// use q\eud\core\excel2003 as excel2003;
use XLSXWriter;

// load it up ##
\q\eud\core\export::run();

class export extends \q_export_user_data {

    public static function run()
    {

        if ( \is_admin() ) {

            // run export ##
            \add_action( 'admin_init', array( get_class(), 'render' ), 1000003 );

        }

    }


    /**
    * Attempt to generate the export file based on the passed arguements
    *
    * @since 0.1
    * @return Mixes
    **/
    public static function render()
    {

        // Check if the user clicked on the Save, Load, or Delete Settings buttons ##
        if (
            ! isset( $_POST['_wpnonce-q-eud-admin-page'] )
            || isset( $_POST['load_export'] )
            || isset( $_POST['save_export'] )
            || isset( $_POST['delete_export'] ) )
        {

            return false;

        }

        // Increase maximum execution time to prevent "Maximum execution time exceeded" error ##
        ini_set( 'max_execution_time', -1 );
        ini_set( 'memory_limit', -1 ); // bad idea? ##

        // check admin referer ##
        \check_admin_referer( 'q-eud-admin-page', '_wpnonce-q-eud-admin-page' );

        // build argument array ##
        $args = array(
            'fields'    => ( isset( $_POST['user_fields'] ) && '1' == $_POST['user_fields'] ) ? 
                            'all' : 
                            array( 'ID' ), // exclude standard wp_users fields from get_users query ##
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

                // test it ##
                helper::log( $args );

            }

        }

        // add custom args via filters ##
        $args = \apply_filters( 'q/eud/export/args', $args );

        #helper::log( $args );

        // pre_user query ##
        \add_action( 'pre_user_query', array( get_class(), 'pre_user_query' ) );
        $users = \get_users( $args );
        \remove_action( 'pre_user_query', array( get_class(), 'pre_user_query' ) );

        // test args ##
        #if ( self::$debug ) helper::log ( $users );

        // no users found, so chuck an error into the args array and exit the export ##
        if ( ! $users ) {

            \wp_redirect( \add_query_arg( 'error', 'empty', \wp_get_referer() ) );
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

        switch ( $export_method ) {

            case ( 'csv' ):

                // to csv ##
                header( 'Content-Description: File Transfer' );
                header( 'Content-Disposition: attachment; filename='.$filename.'.csv' );
                header( 'Content-Type: text/csv; charset=' . \get_option( 'blog_charset' ), true );

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

			/*
            case ( 'excel2003' ):

                // to xls ##
                header( 'Content-Description: File Transfer' );
                header("Content-Type: application/vnd.ms-excel");
                header("Content-Disposition: attachment; filename=$filename.xls");
                header("Pragma: no-cache");
                header("Expires: 0");

                // set a csv check flag
                $is_csv = false;

                // open xml
                $doc_begin  = excel2003::begin();

                //preformat
                $pre        = excel2003::pre();

                // how to seperate data ##
                $seperator  = excel2003::seperator();

                // line break ##
                $breaker    = excel2003::breaker();

                // close xml
                $doc_end    = excel2003::end();

			break;
			*/

            case ( 'excel2007' ):

                // to xlsx ##
                header( 'Content-Description: File Transfer' );
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition: attachment; filename=$filename.xlsx");
                header('Content-Transfer-Encoding: binary');
                //header('Content-Length: ' . filesize($file));
                //header('Cache-Control: must-revalidate');
                //header('Pragma: public');
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

                $writer = new XLSXWriter();
				
			break;

        }


        // check for selected usermeta fields ##
        $usermeta = isset( $_POST['usermeta'] ) ? $_POST['usermeta']: '';
        #helper::log( $usermeta );
        $usermeta_fields = array();

        // loop over each field and sanitize ## @todo - user array_map ##
        if ( $usermeta && is_array( $usermeta ) ) {
            foreach( $usermeta as $field ) {
                $usermeta_fields[] = \sanitize_text_field ( $field  );
            }
        }

        #helper::log( $usermeta_fields );
        #exit;

        // check for selected x profile fields ##
        $bp_fields = isset( $_POST['bp_fields'] ) ? $_POST['bp_fields'] : '';
        $bp_fields_passed = array();
        if ( $bp_fields && is_array( $bp_fields ) ) {

            foreach( $bp_fields as $field ) {

                // reverse tidy ##
                $field = str_replace( '__', ' ', \sanitize_text_field ( $field ) );

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
                $field = str_replace( '__', ' ', \sanitize_text_field ( $field ) );

                // add to array ##
                $bp_fields_update_passed[] = $field . " Update Date";

            }

        }

        // global wpdb object ##
        global $wpdb;

        // debug ##
        #helper::log( 'merging array' );

        // compile final fields list ##
        $fields = array_merge(
                core::get_user_fields() // standard wp_user fields ##
            ,	core::get_special_fields() // 'special' fields - which are controlled via dedicated checks ##
            ,	$usermeta_fields // wp_user_meta fields ##
            ,	$bp_fields_passed // selected buddypress fields ##
            ,	$bp_fields_update_passed // update date for buddypress fields ##
        );

        // test field array ##
        #helper::log( $fields );

        // build the document headers ##
        $headers = array();

        foreach ( $fields as $key => $field ) {

            #helper::log( 'Field: '. $field );

            // filter field name ##
            $field = \apply_filters( 'q/eud/export/field', $field );

            // grab fields to exclude from exports - filterable ##
            if ( in_array( $fields[$key], core::get_exclude_fields() ) ) {

                #helper::log( 'Dump Field: '. $fields[$key] );

                // ditch 'em ##
                unset( $fields[$key] );

            } else {

                if ( $is_csv ) {

                    $headers[] = '"' . $field . '"';

                } else {

                    $headers[] = $field;

                }

            }

        }

        // quick check ##
        #helper::log( $fields );
        #if ( self::$debug ) #helper::log( $bp_fields_passed );

        // no more buffering while spitting back the export data ##
        if( ob_get_level() > 0 ) ob_end_flush();

        // get the value in bytes allocated for Memory via php.ini ##
        // @link http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue
        $memory_limit = core::return_bytes( ini_get('memory_limit') ) * .75;

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


        if ($export_method !== "excel2007") {
            // open doc wrapper.. ##
            echo $doc_begin;

            // echo headers ##
            echo $pre . implode( $seperator, $headers ) . $breaker;

            #helper::log( $users );
        } else {

            $xlsx_header = array_flip($headers);

            foreach($xlsx_header as $k => $v) {
                $xlsx_header[$k] = "string";
            }

            $writer->writeSheetHeader('Sheet1', $xlsx_header);
        }

        // build row values for each user ##
        foreach ( $users as $user ) {

            #helper::log( $user );

            // check if we're hitting any Memory limits, if so flush them out ##
            // per http://wordpress.org/support/topic/how-to-exporting-a-lot-of-data-out-of-memory-issue?replies=2
            if ( memory_get_usage( true ) > $memory_limit ) {
                \wp_cache_flush();
            }

            // open up a new empty array ##
            $data = array();

            // BP loaded ? ##
            if (
                ! self::$bp_data_available
                && function_exists ( 'bp_is_active' )
                && \bp_is_active( 'xprofile' )
                && class_exists( 'BP_XProfile_ProfileData' )
                && method_exists( 'BP_XProfile_ProfileData', 'get_all_for_user' )
                && is_callable ( array( 'BP_XProfile_ProfileData', 'get_all_for_user' ) )
            ) {

                helper::log( 'XProfile Accessible' );
                self::$bp_data_available = true; // we only need to check for BP once ##

            }

            // grab all user data ##
            if (
                self::$bp_data_available
                && ! $bp_data = \BP_XProfile_ProfileData::get_all_for_user( $user->ID )
            ) {

                // null the data to be sure ##
                $bp_data = false;

                helper::log( 'XProfile returned no data ID#: '.$user->ID );

            }

            // single query method - get all user_meta data ##
            $get_user_meta = (array)\get_user_meta( $user->ID );
            #helper::log( $get_user_meta );

            // loop over each field ##
            foreach ( $fields as $field ) {

                // check if this is a BP field ##
                if ( 
                    isset( $bp_data ) 
                    && isset( $bp_data[$field] ) 
                    && in_array( $field, $bp_fields_passed ) 
                ){

                    // old way from single BP query ##
                    $value = $bp_data[$field];

                    if ( is_array( $value ) ) {

                        $value = \maybe_unserialize( $value['field_data'] ); // suggested by @grexican ##
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
                            $value =  implode( "::", $value );
                        }

                    }

                    // sanitize ##
                    #$value = $this->sanitize($value);

                // check if this is a BP field we want the updated date for ##
                }
                elseif ( in_array( $field, $bp_fields_update_passed ) )
                {

                    global $bp;

                    $real_field = str_replace(" Update Date", "", $field);
                    $field_id = \xprofile_get_field_id_from_name( $real_field );
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
                elseif ( isset( $_POST['roles'] ) && '1' == $_POST['roles'] && $field == 'roles' )
                {

					// empty array ##
					$user_roles = [];
					
					// get usermeta data
					$userdata = \get_userdata( $user->ID );
						
					// loop over roles, taking the name ##
					foreach( $userdata->roles as $role ) {

						$user_roles[] = \translate_user_role( $role );
						
					}

					// test ##
					// helper::log( $user_roles );

                     // empty value if no role found - or flat array of user roles ##
                    $value = ! empty( $user_roles ) ? implode( $user_roles, '|' ) : '';

                // include the user's BP group in the export ##
                }
                elseif ( isset( $_POST['groups'] ) && '1' == $_POST['groups'] && $field == 'groups' )
                {

                    if ( function_exists( 'groups_get_user_groups' ) ) {

                        // check if user is a member of any groups ##
                        $group_ids = \groups_get_user_groups( $user->ID );

                        #$this->pr( $group_ids );
                        #wp_die( pr( 'loaded group data.' ));

                        if ( ! $group_ids || $group_ids == '' ) {

                            $value = '';

                        } else {

                            // new empty array ##
                            $groups = array();

                            // loop over all groups ##
                            foreach( $group_ids["groups"] as $group_id ) {

                                $groups[] = \groups_get_group( array( 'group_id' => $group_id )) -> name . ( end( $group_ids["groups"] ) == $group_id ? '' : '' );

                            }

                            // implode it ##
                            $value = implode( $groups, '|' );

                        }

                    } else {

                        $value = '';

                    }

                }
                elseif ( $field == 'bp_latest_update' || $field == 'last_activity' )
                {

                    // https://bpdevel.wordpress.com/2014/02/21/user-last_activity-data-and-buddypress-2-0/ ##
                    $value = \bp_get_user_last_activity( $user->ID );

                // user or usermeta field ##
                }
                else
                {

                    // the user_meta key isset ##
                    if ( isset( $get_user_meta[$field] ) ) {

                        // take from the bulk get_user_meta call - this returns an array in all cases, so we take the first key ##
                        $value = $get_user_meta[$field][0];

                    // standard WP_User value ##
                    } else {

                        // use the magically assigned value from WP_Users
                        $value = isset( $user->{$field} ) ? $user->{$field} : null ;

                    }


                    // the $value might be serialized ##
                    $value = core::unserialize( $value );

                    // the value is an array ##
                    if ( is_array ( $value ) ) {

                        // recursive implode it ##
                        $value = core::recursive_implode( $value );

                    }

                    // sanitize ##
                    #$value = $this->sanitize($value);

                }

                // filter $value ##
                $value = \apply_filters( 'q/eud/export/value', $value, $field );

                // sanitize ##
                $value = core::sanitize( $value );

                // wrap values in quotes and add to array ##
                if ( $is_csv ) {

                    $data[] = '"' . str_replace( '"', '""', core::format_value( $value ) ) . '"';

                // just add to array ##
                } else {

                    $data[] = core::format_value( $value );
                }

            }

            if ($export_method !== "excel2007") {
                // echo row data ##
                echo $pre . implode( $seperator, $data ) . $breaker;
            } else {
                $writer->writeSheetRow('Sheet1', $data);
            }

        }

        if ($export_method !== "excel2007") {
            // close doc wrapper..
            echo $doc_end;
        } else {
            //$writer->writeSheet($rows,'Sheet1', $header); //or write the whole sheet in 1 call
            //$writer->writeToFile('xlsx-simple.xlsx');
            //$writer->writeToStdOut();
            echo $writer->writeToString();
        }

        // stop PHP, so file can export correctly ##
        exit;

    }


    
    
    /**
    * Pre User Query
    *
    * @since        2.0.0
    */
    public static function pre_user_query( $user_search = null )
    {

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

        // search by last update time of BP extended fields ##
        if (
            class_exists( 'BP_Xprofile_Field' )
            && ( isset ($_POST['updated_since_date'] ) && $_POST['updated_since_date'] != '' )
            && (isset ($_POST['bp_field_updated_since'] ) && $_POST['bp_field_updated_since'] != '' )
        ) {

            $last_updated_date = new \DateTime( \sanitize_text_field ( $_POST['updated_since_date'] ) . ' 00:00:00' );
            self::$updated_since_date = $last_updated_date->format( 'Y-m-d H:i:s' );
            self::$field_updated_since = \sanitize_text_field ( $_POST['bp_field_updated_since'] );
            $field_updated_since_id = \BP_Xprofile_Field::get_id_from_name( self::$field_updated_since );
            $user_search->query_from .=  " JOIN `wp_bp_xprofile_data` XP ON XP.user_id = wp_users.ID ";
            $where .= $wpdb->prepare( " AND XP.field_id = %s AND XP.last_updated >= %s", $field_updated_since_id, self::$updated_since_date );

        }

        if ( ! empty( $where ) ) {

            $user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1 $where", $user_search->query_where );

        }

        #wp_die( self::$pr( $user_search ) );
        return $user_search;

    }



}
