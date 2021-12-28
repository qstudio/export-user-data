<?php

namespace q\eud\admin;

// import classes ##
use q\eud;
use q\eud\plugin;
use q\eud\core\helper as h;
use q\eud\core\user as user;
use q\eud\api\admin as api_admin;

class render {

	private $plugin, $user;

	public function __construct( \q\eud\plugin $plugin, \q\eud\core\user $user ){

		$this->plugin = $plugin; 

		$this->user = $user;

	}

    /**
    * Add administration menus
    *
    * @since 0.1
    **/
    public function add_menu():void
	{

        \add_users_page ( 
            \__( 'Export User Data', 'q-export-user-data' ), 
            \__( 'Export User Data', 'q-export-user-data' ), 
            \apply_filters( 'q/eud/admin_capability', 'list_users' ), 
            'q-export-user-data', 
            [ $this, 'admin_page' ] // callback method ## 
        );

    }

    /**
    * Content of the admin page
    *
    * @since    0.1
    */
    public function admin_page(){

        // quick security check ##
        if ( ! \current_user_can( \apply_filters( 'q/eud/admin_capability', 'list_users' ) ) ) {

            \wp_die( \__( 'You do not have sufficient permissions to access this page.', 'q-export-user-data' ) );

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
                $save_export = \sanitize_text_field( $_POST['save_new_export_name'] );

            } elseif ( ! empty( $_POST['export_name'] ) ) {

                $save_export = \sanitize_text_field( $_POST['export_name'] );

            }

            // Build array of $options to save and save them ##
            if ( isset( $save_export ) ) {

				// prepare all array values ##
				$usermeta = 
					isset( $_POST['usermeta'] ) ? 
					array_map( 'sanitize_text_field', 
					$_POST['usermeta'] ) : 
					'';
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
					\sanitize_text_field( $_POST['user_fields'] ) :
                    '1' ;
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

                // assign all values to an array ##
                $save_array = array (
                    'usermeta_saved_fields' => $usermeta,
                    'role' => $role,
                    'roles' => $roles,
                    'user_fields' => $user_fields,
                    'groups' => $groups,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'limit_offset' => $limit_offset,
                    'limit_total' => $limit_total,
                    'updated_since_date' => $updated_since_date,
                    'format' => $format
                );

                // store the options, for next load ##
                $this->user->set_user_options( $save_export, $save_array );

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

            $this->user->get_user_options_by_export( \sanitize_text_field( $_POST['export_name'] ) );

        }

        // Delete settings button was pressed ##
        if (
            isset( $_POST['delete_export'] )
            && isset( $_POST['export_name'] )
            && \check_admin_referer( 'q-eud-admin-page', '_wpnonce-q-eud-admin-page' )
        ) {

            $this->user->delete_user_options( \sanitize_text_field( $_POST['export_name'] ) );

        }

?>
<div class="wrap">
    <h2><?php \_e( 'Export User Data', 'q-export-user-data' ); ?></h2>
<?php

    // nothing happening? ##
    if ( isset( $_GET['qeud_error'] ) && 'empty' == $_GET['qeud_error'] ) {
        
	?>
	<div class="updated"><p><strong><?php \_e( 'No users found to export in passed query.', 'q-export-user-data' ); ?></strong></p></div>
	<?php
	
	}
	
	// get props
	$_groups = $this->plugin->get( '_groups' );
	$_user_fields = $this->plugin->get( '_user_fields' );
	$_role = $this->plugin->get( '_role' );
	$_roles = $this->plugin->get( '_roles' );
	$_start_date = $this->plugin->get( '_start_date' );
	$_end_date = $this->plugin->get( '_end_date' );
	$_limit_offset = $this->plugin->get( '_limit_offset' );
	$_limit_total = $this->plugin->get( '_limit_total' );
	$_updated_since_date = $this->plugin->get( '_updated_since_date' );
	$_format = $this->plugin->get( '_format' );
	$_updated_since_date = $this->plugin->get( '_updated_since_date' );

?>
    <form method="post" action="" enctype="multipart/form-data">
        <?php \wp_nonce_field( 'q-eud-admin-page', '_wpnonce-q-eud-admin-page' ); ?>
        <table class="form-table">
<?php

            // allow admin to select user meta fields to export ##
            global $wpdb;
    
            // filterable SQL ##
            $meta_keys_sql = \apply_filters( 
                'q/eud/admin/sql', 
                "SELECT distinct(meta_key) FROM $wpdb->usermeta" 
            );

			// run Query ##
			$meta_keys = $wpdb->get_results( $meta_keys_sql );

            // sort ##
			asort( $meta_keys );

            // get meta_key value from object ##
            $meta_keys = \wp_list_pluck( $meta_keys, 'meta_key' );

            // allow array to be filtered ##
            $meta_keys_common = \apply_filters( 'q/eud/admin/meta_keys_common', [] );

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

                                if ( strpos( $key, $drop ) !== false ) {

                                    // https://wordpress.org/support/topic/bugfix-numbers-in-export-headers?replies=1
                                    // removed $key = assignment, as not required ##
                                    if ( ( array_search( $key, $meta_keys ) ) !== false ) {

                                        $usermeta_class = 'common';

                                    }

                                }

                            }

