<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   CC Stats
 * @author    Your Name <email@example.com>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Your Name or Company Name
 */

$cc_stats = CC_Stats::get_instance();
$plugin_slug = $cc_stats->get_plugin_slug();
$current_user_id = get_current_user_id();

?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<h3>Hub Stats</h3>
	<a href="<?php
		// URL needs to have the stat we're requesting and be nonced.
		echo wp_nonce_url( add_query_arg(
		    array(
		        'page' => $plugin_slug,
		        'stat' => 'hub-csv'
		    ),
		    admin_url( 'tools.php' )
		), 'cc-stats-' . $current_user_id );
	?>">Generate an overview CSV.</a>

</div>
