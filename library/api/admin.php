<?php

namespace q\eud\api;

// import classes ##
use q\eud;
use q\eud\plugin;
use q\eud\core\core;
use q\eud\core\helper as h;

class admin {

	private $plugin;

	function __construct(){

		$this->plugin = plugin::get_instance(); 

	}

    /**
    * Render admin fields
    *
    * @since       2.0.0.
    * @return      HTML
    */
    function render( $array = null ){

        // check if we have any fields to show ##
        if ( 
            is_null( $array ) 
            || ! is_array( $array )    
        ) {

            return false;

        }

        // check that we have all required arrays ##
        if ( 
            ! $array['title'] // string ##
            || ! $array['label'] // lowercase string ##
            || ! $array['type']
        ) {

            return false;

        }

        // is this toggleable ? ##
        $toggleable = false === $array['title'] ? 'standard' : 'toggleable' ;

        // keep labels formatted nicely ##
        $array['label'] = \sanitize_key( $array['label'] );

        // build out options ##
        if ( ! self::has_options( $array ) ) { 
            
            return false;

        }

?>
        <tr valign="top" class="<?php \esc_attr_e( $toggleable ); ?>">
            <th scope="row"><label for="q_eud_<?php \esc_attr_e( $array['label'] ); ?>"><?php \esc_attr_e( $array['title'] ); ?></label></th>
            <td>
<?php 

                // options ##
                self::build_options( $array );

                // do we have a description ? ##
                if ( isset( $array['description'] ) ) {

?>
                    <p class="description"><?php \esc_attr_e( $array['description'] ); ?></p>
<?php

                }

?>
            </td>
        </tr>
<?php
    
    }

    public static function has_options( $array = null ){

        if ( 
            is_null( $array )
            || ! is_array( $array )
            || ! isset( $array['options'] )
            || ! isset( $array['label'] )
            || ! is_array( $array['options'] )
            || ! isset( $array['options_ID'] )
            || ! isset( $array['options_title'] )
            || ! isset( $array['label_select'] )
        ) {

            return false;

        }

        // crude ##
        return true;

    }

    public static function build_options( $array = null ){

        if ( 
            is_null( $array )
            || ! is_array( $array )
            || ! isset( $array['type'] )
        ) {

            return false;

        }

        // start empty ##
        $return = false;

        switch ( $array['type'] ) {

            case ( 'select' ) :

                $return = self::field_select( $array );

            break ;

        }

        // kick it back ##
        return $return;

    }

    public static function field_select( $array = null ){

        if ( 
            is_null( $array )
            || ! is_array( $array )
            || ! isset( $array['options'] )
            || ! isset( $array['label'] )
            || ! is_array( $array['options'] )
            || ! isset( $array['options_ID'] )
            || ! isset( $array['options_title'] )
            || ! isset( $array['label_select'] )
        ) {

            return false;

        }

        // is this a multiselect ? ##
        $multiselect = isset( $array['multiselect'] ) ? ' multiple="multiple"' : '' ;

?>
        <select <?php \esc_attr_e( $multiselect ); ?> name="<?php \esc_attr_e( $array['label'] ); ?>" id="q_eud_<?php \esc_attr_e( $array['label'] ); ?>">
		<?php

            // label ##
            echo '<option value="">'.\esc_attr( $array['label_select'] ).'</option>';

            // loop over all options  ##
            foreach ( $array['options'] as $item ) {

                // id ##
                $id = $item->{$array['options_ID']}; // ID

                // title
                $title = $item->{$array['options_title']}; // post_title ##

                // check if option built ##
                if (
                    ! $id
                    || ! $title
                ) {

                    continue;

                }

?>
                <option value='<?php \esc_attr_e( $id ); ?>'><?php esc_attr_e( $title ); ?></option>
<?php

            }

?>
        </select>
<?php

    }

}
