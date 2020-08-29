<?php

namespace q\eud\core;

use q\eud\core\core as core;
use q\eud\core\helper as helper;

// load it up ##
\q\eud\core\filters::run();

class filters extends \q_export_user_data {

    public static function run()
    {

        if ( \is_admin() ) {

            // EUD - filter key shown ##
            \add_filter( 'q/eud/admin/display_key', [ get_class(), 'display_key' ], 1, 1 );

        }

    }


    /**
    * Filter keys in EUD plugin
    *
    * @since 2.0.0
    */
    public static function display_key( $string = null ) 
    {

        #helper::log( 'string from filter: '.$string );

        if ( is_null( $string ) ) {

            return false;

        }

        // array of translations ##
        $array = [
            'first_name'    => 'First Name',
        ];

        // check if $string exists as key in $array and if so, replace and return ##
        if ( array_key_exists( $string, $array ) ) {

            return $array[$string];

        }

        // kick it back ##
        return $string;

    }

}
