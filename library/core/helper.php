<?php

// namespace ##
namespace q\report\core;

// piggyback Q helper ##
use q\core\helper as q_helper;

/**
 * helper Class
 * @package   q_report\core
 */
class helper extends \q_export_user_data {

     
    /**
     * Write to WP Error Log
     *
     * @since       1.5.0
     * @return      void
     */
    public static function log( $log )
    {

        if ( true === WP_DEBUG ) {

            $trace = debug_backtrace();
            $caller = $trace[1];

            $suffix = sprintf(
                __( ' - %s%s() %s:%d', 'Q' )
                ,   isset($caller['class']) ? $caller['class'].'::' : ''
                ,   $caller['function']
                ,   isset( $caller['file'] ) ? $caller['file'] : 'n'
                ,   isset( $caller['line'] ) ? $caller['line'] : 'x'
            );

            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ).$suffix );
            } else {
                error_log( $log.$suffix );
            }

        }

    }


}