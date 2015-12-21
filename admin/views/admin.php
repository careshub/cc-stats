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

	<section>
		<h3>Hub Stats</h3>
		<ul>
			<li>
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
			</li>
		</ul>
	</section>

	<section>
		<h3>Member Stats</h3>
		<ul>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'member-favorites'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a member favorites CSV.</a>
			</li>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'member-friend-connections'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a friend connections CSV.</a>
			</li>
		</ul>
	</section>

	<section>
		<h3>Forum Stats</h3>
		<ul>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'forum-subscriptions'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a forum subscriptions CSV.</a>
			</li>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'forum-topic-subscriptions'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a forum topic subscriptions CSV.</a>
			</li>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'forum-reply-relationships'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a forum reply relationships CSV.</a>
				<span class="info">Shows member relationships by showing who replied to whom, in which topic and in which forum.</span>
			</li>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'forum-topic-favorites'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a forum topic favorites CSV.</a>
			</li>
		</ul>
	</section>

</div>
