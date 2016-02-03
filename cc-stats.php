<?php
/**
 *
 * @package   CC Stats
 * @author    David Cavins
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 *
 * @wordpress-plugin
 * Plugin Name:       CC Stats
 * Description:       Allows site admins to generate reports about hubs and members.
 * Version:           1.2.0
 * Author:            CARES staff
 * Text Domain:       plugin-name-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/* Nothing for now! */


/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

function cc_stats_admin_class_init() {

	// Admin and dashboard functionality
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'public/class-cc-stats.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'admin/class-cc-stats-admin.php' );
		add_action( 'bp_include', array( 'CC_Stats_Admin', 'get_instance' ), 21 );

	}

}
add_action( 'bp_include', 'cc_stats_admin_class_init' );