                            // print key ##
                            ?>
							<option value="<?php \esc_attr_e( $key ); ?>" title="<?php \esc_attr_e( $key ); ?>" class="<?php \esc_attr_e( $usermeta_class ); ?>">
								<?php \esc_attr_e( $display_key ); ?>
							</option>
							<?php

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
            <tr valign="top" class="toggleable">
                <th scope="row"><label for="user_fields"><?php \_e( 'Standard User Fields', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input id='user_fields' type='checkbox' name='user_fields' value='1' <?php \checked( isset ( $_user_fields ) ? intval ( $_user_fields ) : '', 1 ); ?> />
                    <p class="description"><?php

                        printf(
                            \__( 'Include Standard user profile fields, such as user_login. <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_url('https://codex.wordpress.org/Database_Description#Table:_wp_users')
                            ,   'Codex'
                        );

                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="toggleable">
                <th scope="row"><label for="q_eud_users_role"><?php \_e( 'Role', 'q-export-user-data' ); ?></label></th>
                <td>
                    <select name="role" id="q_eud_users_role">
						<option value=""><?php \_e( 'All Roles', 'q-export-user-data' ); ?></option>
						<?php

					global $wp_roles;

					foreach ( $wp_roles->role_names as $role => $name ) {

						if ( isset ( $_role ) && ( $_role == $role ) ) {

						?>
						<option selected value="<?php \esc_attr_e( $role ); ?>"><?php \esc_attr_e( $name ); ?></option>
						<?php

						} else {

						?>
						<option value="<?php \esc_attr_e( $role ); ?>"><?php \esc_attr_e( $name ); ?></option>
						<?php

						}
					}

					?>
                    </select>
                    <p class="description"><?php
                        printf(
                            \__( 'Filter the exported users by a WordPress Role. <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_url('http://codex.wordpress.org/Roles_and_Capabilities')
                            ,   'Codex'
                        );
                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="toggleable">
                <th scope="row"><label for="roles"><?php \_e( 'User Roles', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input id='roles' type='checkbox' name='roles' value='1' <?php \checked( isset ( $_roles ) ? intval ( $_roles ) : '', 1 ); ?> />
                    <p class="description"><?php
                        printf(
                            \__( 'Include all of the users <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_url('http://codex.wordpress.org/Roles_and_Capabilities')
                            ,   'Roles'
                        );
                    ?></p>
                </td>
            </tr>

            <tr valign="top" class="toggleable">
                <th scope="row"><label><?php \_e( 'Registered', 'q-export-user-data' ); ?></label></th>
                <td>
                    <input type="text" id="q_eud_users_start_date" name="start_date" value="<?php \esc_attr_e( $_start_date ); ?>" class="start-datepicker" />
                    <input type="text" id="q_eud_users_end_date" name="end_date" value="<?php \esc_attr_e( $_end_date ); ?>" class="end-datepicker" />
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
                    <input name="limit_offset" type="text" id="q_eud_users_limit_offset" value="<?php \esc_attr_e( $_limit_offset ); ?>" class="regular-text code numeric" style="width: 136px;" placeholder="<?php \_e( 'Offset', 'q-export-user-data' ); ?>">
                    <input name="limit_total" type="text" id="q_eud_users_limit_total" value="<?php \esc_attr_e ( $_limit_total ); ?>" class="regular-text code numeric" style="width: 136px;" placeholder="<?php \_e( 'Total', 'q-export-user-data' ); ?>">
                    <p class="description"><?php
                        printf(
                            \__( 'Enter an offset start number and a total number of users to export. <a href="%s" target="_blank">%s</a>', 'q-export-user-data' )
                            ,   \esc_url('http://codex.wordpress.org/Function_Reference/get_users#Parameters')
                            ,   'Codex'
                        );
                    ?></p>
                </td>
            </tr>
			<?php

			// pull in extra export options from api ##
			if ( $api_fields = \apply_filters( 'q/eud/api/admin/fields', [] ) ) {

				// create api instance #
				$api_admin = new \q\eud\api\admin();
				
				foreach( $api_fields as $field ) {
				
					$api_admin->render( $field );

				}

			}

			?>
            <tr valign="top">
                <th scope="row"><label for="q_eud_users_format"><?php \_e( 'Format', 'q-export-user-data' ); ?></label></th>
                <td>
                    <select name="format" id="q_eud_users_format">
					<?php

					if ( isset ( $_format ) && ( $_format == 'excel2007' ) ) {

						?>
						<option selected value="excel2007"><?php \_e( 'Excel 2007 (xlsx)', 'q-export-user-data' ); ?></option>
						<?php
					
					} else {

						?>
						<option value="excel2007"><?php \_e( 'Excel 2007 (xlsx)', 'q-export-user-data' ); ?></option>
						<?php

					}

					if ( isset ( $_format ) && ( $_format == 'csv' ) ) {

						?>
						<option selected value="csv"><?php \_e( 'CSV', 'q-export-user-data' ); ?></option>
						<?php

					} else {

						?>
						<option value="csv"><?php \_e( 'CSV', 'q-export-user-data' ); ?></option>
						<?php

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
                        <input type="text" class="regular-text" name="save_new_export_name" id="q_eud_save_options_new_export" placeholder="<?php \_e( 'Export Name', 'q-export-user-data' ); ?>" value="<?php echo isset( $_POST['export_name'] ) ? \esc_attr_e( $_POST['export_name'] ) : '' ; ?>">
                        <input type="submit" id="save_export" class="button-primary" name="save_export" value="<?php \_e( 'Save', 'q-export-user-data' ); ?>" />
                    </div>
                    <?php

					// check if the user has any saved exports ##
                    if ( $this->user->get_user_options() ) {

					?>
                    <div class="row">
                        <select name="export_name" id="q_eud_save_options" class="regular-text">
						<?php

					// loop over each saved export ##
					foreach( $this->user->get_user_options() as $export ) {

						// select Loaded export name, if selected ##
						if (
							isset( $_POST['load_export'] )
							&& isset( $_POST['export_name'] )
							&& ( $_POST['export_name'] == $export )
						) {

							?>
							<option selected value='<?php \esc_attr_e( $export ); ?>'><?php \esc_attr_e( $export ); ?></option>
							<?php

						// just list previous export name ##
						} else {

							?>
							<option value='<?php \esc_attr_e( $export ); ?>'><?php \esc_attr_e( $export ); ?></option>
							<?php

						}

					}

						?>
                        </select>

                        <input type="submit" id="load_export" class="button-primary" name="load_export" value="<?php \_e( 'Load', 'q-export-user-data' ); ?>" />
                        <input type="submit" id="delete_export" class="button-primary" name="delete_export" value="<?php \_e( 'Delete', 'q-export-user-data' ); ?>" />
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
            <input type="hidden" name="_wp_http_referer" value="<?php echo isset( $_SERVER['REQUEST_URI'] ) ? \esc_url( $_SERVER['REQUEST_URI'] ) : '' ; ?>" />
            <input type="submit" class="button-primary" value="<?php \_e( 'Run Export', 'q-export-user-data' ); ?>" />
        </p>
    </form>
    </div>
	<?php
    
	}

    /**
    * style and interaction
    */
    public function admin_enqueue_scripts( $hook ):void
	{

        // load the scripts on only the plugin admin page ##
        if ( 
            ! isset( $_GET['page'] )
            || $_GET['page'] != 'q-export-user-data' 
        
        ) {

            return;

        }

        \wp_register_style( 'q-eud-css', $this->plugin::get_plugin_url( 'library/admin/css/q-eud.css' ), '', $this->plugin->get( '_version' ) );
        \wp_enqueue_style( 'q-eud-css' );
        \wp_enqueue_script( 'q-eud-multi-select', $this->plugin::get_plugin_url( 'library/admin/javascript/jquery.multi-select.js' ), array('jquery'), '0.9.8', false );

        // add script ##
        \wp_enqueue_script('jquery-ui-datepicker');
		\wp_register_style('jquery-ui', $this->plugin::get_plugin_url( 'library/admin/css/jquery-ui.css' ), array(), '1.8.0' );
    	\wp_enqueue_style('jquery-ui');

    }
    
    /**
     * Inline jQuery
	 *
     * @since       0.8.2
     */
    public function jquery():void
	{

        // load the scripts only on the plugin admin page
        if (
            ! isset( $_GET['page'] ) 
            || \sanitize_text_field( $_GET['page'] ) != 'q-export-user-data' 
        ) {

            return ;

		}

		// get saved fields ##
		$_usermeta_saved_fields = $this->plugin->get( '_usermeta_saved_fields' );
		if ( ! is_array( $_usermeta_saved_fields ) ) {
			$_usermeta_saved_fields = [];
		}

?>
    <script>

    // lazy load in some jQuery validation ##
    jQuery(document).ready(function($) {

        // build super multiselect ##
        jQuery('#usermeta, #bp_fields, #bp_fields_update_time').multiSelect();

        // Select any fields from saved settings ##
        jQuery('#usermeta').multiSelect('select',([ <?php echo implode( ',', array_map( function( $field ){ return "'".\esc_attr( $field )."'"; }, $_usermeta_saved_fields ) );; // escaped ?>]));

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
                jQuery(this).text("<?php \_e( 'Hide', 'q-export-user-data' ); ?>");
            } else {
                jQuery(this).text("<?php \_e( 'Show', 'q-export-user-data' ); ?>");
            }
        });

        // validate save button ##
        jQuery("#save_export").click( function(e) {

            // grab the value of the input ##
            var q_eud_save_options_new_export = jQuery('#q_eud_save_options_new_export').val();

            if ( ! q_eud_save_options_new_export || q_eud_save_options_new_export == '' ) {

                e.preventDefault();
                jQuery('#q_eud_save_options_new_export').addClass("error");

            }

        });

        // remove validation on focus ##
        jQuery("body").on( 'focus', '#q_eud_save_options_new_export', function(e) {

            jQuery(this).removeClass("error");

        });

		<?php
        // method returns an object with "first" & "last" keys ##
        $dates = \q\eud\core\get::user_registered_dates();

        // get date format from WP settings #
        $date_format = 'yy-mm-dd' ; // get_option('date_format') ? get_option('date_format') : 'yy-mm-dd' ;
        $start_of_week = \get_option('start_of_week') ? \get_option('start_of_week') : 'yy-mm-dd' ;

		?>
        // start date picker ##
        jQuery('.start-datepicker').datepicker( {
            dateFormat  : '<?php \esc_attr_e( $date_format ); ?>',
            minDate     : '<?php \esc_attr_e( substr( $dates["0"]->first, 0, 10 ) ); ?>',
            maxDate     : '<?php \esc_attr_e( substr( $dates["0"]->last, 0, 10 ) ); ?>',
            firstDay    : '<?php \esc_attr_e( $start_of_week ); ?>'
        } );

        // end date picker ##
        jQuery('.end-datepicker').datepicker( {
            dateFormat  : '<?php \esc_attr_e( $date_format ); ?>',
            minDate     : '<?php \esc_attr_e( substr( $dates["0"]->first, 0, 10 ) ); ?>',
            maxDate     : '<?php \esc_attr_e( substr( $dates["0"]->last, 0, 10 ) ); ?>',
            firstDay    : '<?php \esc_attr_e( $start_of_week ); ?>'
        } );

        // end date picker ##
        // might want to set minDate to something else, but not sure
        // what would be best for everyone
        jQuery('.updated-datepicker').datepicker( {
            dateFormat  : '<?php \esc_attr_e( $date_format ); ?>',
            minDate     : '<?php \esc_attr_e( substr( $dates["0"]->first, 0, 10 ) ); ?>',
            maxDate	    : '0',
            firstDay    : '<?php \esc_attr_e( $start_of_week ); ?>'
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
    public function css(){

        // load the scripts on only the plugin admin page
        if (
            ! isset( $_GET['page'] ) 
            || $_GET['page'] != 'q-export-user-data' 
        ) {

            return;

        }

	?>
	<style>
		.toggleable { display: none; }
		.hidden { display: none; }
	</style>
	<?php
            
    }

}
