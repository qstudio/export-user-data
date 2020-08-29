<?php

namespace q\eud\admin;

use q\eud\core\core as core;
use q\eud\core\helper as helper;
use q\eud\core\user as user;
use q\eud\core\buddypress as buddypress;
use q\eud\api\admin as api_admin;

// load it up ##
\q\eud\admin\admin::run();

class admin extends \q_export_user_data {

    public static function run()
    {

        #global $pagenow;

        if ( \is_admin() ) {

            // add export menu inside admin ##
            \add_action( 'admin_menu', array( get_class(), 'add_menu' ) );

            // UI style and functionality ##
            \add_action( 'admin_enqueue_scripts', array( get_class(), 'admin_enqueue_scripts' ), 1 );
            \add_action( 'admin_footer', array( get_class(), 'jquery' ), 100000 );
            \add_action( 'admin_footer', array( get_class(), 'css' ), 100000 );

        }            

    }



    /**
    * Add administration menus
    *
    * @since 0.1
    **/
    public static function add_menu()
    {

        add_users_page ( 
            __( 'Export User Data', 'q-export-user-data' ), 
            __( 'Export User Data', 'q-export-user-data' ), 
            \apply_filters( 'q/eud/admin_capability', 'list_users' ), 
            'q-export-user-data', 
            array( 
                get_class(), 
                'admin_page' 
            ) 
        );

    }


