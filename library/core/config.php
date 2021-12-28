<?php

namespace q\eud\core;

// import classes ##
use q\eud;
use q\eud\plugin as plugin;
use q\eud\core\helper as h;

class config {

	private $plugin;

	function __construct(){

		$this->plugin = plugin::get_instance(); 

	}

    /**
    * Load up saved exports for this user
    * Set to public as hooked into action
    *
    * @since       0.9.6
    * @return      Array of saved exports
    */
    public static function load(){

        // load api admin fields ##
        self::get_admin_fields();

    }

    /**
    * Load up saved exports for this user
    * Set to public as hooked into action
    *
    * @since       0.9.6
    * @return      Array of saved exports
    */
    public function get_admin_fields(){

        // build array ##
        $array = [
            'program' => [
                'title'             => \_e( 'Programs', 'export-user-data' ),
                'label'             => 'program',
                'description'       => \__( 'Select the program that you wish to export.', 'export-user-data' ),
                'label_select'      => \__( 'All Programs', 'export-user-data' ),
                'options'           => \get_posts([ 'post_type'=> 'program', 'posts_per_page' => -1 ]),
                'options_ID'        => 'ID',
                'options_title'     => 'post_title'
			]
		];

        // filter and return ##
        apply_filters( 'q/eud/api/admin_fields', $array );

		// add to static property ##
		$this->plugin->set( '_api_admin_fields', $array );

        // kick back true ##
        return true;

    }

}
