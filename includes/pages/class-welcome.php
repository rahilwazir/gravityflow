<?php
/**
 * Gravity Flow Reports
 *
 * @package     GravityFlow
 * @subpackage  Classes/Gravity_Flow_Reports
 * @copyright   Copyright (c) 2015-2018, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class Gravity_Flow_Reports
 */
class Gravity_Flow_Welcome {

	/**
	 * Display of the welcome page
	 *
	 * @param array $args The welcome page arguments.
	 */
	public static function display( $args ) {

		?>

		<h2>This is content from the welcome class</h2>
		<?php

	}

	
}
