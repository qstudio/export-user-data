<?php

namespace q\eud\core;

use q\eud\core\core as core;
use q\eud\core\helper as helper;

// load it up ##
// \q\eud\core\config::run();

class config extends \q_export_user_data {

    public static function run()
    {

        if ( \is_admin() ) {

            // load standard fields ##
            \add_action( 'admin_init', array( get_class(), 'load' ), 1 );

        }

    }



    /**
    * Load up saved exports for this user
    * Set to public as hooked into action
    *
    * @since       0.9.6
    * @return      Array of saved exports
    */
    public static function load()
    {

        // load api admin fields ##
        self::get_admin_fields();

        // kick it back ##
        return true;

    }



    /**
    * Load up saved exports for this user
    * Set to public as hooked into action
    *
    * @since       0.9.6
    * @return      Array of saved exports
    */
    public static function get_admin_fields()
    {

        // build array ##
        $array = array(
            'program' => array(
                'title'             => \_e( 'Programs', 'export-user-data' ),
                'label'             => 'program',
                'description'       => \__( 'Select the program that you wish to export.', 'export-user-data' ),
                'label_select'      => \__( 'All Programs', 'export-user-data' ),
                'options'           => \get_posts( array( 'post_type'=> 'program', 'posts_per_page' => -1 ) ),
                'options_ID'        => 'ID',
                'options_title'     => 'post_title'
            )
        );

        // test it ##
        #self::log( $array );

        // add to static property ##
        self::$api_admin_fields = $array;

        // filter and return ##
        apply_filters( 'q/eud/api/admin_fields', self::$api_admin_fields );

        // test it ##
        self::log( self::$api_admin_fields );

        // kick it back ##
        return true;

    }


}
