<?php

namespace q\eud\api;

use q\eud\core\core as core;
use q\eud\core\helper as helper;

// load it up ##
#\q\eud\api\admin::run();

class admin extends \q_export_user_data {

    public static function run()
    {

        if ( \is_admin() ) {

            // load standard fields ##
            #\add_action( 'admin_init', array( get_class(), 'load' ), 1 );

        }

    }



    /**
    * Render admin fields
    *
    * @since       2.0.0.
    * @return      HTML
    */
    public static function render( $array = null )
    {

        // check if we have any fields to show ##
        if ( 
            is_null( $array ) 
            || ! is_array( $array )    
        ) {

            #helper::log( 'No fields found' );

            return false;

        }

        #helper::log( $array );

        // check that we have all required arrays ##
        if ( 
            ! $array['title'] // string ##
            || ! $array['label'] // lowercase string ##
            || ! $array['type']
            #|| ! $array['description']
        ) {

            #helper::log( 'Missing data' );

            return false;

        }

        // is this toggleable ? ##
        $toggleable = false === $array['title'] ? 'standard' : 'toggleable' ;

        // keep labels formatted nicely ##
        $array['label'] = \sanitize_key( $array['label'] );

        #helper::log( $array['options'] );

        // build out options ##
        if ( ! self::has_options( $array ) ) { 
            
            #helper::log( 'Missing options for: '.$array['label'] );

            return false;

        }

?>
        <tr valign="top" class="<?php echo $toggleable; ?>">
            <th scope="row"><label for="q_eud_<?php echo $array['label']; ?>"><?php echo $array['title']; ?></label></th>
            <td>
<?php 

                // options ##
                self::build_options( $array );

                // do we have a description ? ##
                if ( isset( $array['description'] ) ) {

?>
                    <p class="description"><?php echo $array['description']; ?></p>
<?php

                }

?>
            </td>
        </tr>
<?php
    
    }


    public static function has_options( $array = null )
    {

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



    public static function build_options( $array = null )
    {

        if ( 
            is_null( $array )
            || ! is_array( $array )
            || ! isset( $array['type'] )
        ) {

            #helper::log( 'Error building options for: '.$array['label'] );

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



    public static function field_select( $array = null )
    {

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

            #helper::log( 'Error building select options for: '.$array['label'] );

            return false;

        }

        #helper::log( 'Building select options for: '.$array['label'] );

        // is this a multiselect ? ##
        $multiselect = isset( $array['multiselect'] ) ? ' multiple="multiple"' : '' ;

?>
        <select <?php echo $multiselect; ?> name="<?php echo $array['label']; ?>" id="q_eud_<?php echo $array['label']; ?>">
<?php

            // label ##
            echo '<option value="">'.$array['label_select'].'</option>';

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
                <option value='<?php echo \esc_attr( $id ); ?>'><?php echo $title; ?></option>
<?php

            }

?>
        </select>
<?php

    }


}
