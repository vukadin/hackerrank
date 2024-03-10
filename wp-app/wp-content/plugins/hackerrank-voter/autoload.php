<?php
/**
 * Plugin autoloader.
 *
 * @package HackerRankVoter
 * @version 1.0.0
 */

namespace Hacker_Rank\Voter;

if ( ! defined( 'ABSPATH' ) ) :
	wp_die( 'Silence is gold!' );
endif;

spl_autoload_register(
	function ( $class_name ) {
		$includes_dir = HACKER_RANK_VOTER_DIR . '/includes/';

		$namespace_separator = '\\';
		$class_name          = strtolower( $class_name );
		$namespace           = strtolower( __NAMESPACE__ );

		if ( 0 !== strpos( $class_name, $namespace . $namespace_separator ) ) :
			return;
		endif;

		$formatted_class_name = substr( $class_name, strlen( $namespace . $namespace_separator ) );

		$file = $includes_dir . str_replace( $namespace_separator, '/', $formatted_class_name ) . '.php';

		if ( file_exists( $file ) ) :
			include $file;
		endif;
	}
);
