<?php

namespace q\eud\core;

// import classes ##
use q\eud;
use q\eud\plugin as plugin;
use q\eud\core\helper as h;

class filters {

	private $plugin;

	function __construct( \q\eud\plugin $plugin ){

		$this->plugin = $plugin; 

	}

    /**
    * Filter keys in EUD plugin
    *
    * @since 2.0.0
    */
    public static function display_key( $string = null ){

        if ( is_null( $string ) ) {

            return;

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