    /**
    * Content of the admin page
    *
    * @since    0.1
    */
    public static function admin_page()
    {

        // quick security check ##
        if ( ! \current_user_can( \apply_filters( 'q/eud/admin_capability', 'list_users' ) ) ) {

            \wp_die( __( 'You do not have sufficient permissions to access this page.', 'q-export-user-data' ) );

        }

        // Save settings button was pressed ##
        if (
            isset( $_POST['save_export'] )
            && \check_admin_referer( 'q-eud-admin-page', '_wpnonce-q-eud-admin-page' )
        ) {

            // start with an empty variable ##
            $save_export = "";

            if ( ! empty( $_POST['save_new_export_name'] ) ) {

                // assign value ##
                $save_export = $_POST['save_new_export_name'];

            } elseif ( ! empty( $_POST['export_name'] ) ) {

                $save_export = $_POST['export_name'];

            }

            // clean up $save_export ##
            $save_export = \sanitize_text_field( $save_export );

            // Build array of $options to save and save them ##
            if ( isset( $save_export ) ) {

				// prepare all array values ##
				$usermeta = 
					isset( $_POST['usermeta'] ) ? 
					array_map( 'sanitize_text_field', 
					$_POST['usermeta'] ) : 
					'';
                $bp_fields = 
                    isset( $_POST['bp_fields'] ) ? 
                    array_map( 'sanitize_text_field', $_POST['bp_fields'] ) : 
                    '' ;
                $bp_fields_update = 
                    isset( $_POST['bp_fields_update_time'] ) ? 
                    array_map( 'sanitize_text_field', $_POST['bp_fields_update_time'] ) : 
                    '' ;
                $format = 
                    isset( $_POST['format'] ) ? 
                    \sanitize_text_field( $_POST['format'] ) :
                    '' ;
                $role = 
                    isset( $_POST['role'] ) ? 
                    \sanitize_text_field( $_POST['role'] ) :
                    '' ;
                $roles = 
                    isset( $_POST['roles'] ) ? 
                    \sanitize_text_field( $_POST['roles'] ) :
                    '' ;
                $user_fields = 
                    isset( $_POST['user_fields'] ) ? 
                    array_map( 'sanitize_text_field', $_POST['user_fields'] ) : 
                    '0' ;
                $groups = 
                    isset( $_POST['groups'] ) ? 
                    \sanitize_text_field( $_POST['groups'] ) :
                    '0' ;
                $start_date = 
                    isset( $_POST['start_date'] ) ? 
                    \sanitize_text_field( $_POST['start_date'] ) : 
                    '' ;
                $end_date = 
                    isset( $_POST['end_date'] ) ? 
                    \sanitize_text_field( $_POST['end_date'] ) : 
                    '' ;
                $limit_offset = 
                    isset( $_POST['limit_offset'] ) ? 
                    \sanitize_text_field( $_POST['limit_offset'] ) : 
                    '' ;
                $limit_total = 
                    isset( $_POST['limit_total'] ) ? 
                    \sanitize_text_field( $_POST['limit_total'] ) : 
                    '' ;
                $updated_since_date = 
                    isset( $_POST['updated_since_date'] ) ? 
                    \sanitize_text_field( $_POST['updated_since_date'] ) : 
                    '' ;
                $field_updated_since = 
                    isset( $_POST['bp_field_updated_since'] ) ? 
                    array_map( 'sanitize_text_field', $_POST['bp_field_updated_since'] ) :
                    '';

                // assign all values to an array ##
                $save_array = array (
                    'usermeta_saved_fields' => $usermeta,
                    'bp_fields_saved_fields' => $bp_fields,
                    'bp_fields_update_time_saved_fields' => $bp_fields_update,
                    'role' => $role,
                    'roles' => $roles,
                    'user_fields' => $user_fields,
                    'groups' => $groups,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'limit_offset' => $limit_offset,
                    'limit_total' => $limit_total,
                    'updated_since_date' => $updated_since_date,
                    'field_updated_since' => $field_updated_since,
                    'format' => $format
                );

                // store the options, for next load ##
                user::set_user_options( $save_export, $save_array );

                // Display the settings the user just saved instead of blanking the form ##
                $_POST['load_export'] = 'Load Settings';
                $_POST['export_name'] = $save_export;

            }

        }

        // Load settings button was pressed ( or option saved and $_POST variables hijacked )##
        if (
            isset( $_POST['load_export'] )
            && isset( $_POST['export_name'] )
            && \check_admin_referer( 'q-eud-admin-page', '_wpnonce-q-eud-admin-page' )
        ) {

            user::get_user_options_by_export( \sanitize_text_field( $_POST['export_name'] ) );

        }

        // Delete settings button was pressed ##
        if (
            isset( $_POST['delete_export'] )
            && isset( $_POST['export_name'] )
            && \check_admin_referer( 'q-eud-admin-page', '_wpnonce-q-eud-admin-page' )
        ) {

            user::delete_user_options( \sanitize_text_field( $_POST['export_name'] ) );

        }

?>
<div class="wrap">
    <h2><?php \_e( 'Export User Data', 'q-export-user-data' ); ?></h2>
<?php

    // nothing happening? ##
    if ( isset( $_GET['error'] ) ) {
        echo '<div class="updated"><p><strong>' . \__( 'No users found.', 'q-export-user-data' ) . '</strong></p></div>';
    }

?>
    <form method="post" action="" enctype="multipart/form-data">
        <?php \wp_nonce_field( 'q-eud-admin-page', '_wpnonce-q-eud-admin-page' ); ?>
        <table class="form-table">
<?php

            // allow admin to select user meta fields to export ##
            global $wpdb;
    
            // filterable SQL ##
            $meta_keys = \apply_filters( 
                'q/eud/admin/sql', 
                $wpdb->get_results( "SELECT distinct(meta_key) FROM $wpdb->usermeta" ) 
            );

            // filterable sort ##
            \apply_filters( 
                'q/eud/admin/sort', 
                asort( $meta_keys )
            );

            // get meta_key value from object ##
            $meta_keys = \wp_list_pluck( $meta_keys, 'meta_key' );

            // allow array to be filtered ##
            $meta_keys_common = \apply_filters( 'q/eud/admin/meta_keys_common', [] );

            // test array ##
            #helper::log( $meta_keys );
            #helper::log( $meta_keys_common );

            // check if we got anything ? ##
            if ( $meta_keys ) {

?>
            <tr valign="top">
                <th scope="row">
                    <label for="q_eud_usermeta"><?php \_e( 'User Meta Fields', 'q-export-user-data' ); ?></label>
                    <p class="filter" style="margin: 10px 0 0;">
                        <?php \_e('Filter', 'q-export-user-data'); ?>: <a href="#" class="usermeta-all"><?php \_e('All', 'q-export-user-data'); ?></a> | <a href="#" class="usermeta-common"><?php \_e('Common', 'q-export-user-data'); ?></a>
                    </p>
                    <p class="filter" style="margin: 10px 0 0;">
                        <?php \_e('Select', 'q-export-user-data'); ?>: <a href="#" class="select-all"><?php \_e('All', 'q-export-user-data'); ?></a> | <a href="#" class="select-none"><?php \_e('None', 'q-export-user-data'); ?></a>
                    </p>
                </th>
                <td>
                    <select multiple="multiple" id="usermeta" name="usermeta[]">
<?php

                        foreach ( $meta_keys as $key ) {    

                            // filter key displayed ##
                            $display_key = \apply_filters( 'q/eud/admin/display_key', $key );

                            // class ##
                            $usermeta_class = 'normal';

                            foreach ( $meta_keys_common as $drop ) {

                                #helper::log( 'Checking: '.$drop );

                                if ( strpos( $key, $drop ) !== false ) {

                                    #helper::log( 'Checking: '.$key );

                                    // https://wordpress.org/support/topic/bugfix-numbers-in-export-headers?replies=1
                                    // removed $key = assignment, as not required ##
                                    if ( ( array_search( $key, $meta_keys ) ) !== false ) {

                                        #helper::log( 'Found: '.$key );

                                        $usermeta_class = 'common';

                                    }

                                }

                            }

                            // print key ##
                            echo "<option value='".\esc_attr( $key )."' title='".\esc_attr( $key )."' class='".$usermeta_class."'>$display_key</option>";

                        }
?>
                    </select>
                    <p class="description"><?php
                        printf(
                            \__( 'Select the user meta keys to export, use the filters to simplify the list.', 'q-export-user-data' )
                        );
                    ?></p>
                </td>
            </tr>
<?php

            } // meta_keys found ##

?>
<?php

        // buddypress x profile data ##
        if ( $bp_fields = buddypress::get_fields() ) {
            
?>
            <tr valign="top">
                <th scope="row">
                    <label for="q_eud_xprofile"><?php \_e( 'BP xProfile Fields', 'q-export-user-data' ); ?></label>
                    <p class="filter" style="margin: 10px 0 0;">
                        <?php \_e('Select', 'q-export-user-data'); ?>: <a href="#" class="select-all"><?php \_e('All', 'q-export-user-data'); ?></a> | <a href="#" class="select-none"><?php \_e('None', 'q-export-user-data'); ?></a>
                    </p>
                </th>
                <td>
                    <select multiple="multiple" id="bp_fields" name="bp_fields[]">
<?php

                    foreach ( $bp_fields as $key ) {

                        // print key ##
                        echo "<option value='".\esc_attr( $key )."' title='".\esc_attr( $key )."'>$key</option>";

                    }

?>
                    </select>
                    <p class="description"><?php
                        printf(
                            \__( 'Select the BuddyPress XProfile keys to export', 'q-export-user-data' )
                        );
                    ?></p>
                </td>
            </tr>
<?php

            // allow export of update times ##

?>
            <tr valign="top" class="toggleable">
                <th scope="row">
                    <label for="q_eud_xprofile"><?php \_e( 'BP xProfile Fields Update Time', 'q-export-user-data' ); ?></label>
                    <p class="filter" style="margin: 10px 0 0;">
                        <?php \_e('Select', 'q-export-user-data'); ?>: <a href="#" class="select-all"><?php \_e('All', 'q-export-user-data'); ?></a> | <a href="#" class="select-none"><?php _e('None', 'q-export-user-data'); ?></a>
                    </p>
                </th>
                <td>
                    <select multiple="multiple" id="bp_fields_update_time" name="bp_fields_update_time[]">
<?php

                    foreach ( $bp_fields as $key ) {

                        echo "<option value='".\esc_attr( $key )."' title='".\esc_attr( $key )."'>$key</option>";

                    }

?>
                    </select>
                    <p class="description"><?php
                        printf(
                            \__( 'Select the BuddyPress XProfile keys updated dates to export', 'q-export-user-data' )
                        );
                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="toggleable">
                <th scope="row"><label for="groups"><?php \_e( 'BP User Groups', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input id='groups' type='checkbox' name='groups' value='1' <?php \checked( isset ( self::$groups ) ? intval ( self::$groups ) : '', 1 ); ?> />
                    <p class="description"><?php
                        printf(
                            \__( 'Include BuddyPress Group Data. <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_html('https://codex.buddypress.org/buddypress-components-and-features/groups/')
                            ,   'Codex'
                        );
                    ?></p>
                </td>
            </tr>
<?php

        } // BP installed and active ##

?>
            <tr valign="top" class="toggleable">
                <th scope="row"><label for="user_fields"><?php \_e( 'Standard User Fields', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input id='user_fields' type='checkbox' name='user_fields' value='1' <?php \checked( isset ( self::$user_fields ) ? intval ( self::$user_fields ) : '', 1 ); ?> />
                    <p class="description"><?php

                        #self::log( 'user_fields: '.self::$user_fields );
                        #echo 'user_fields: '. self::$user_fields;

                        printf(
                            \__( 'Include Standard user profile fields, such as user_login. <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_html('https://codex.wordpress.org/Database_Description#Table:_wp_users')
                            ,   'Codex'
                        );

                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="toggleable">
                <th scope="row"><label for="q_eud_users_role"><?php \_e( 'Role', 'q-export-user-data' ); ?></label></th>
                <td>
                    <select name="role" id="q_eud_users_role">
<?php

                        echo '<option value="">' . \__( 'All Roles', 'q-export-user-data' ) . '</option>';

                        global $wp_roles;

                        foreach ( $wp_roles->role_names as $role => $name ) {

                            if ( isset ( self::$role ) && ( self::$role == $role ) ) {

                                echo "\n\t<option selected value='" . \esc_attr( $role ) . "'>$name</option>";

                            } else {

                                echo "\n\t<option value='" . \esc_attr( $role ) . "'>$name</option>";

                            }
                        }


?>
                    </select>
                    <p class="description"><?php
                        printf(
                            \__( 'Filter the exported users by a WordPress Role. <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_html('http://codex.wordpress.org/Roles_and_Capabilities')
                            ,   'Codex'
                        );
                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="toggleable">
                <th scope="row"><label for="roles"><?php \_e( 'User Roles', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input id='roles' type='checkbox' name='roles' value='1' <?php \checked( isset ( self::$roles ) ? intval ( self::$roles ) : '', 1 ); ?> />
                    <p class="description"><?php
                        printf(
                            \__( 'Include all of the users <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_html('http://codex.wordpress.org/Roles_and_Capabilities')
                            ,   'Roles'
                        );
                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="toggleable">
                <th scope="row"><label><?php \_e( 'Registered', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input type="text" id="q_eud_users_start_date" name="start_date" value="<?php echo self::$start_date; ?>" class="start-datepicker" />
                    <input type="text" id="q_eud_users_end_date" name="end_date" value="<?php echo self::$end_date; ?>" class="end-datepicker" />
                    <p class="description"><?php
                        printf(
                            \__( 'Pick a start and end user registration date to limit the results.', 'q-export-user-data' )
                        );
                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="toggleable">
                <th scope="row"><label><?php \_e( 'Limit Range', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input name="limit_offset" type="text" id="q_eud_users_limit_offset" value="<?php echo( self::$limit_offset ); ?>" class="regular-text code numeric" style="width: 136px;" placeholder="<?php _e( 'Offset', 'q-export-user-data' ); ?>">
                    <input name="limit_total" type="text" id="q_eud_users_limit_total" value="<?php echo ( self::$limit_total ); ?>" class="regular-text code numeric" style="width: 136px;" placeholder="<?php _e( 'Total', 'q-export-user-data' ); ?>">
                    <p class="description"><?php
                        printf(
                            \__( 'Enter an offset start number and a total number of users to export. <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_html('http://codex.wordpress.org/Function_Reference/get_users#Parameters')
                            ,   'Codex'
                        );
                    ?></p>
                </td>
            </tr>
<?php

        // buddypress x profile data ##
        if ( $bp_fields = buddypress::get_fields() ) {
            
?>
            <tr valign="top" class="toggleable">
                <th scope="row"><label><?php \_e( 'Updated Since', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input type="text" id="q_eud_updated_since_date" name="updated_since_date" value="<?php echo self::$updated_since_date; ?>" class="updated-datepicker" />
                    <select id="bp_field_updated_since" name="bp_field_updated_since">
<?php

                    foreach ( $bp_fields as $key ) {
                        
                        if ( self::$field_updated_since == $key ) {
                            
                            echo "<option value='".\esc_attr( $key )."' title='".\esc_attr( $key )."' selected>$key</option>";
                       
                        } else {
                        
                            echo "<option value='".\esc_attr( $key )."' title='".\esc_attr( $key )."'>$key</option>";
                        
                        }

                    }

?>
                    </select>

                    <p class="description"><?php
                        printf(
                            \__( 'Limit the results to users who have updated this extended profile field after this date.', 'q-export-user-data' )
                        );
                    ?></p>
                </td>
            </tr>
<?php

        } // bp date ##

        // pull in extra export options from api ##
        if ( $api_fields = \apply_filters( 'q/eud/api/admin/fields', [] ) ) {
            
            foreach( $api_fields as $field ) {
             
                api_admin::render( $field );

            }

        }

?>
            <tr valign="top">
                <th scope="row"><label for="q_eud_users_format"><?php \_e( 'Format', 'q-export-user-data' ); ?></label></th>
                <td>
                    <select name="format" id="q_eud_users_format">
<?php
						/*
						if ( isset ( self::$format ) && ( self::$format == 'excel2003' ) ) {

                            echo '<option selected value="excel2003">' . __( 'Excel 2003 (xls)', 'q-export-user-data' ) . '</option>';

                        } else {

                            echo '<option value="excel2003">' . __( 'Excel 2003 (xls)', 'q-export-user-data' ) . '</option>';

						}
						*/

                        if ( isset ( self::$format ) && ( self::$format == 'excel2007' ) ) {

                            echo '<option selected value="excel2007">' . __( 'Excel 2007 (xlsx)', 'q-export-user-data' ) . '</option>';

                        } else {

                            echo '<option value="excel2007">' . __( 'Excel 2007 (xlsx)', 'q-export-user-data' ) . '</option>';

                        }

                        if ( isset ( self::$format ) && ( self::$format == 'csv' ) ) {

                            echo '<option selected value="csv">' . __( 'CSV', 'q-export-user-data' ) . '</option>';

                        } else {

                            echo '<option value="csv">' . __( 'CSV', 'q-export-user-data' ) . '</option>';

                        }
?>
                    </select>
                    <p class="description"><?php
                        printf(
                            \__( 'Select the format for the export file.', 'q-export-user-data' )
                        );
                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="remember">
                <th scope="row"><label for="q_eud_save_options"><?php \_e( 'Stored Options', 'q-export-user-data' ); ?></label></th>
                <td>

                    <div class="row">
                        <input type="text" class="regular-text" name="save_new_export_name" id="q_eud_save_options_new_export" placeholder="<?php \_e( 'Export Name', 'q-export-user-data' ); ?>" value="<?php echo isset( $_POST['export_name'] ) ? \sanitize_text_field( $_POST['export_name'] ) : '' ; ?>">
                        <input type="submit" id="save_export" class="button-primary" name="save_export" value="<?php \_e( 'Save', 'q-export-user-data' ); ?>" />
                    </div>
                    <?php

                    // check if the user has any saved exports ##
                    if ( user::get_user_options() ) {

?>
                    <div class="row">
                        <select name="export_name" id="q_eud_save_options" class="regular-text">
<?php

                            // loop over each saved export ##
                            foreach( user::get_user_options() as $export ) {

                                // select Loaded export name, if selected ##
                                if (
                                    isset( $_POST['load_export'] )
                                    && isset( $_POST['export_name'] )
                                    && ( \sanitize_text_field( $_POST['export_name'] ) == $export )
                                ) {

                                    echo "<option selected value='$export'>".$export."</option>";

                                // just list previous export name ##
                                } else {

                                    echo "<option value='$export'>".$export."</option>";

                                }

                            }

?>
                        </select>

                        <input type="submit" id="load_export" class="button-primary" name="load_export" value="<?php _e( 'Load', 'q-export-user-data' ); ?>" />
                        <input type="submit" id="delete_export" class="button-primary" name="delete_export" value="<?php _e( 'Delete', 'q-export-user-data' ); ?>" />
<?php

                        }

?>
                        </div>
                        <p class="description"><?php \_e( 'Save, load or delete your stored export options.', 'q-export-user-data' ); ?></p>

                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="q_eud_xprofile"><?php \_e( 'Advanced Options', 'q-export-user-data' ); ?></label>
                </th>
                <td>
                    <div class="toggle">
                        <a href="#"><?php \_e( 'Show', 'q-export-user-data' ); ?></a>
                    </div>
                </td>
            </tr>

        </table>
        <p class="submit">
            <input type="hidden" name="_wp_http_referer" value="<?php echo \esc_url( $_SERVER['REQUEST_URI'] ); ?>" />
            <input type="submit" class="button-primary" value="<?php \_e( 'Run Export', 'q-export-user-data' ); ?>" />
        </p>
    </form>
    </div>

<?php
    }



    /**
    * style and interaction
    */
    public static function admin_enqueue_scripts( $hook )
    {

        // load the scripts on only the plugin admin page ##
        if ( 
            ! isset( $_GET['page'] )
            || $_GET['page'] != 'q-export-user-data' 
        
        ) {

            return false;

        }

        \wp_register_style( 'q-eud-css', \plugins_url( 'css/q-eud.css' ,__FILE__ ), '', self::version );
        \wp_enqueue_style( 'q-eud-css' );
        \wp_enqueue_script( 'q_eud_multi_select_js', \plugins_url( 'javascript/jquery.multi-select.js', __FILE__ ), array('jquery'), '0.9.8', false );

        // add script ##
        \wp_enqueue_script('jquery-ui-datepicker');
    	\wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
    	\wp_enqueue_style('jquery-ui');

        // add style ##
        // \wp_enqueue_style( 'jquery-ui-datepicker' );
        // \wp_enqueue_style('jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

    }



    
    /**
    * Inline jQuery
    * @since       0.8.2
    */
    public static function jquery()
    {

        // load the scripts on only the plugin admin page
        if (
            ! isset( $_GET['page'] ) 
            || $_GET['page'] != 'q-export-user-data' 
        ) {

            return false;

        }

?>
    <script>

    // lazy load in some jQuery validation ##
    jQuery(document).ready(function($) {

        // build super multiselect ##
        jQuery('#usermeta, #bp_fields, #bp_fields_update_time').multiSelect();

        // Select any fields from saved settings ##
        jQuery('#usermeta').multiSelect('select',([<?php echo( core::quote_array( self::$usermeta_saved_fields ) ); ?>]));
        jQuery('#bp_fields').multiSelect('select',([<?php echo( core::quote_array( self::$bp_fields_saved_fields ) ); ?>]));
        jQuery('#bp_fields_update_time').multiSelect('select',([<?php echo( core::quote_array( self::$bp_fields_update_time_saved_fields ) ); ?>]));

        // show only common ##
        jQuery('.usermeta-common').click(function(e){
            // console.log( 'Common..' );
            e.preventDefault();
            jQuery('#ms-usermeta .ms-selectable li.normal').hide();
        });

        // show all ##
        jQuery('.usermeta-all').click(function(e){
            e.preventDefault();
            jQuery('#ms-usermeta .ms-selectable li').show();
        });

        // select all ##
        jQuery('.select-all').click(function(e){
            e.preventDefault();
            jQuery( jQuery(this).parent().parent().parent().find( 'select' ) ).multiSelect( 'select_all' );
        });

        // select none ##
        jQuery('.select-none').click(function(e){
            e.preventDefault();
            jQuery( jQuery(this).parent().parent().parent().find( 'select' ) ).multiSelect( 'deselect_all' );
        });


        // validate number inputs ##
        $("input.numeric").blur(function() {

            //console.log("you entered "+ $(this).val());

            if ( $(this).val() && ! $.isNumeric( $(this).val() ) ) {

                //console.log("this IS NOT a number");
                $(this).css({ 'background': 'red', 'color': 'white' }); // highlight error ##
                $("p.submit .button-primary").attr('disabled','disabled'); // disable submit ##

            } else {

                $(this).css({ 'background': 'white', 'color': '#333' }); // remove error highlighting ##
                $("p.submit .button-primary").removeAttr('disabled'); // enable submit ##

            }

        });

        // toggle advanced options ##
        jQuery(".toggle a").click( function(e) {
            e.preventDefault();
            $toggleable = jQuery("tr.toggleable");
            $toggleable.toggle();
            if ( $toggleable.is(":visible") ) {
                jQuery(this).text("<?php _e( 'Hide', 'q-export-user-data' ); ?>");
            } else {
                jQuery(this).text("<?php _e( 'Show', 'q-export-user-data' ); ?>");
            }
        });

        // validate save button ##
        jQuery("#save_export").click( function(e) {

            // grab the value of the input ##
            var q_eud_save_options_new_export = jQuery('#q_eud_save_options_new_export').val();

            if ( ! q_eud_save_options_new_export || q_eud_save_options_new_export == '' ) {

                e.preventDefault(); // stop things here ##
                jQuery('#q_eud_save_options_new_export').addClass("error");

            }

        });

        // remove validation on focus ##
        jQuery("body").on( 'focus', '#q_eud_save_options_new_export', function(e) {

            jQuery(this).removeClass("error");

        });

<?php

        // method returns an object with "first" & "last" keys ##
        $dates = core::get_user_registered_dates();

        // get date format from WP settings #
        $date_format = 'yy-mm-dd' ; // get_option('date_format') ? get_option('date_format') : 'yy-mm-dd' ;
        $start_of_week = \get_option('start_of_week') ? \get_option('start_of_week') : 'yy-mm-dd' ;
        #self::log( 'Date format: '.$date_format );

?>

        // start date picker ##
        jQuery('.start-datepicker').datepicker( {
            dateFormat  : '<?php echo $date_format; ?>',
            minDate     : '<?php echo substr( $dates["0"]->first, 0, 10 ); ?>',
            maxDate     : '<?php echo substr( $dates["0"]->last, 0, 10 ); ?>',
            firstDay    : '<?php echo $start_of_week; ?>'
        } );

        // end date picker ##
        jQuery('.end-datepicker').datepicker( {
            dateFormat  : '<?php echo $date_format; ?>',
            minDate     : '<?php echo substr( $dates["0"]->first, 0, 10 ); ?>',
            maxDate     : '<?php echo substr( $dates["0"]->last, 0, 10 ); ?>',
            firstDay    : '<?php echo $start_of_week; ?>'
        } );

        // end date picker ##
        // might want to set minDate to something else, but not sure
        // what would be best for everyone
        jQuery('.updated-datepicker').datepicker( {
            dateFormat  : '<?php echo $date_format; ?>',
            minDate     : '<?php echo substr( $dates["0"]->first, 0, 10 ); ?>',
            maxDate	    : '0',
            firstDay    : '<?php echo $start_of_week; ?>'
        } );

    });

    </script>
<?php

    }



   
    /**
    * Inline CSS
    *
    * @since       0.8.2
    */
    public static function css()
    {

        // load the scripts on only the plugin admin page
        if (
            ! isset( $_GET['page'] ) 
            || $_GET['page'] != 'q-export-user-data' 
        ) {

            return false;

        }

?>
        <style>
            .toggleable { display: none; }
            .hidden { display: none; }
        </style>
<?php
            
    }

}
