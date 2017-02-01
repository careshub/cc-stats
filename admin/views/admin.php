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

global $wpdb;
$bp = buddypress();

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
			<li>
				<form name="single-hub-member-list" id="single-hub-member-list" class="standard-form" action="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'single-hub-member-list'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>" method="post">
					<label for="group_id"><strong>Create a member list CSV for a single hub.</strong></label><br />
					<?php
						$groups = $wpdb->get_results( "SELECT id, name	FROM {$bp->groups->table_name} ORDER BY	name ASC" );
						if ( $groups ) {
							?>
							<select name="group_id" id="hub-member-list-select" class="chosen-select" data-placeholder="Choose a hub..." style="width:75%;">
							<!-- Include an empty option for chosen.js support-->
							<option></option>
							<?php
							foreach ( $groups as $group ) {
								?>
								<option value="<?php echo $group->id; ?>"><?php echo $group->name; ?></option>
								<?php
							}
							?>
							</select><br />
							<input type="checkbox" id="fetch_profile_data" name="fetch_profile_data"> <label for="fetch_profile_data">Include hub-related profile field data.</label>
							<?php
						}
					?>
					<input type="submit" value="Create CSV">
				</form>
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
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'member-replied-to-activity-connections'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a &ldquo;replied-to-activity-update&rdquo; connections CSV.</a>
			</li>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'member-private-messages-connections'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a private messaging connections CSV.</a>
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

	<section>
		<h3>Private Message Stats</h3>
		<ul>
			<li>
				Private message read percentage: <?php echo $cc_stats->private_message_read_ratio(); ?>%
			</li>
		</ul>
	</section>

	<section>
		<h3>Salud America Stats</h3>
		<ul>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'sa-hub-members-all'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a Salud America hub member CSV &ndash; Complete List</a>
			</li>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'sa-hub-members-email'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a Salud America hub member CSV &ndash; Email Contacts Only</a>
			</li>
		</ul>
	</section>

	<section>
		<h3>Community Health Improvement Hub Stats</h3>
		<ul>
			<li>
				<a href="<?php
					// URL needs to have the stat we're requesting and be nonced.
					echo wp_nonce_url( add_query_arg(
						array(
							'page' => $plugin_slug,
							'stat' => 'chi-hub-members-all'
						),
						admin_url( 'tools.php' )
					), 'cc-stats-' . $current_user_id );
				?>">Generate a CHI hub member CSV &ndash; Complete List</a>
			</li>
		</ul>
	</section>

</div>
<script type="text/javascript">
	jQuery( '.chosen-select' ).chosen({});
</script>
