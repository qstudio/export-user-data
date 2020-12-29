<?php

/**
 * Autoload classes within the namespace `willow`
 */
spl_autoload_register( function( $class ) {

	// error_log( 'Autoload Class: '.$class );

	// project-specific namespace prefix
	$prefix = 'q\\eud\\';

	/**
	 * Does the class being called use the namespace prefix?
	 *
	 *  - Compare the first {$len} characters of the class name against our prefix
	 *  - If no match, move to the next registered autoloader
	 */

	// character length of our prefix
	$len = strlen( $prefix );

	// if the first {$len} characters don't match
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {

		// error_log( 'Autoload Class Rejected, as outside namespace: '.$class );

		return;

	}

	// base directory where our class files and folders live
	$base_dir = __DIR__ . '/library/';

	/**
	 * Perform normalizing operations on the requested class string
	 *
	 * - Remove the prefix from the class name (so that willow\Plugin looks at src/plugin.php)
	 * - Replace namespace separators with directory separators in the class name
	 * - Prepend the base directory
	 * - Append with .php
	 * - Convert to lower case
	 */
	$class_name = str_replace( $prefix, '', $class );

	// error_log( 'Class Name: '.$class_name );

	$possible_file = strtolower( $base_dir . str_replace('\\', '/', $class_name ) . '.php' );

	// require the file if it exists
	if( file_exists( $possible_file ) ) {

		// error_log( 'Willow auto-loading: '.$possible_file );

		require $possible_file;

	}

});
