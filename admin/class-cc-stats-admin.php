<?php
/**
 * @package   CC Stats Admin
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package CC Stats Admin
 * @author  David Cavins
 */
class CC_Stats_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$plugin = CC_Stats::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		// add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Check for requests that stats be run.
		add_action( 'admin_init', array( $this, 'maybe_run_stats' ) );

		// Add error notices if needed.
		add_action( 'admin_init', array( $this, 'maybe_add_admin_notice' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( 'chosen-js-styles', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.min.css', array(), '1.4.2' );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( 'chosen-js-script', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.jquery.min.js', array( 'jquery' ), '1.4.2' );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Tools menu.
		 */
		$this->plugin_screen_hook_suffix = add_management_page(
			__( 'CC Stats', $this->plugin_slug ),
			__( 'CC Stats', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * Check for requests that stats be run.
	 *
	 * @since    1.0.0
	 */
	public function maybe_run_stats() {
		global $plugin_page;

		// Has anything been requested? Is this our screen?
		if ( ! isset( $_REQUEST['stat'] ) || $this->plugin_slug != $plugin_page ) {
			return;
		}

		// Is the nonce good?
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'cc-stats-' . get_current_user_id() ) ) {
			wp_safe_redirect( add_query_arg(
				array(
					'page' => $this->plugin_slug,
					'error' => 'bad-nonce'
				),
				admin_url( 'tools.php' ) ) );
			exit;
		}

		// Run the stat.
		switch ( $_REQUEST['stat'] ) {
			case 'hub-csv':
				$this->run_stat_hub_csv();
				break;
			case 'member-favorites':
				$this->run_member_favorites_csv();
				break;
			case 'member-friend-connections':
				$this->run_member_friend_connections_csv();
				break;
			case 'member-replied-to-activity-connections':
				$this->run_member_replied_to_activity_connections_csv();
				break;
			case 'member-private-messages-connections':
				$this->run_member_private_messages_connections_csv();
				break;
			case 'forum-subscriptions':
				$this->run_forum_subscriptions_csv();
				break;
			case 'forum-topic-subscriptions':
				$this->run_forum_topic_subscriptions_csv();
				break;
			case 'forum-reply-relationships':
				$this->run_forum_reply_relationships_csv();
				break;
			case 'forum-topic-favorites':
				$this->run_forum_topic_favorites_csv();
				break;
			case 'sa-hub-members-all':
				$this->run_sa_hub_members_csv( 'all' );
				break;
			case 'sa-hub-members-email':
				$this->run_sa_hub_members_csv( 'email' );
				break;
			case 'single-hub-member-list':
				$this->run_single_hub_member_list_csv();
				break;
			case 'chi-hub-members-all':
				$this->run_chi_hub_member_list_csv();
				break;
			default:
				// Do nothing if we don't know what we're doing.
				wp_safe_redirect( add_query_arg(
					array(
						'page' => $this->plugin_slug,
						'error' =>'unknown-stat'
					),
					admin_url( 'tools.php' ) ) );
				exit;
				break;
		}
	}

	/**
	 * Create the hub overview CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_stat_hub_csv() {
		global $wpdb;
		$bp = buddypress();

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-hubs-overview.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array( 'Hub ID', 'Name', 'Slug', 'Status', 'Date Created', 'Last Activity', 'Parent Hub ID', 'Total Members', 'Creator ID', 'Creator Email', 'Forum', 'BP Docs', 'CC Hub Home Page', 'CC Hub Pages', 'CC Hub Narratives', 'Custom Plugins' );
		fputcsv( $output, $row );

		// Groups Loop
		if ( bp_has_groups(  array(
				'order'             => 'ASC',
				'orderby'           => 'date_created',
				'page'              => null,
				'per_page'          => null,
				'max'               => false,
				'show_hidden'       => true,
				'user_id'           => null,
				'meta_query'        => false,
				'include'           => false,
				'exclude'           => false,
				'populate_extras'   => false,
				'update_meta_cache' => false,
			) ) ) {
			while ( bp_groups() ) {
				bp_the_group();
				$group_id = bp_get_group_id();
				$group_object = groups_get_group( array( 'group_id' => (int) $group_id, 'populate_extras' => true ) );

				// Hub ID
				$row = array( $group_id );

				// Name
				$row[] = bp_get_group_name();

				// Slug
				$row[] = bp_get_group_slug();

				// Status
				$row[] = bp_get_group_status();

				// Date Created
				$row[] = $group_object->date_created;

				// Date of last activity
				$row[] = $group_object->last_activity;

				// Parent Hub ID
				$row[] = $wpdb->get_var( $wpdb->prepare( "SELECT g.parent_id FROM {$bp->groups->table_name} g WHERE g.id = %d", $group_id ) );

				// Total Members
				$row[] = groups_get_total_member_count( $group_id );

				// Creator ID
				$creator_id = $group_object->creator_id;
				$row[] = $creator_id;

				// Creator Email
				$creator = get_user_by( 'id', $creator_id );
				$row[] = $creator->user_email;

				// Forum
				$row[] = $group_object->enable_forum;

				// BP Docs
				if ( function_exists( 'bp_docs_is_docs_enabled_for_group' ) ) {
					$row[] = bp_docs_is_docs_enabled_for_group( $group_id );
				} else {
					$row[] = '';
				}

				// CC Hub Home Page
				if ( function_exists( 'cc_get_group_home_page_post' ) && cc_get_group_home_page_post( $group_id )->have_posts() ) {
					$row[] = 1;
				} else {
					$row[] = 0;
				}

				// CC Hub Pages
				$row[] = (bool) groups_get_groupmeta( $group_id, "ccgp_is_enabled" );

				// CC Hub Narratives
				$row[] = (bool) groups_get_groupmeta( $group_id, "ccgn_is_enabled" );

				// Custom Plugins
				// To make your group-specific plugin be counted, so something like this:
				/*	add_filter( 'cc_stats_custom_plugins', 'prefix_report_custom_plugin', 10, 2 );
				 *	function prefix_report_custom_plugin( $custom_plugins, $group_id ) {
				 *		if ( $group_id == sa_get_group_id() ) {
				 *			$custom_plugins[] = "CC Salud America";
				 *		}
				 *		return $custom_plugins;
				 *	}
				 */
				$custom_plugins = apply_filters( 'cc_stats_custom_plugins', array(), $group_id );
				$custom_plugins = implode( ' ', $custom_plugins );
				$row[] = $custom_plugins;

				// Write the row.
				fputcsv( $output, $row );
			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the member favorites CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_member_favorites_csv() {

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-member-favorites.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array( 'user_id', 'user_email', 'favorited_post_by_user_id', 'favorited_post_by_user_email', 'date_recorded' );
		// Write the row.
		fputcsv( $output, $row );

		// Use a WP_User_Query meta_query to find users who have favorited activities.
		$args = array(
			'meta_key'     => 'bp_favorite_activities',
			'meta_compare' => 'EXISTS',
			'orderby'      => 'ID'
		);
		$user_query = new WP_User_Query( $args );

		// User Loop
		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				$favorites = bp_activity_get_user_favorites( $user->ID );
				// Passing an empty array to activity_ids gets them all. Abort!
				if ( empty( $favorites ) ) {
					continue;
				}
				// Next, get all of these activity items.
				$items = bp_activity_get_specific( array(
					'activity_ids'      => $favorites,
					'update_meta_cache' => false,
				) );
				foreach ( $items['activities'] as $item ) {
					$op = get_userdata( $item->user_id );
					$row = array( $user->ID, $user->user_email, $item->user_id, $op->user_email, $item->date_recorded );
					fputcsv( $output, $row );
				}
			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the member friend connections CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_member_friend_connections_csv() {
		global $wpdb;
		$bp = buddypress();

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-member-friend-connections.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array( 'initiator_user_id', 'initiator_username', 'initiator_email', 'friend_user_id', 'friend_username', 'friend_email', 'date_created', 'date_friend_registered', 'resulted_from_site_invite' );
		fputcsv( $output, $row );

		$friends = $wpdb->get_results( "SELECT
				 f.initiator_user_id,
				 u1.user_login as initiator_username,
				 u1.user_email as initiator_email,
				 f.friend_user_id,
				 u2.user_login as friend_username,
				 u2.user_email as friend_email,
				 u2.user_registered as friend_registered,
				 f.date_created
			FROM
				 {$bp->friends->table_name} f
				 LEFT JOIN $wpdb->users u1 ON u1.ID = f.initiator_user_id
				 LEFT JOIN $wpdb->users u2 ON u2.ID = f.friend_user_id
			WHERE f.is_confirmed = 1" );

		if ( ! empty( $friends ) ) {
			foreach ( $friends as $friend ) {
				$row = array(
					$friend->initiator_user_id,
					$friend->initiator_username,
					$friend->initiator_email,
					$friend->friend_user_id,
					$friend->friend_username,
					$friend->friend_email,
					$friend->date_created,
					$friend->friend_registered,
					$this->is_result_of_site_invite( $friend->initiator_user_id, $friend->friend_email ),
				);
				fputcsv( $output, $row );
			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Was this friendship extended because of a site invite?
	 *
	 * @since    1.2.0
	 */
	public function is_result_of_site_invite( $inviter_id, $invitee_email ) {
		$retval = 'No';
		if ( ! class_exists( 'Invite_Anyone_Invitation' ) ) {
			return $retval;
		}

		// The date_created is updated when the user accepts the invite so this is unreliable.
		// Probably checking invite_anyone_get_invitations_by_invited_email is better,
		// Note that "cleared" invites become status = draft.
		// So invite_anyone_get_invitations_by_invited_email() won't work because it finds only published posts.

		// Catch a couple of odd cases.
		$invitee_email = str_replace( ' ', '+', $invitee_email );
		$invitee_email = str_replace( '+', '.PLUSSIGN.', $invitee_email );

		$args = array(
			'inviter_id' 		=> $inviter_id,
			'invitee_email'		=> $invitee_email,
			'status'		=> array( 'draft', 'publish' ), // 'publish' are visible on Sent Invites, 'draft' when cleared
			'posts_per_page'	=> -1,
		);
		$invite = new Invite_Anyone_Invitation;
		$site_invites = $invite->get( $args );
		// If a connection exists, this will be not empty.
		if ( ! empty( $site_invites->posts ) ) {
			$retval = "Yes";
		}

		return $retval;
	}

	/**
	 * Create the replied-to-activity-update connections CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_member_replied_to_activity_connections_csv() {
		global $wpdb;
		$bp = buddypress();

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-member-replied-to-activity-connections.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array(
			'user_id',
			'user_email',
			'reply_to_thread_started_by_user_id',
			'reply_to_thread_started_by_user_email',
			'reply_to_comment_by_user_id',
			'reply_to_comment_by_user_email',
			'date_recorded'
			);
		fputcsv( $output, $row );

		$replies = $wpdb->get_results( "SELECT
			a.user_id,
			u.user_email,
			t.user_id as reply_to_thread_started_by_user_id,
			ut.user_email as reply_to_thread_started_by_user_email,
			r.user_id as reply_to_comment_by_user_id,
			ur.user_email as reply_to_comment_by_user_email,
			a.date_recorded
		FROM
			{$bp->activity->table_name} a
			LEFT JOIN {$bp->activity->table_name} t ON a.item_id = t.id
			LEFT JOIN {$bp->activity->table_name} r ON a.secondary_item_id = r.id
			LEFT JOIN $wpdb->users u ON a.user_id = u.ID
			LEFT JOIN $wpdb->users ut ON t.user_id = ut.ID
			LEFT JOIN $wpdb->users ur ON r.user_id = ur.ID
		WHERE a.type = 'activity_comment'" );

		if ( ! empty( $replies ) ) {
			foreach ( $replies as $reply ) {
				$row = array(
					$reply->user_id,
					$reply->user_email,
					$reply->reply_to_thread_started_by_user_id,
					$reply->reply_to_thread_started_by_user_email,
					$reply->reply_to_comment_by_user_id,
					$reply->reply_to_comment_by_user_email,
					$reply->date_recorded
				);
				fputcsv( $output, $row );
			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the replied-to-activity-update connections CSV when requested.
	 *
	 * @since    1.2.0
	 */
	public function run_member_private_messages_connections_csv() {
		global $wpdb;
		$bp = buddypress();

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-member-private-messaging-connections.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array(
			'thread_id',
			'message_id',
			'sender_id',
			'sender_email',
			'recipient_id',
			'recipient_email',
			'date_sent'
			);
		fputcsv( $output, $row );

		$thread_ids = $wpdb->get_col( "SELECT DISTINCT thread_id FROM {$bp->messages->table_name_messages}" );

		foreach ( $thread_ids as $thread_id ) {
			$thread = new BP_Messages_Thread( $thread_id, 'ASC', array( 'update_meta_cache' => false ) );

			foreach ( $thread->messages as $message ) {
				// Get the sender email.
				$sender_obj = get_userdata( $message->sender_id );

				// If the sender object isn't a WP User, move on.
				// Could happen if user is no longer a member.
				if ( ! ( $sender_obj instanceof WP_User ) ) {
					continue;
				}

				foreach ( $thread->recipients as $recipient ) {
					if ( $message->sender_id == $recipient->user_id ) {
						continue;
					}
					// Get the recipient's email.
					$recipient_obj = get_userdata( $recipient->user_id );

					// If the recipient object isn't a WP User, move on.
					if ( ! ( $recipient_obj instanceof WP_User ) ) {
						continue;
					}

					// Start the new row.
					$row = array(
						$thread_id,
						$message->id,
						$message->sender_id,
						$sender_obj->user_email,
						$recipient->user_id,
						$recipient_obj->user_email,
						$message->date_sent
					);
					fputcsv( $output, $row );
				}
			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the forum subscriptions CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_forum_subscriptions_csv() {

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-forum-subscriptions.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array( 'user_id', 'user_email', 'subscribed_to_forum_id', 'subscribed_to_forum_title', 'subscribed_to_forum_by_user_id', 'subscribed_to_forum_by_user_email', 'forum_date' );
		fputcsv( $output, $row );

		// Use a WP_User_Query meta_query to find users who have subscribed to forums.
		$args = array(
			'meta_key'     => 'wp__bbp_forum_subscriptions',
			'meta_compare' => 'EXISTS',
		);
		$user_query = new WP_User_Query( $args );

		// User Loop
		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				$subscriptions = bbp_get_user_subscribed_forum_ids( $user->ID );
				if ( empty( $subscriptions ) ) {
					continue;
				}

				$topics = new WP_QUERY( array(
					'post_type' => 'forum' ,
					'post__in' => $subscriptions,
					'cache_results' => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				) );

				if ( ! empty( $topics->posts ) ) {
					foreach ( $topics->posts as $item ) {
						$op = get_userdata( $item->post_author );
						$row = array( $user->ID, $user->user_email, $item->ID, $item->post_title, $item->post_author, $op->user_email, $item->post_date );
						fputcsv( $output, $row );
					}
				}

			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the forum topic subscriptions CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_forum_topic_subscriptions_csv() {

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-forum-topic-subscriptions.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array( 'user_id', 'user_email', 'subscribed_to_topic_id', 'subscribed_to_topic_title', 'subscribed_to_topic_by_user_id', 'subscribed_to_topic_by_user_email', 'topic_date' );
		fputcsv( $output, $row );

		// Use a WP_User_Query meta_query to find users who have subscribed to forums.
		$args = array(
			'meta_key'     => 'wp__bbp_subscriptions',
			'meta_compare' => 'EXISTS',
		);
		$user_query = new WP_User_Query( $args );

		// User Loop
		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				$subscriptions = bbp_get_user_subscribed_topic_ids( $user->ID );
				if ( empty( $subscriptions ) ) {
					continue;
				}

				$topics = new WP_QUERY( array(
					'post_type' => 'topic',
					'post__in' => $subscriptions,
					'cache_results' => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				) );

				if ( ! empty( $topics->posts ) ) {
					foreach ( $topics->posts as $item ) {
						$op = get_userdata( $item->post_author );
						$row = array( $user->ID, $user->user_email, $item->ID, $item->post_title, $item->post_author, $op->user_email, $item->post_date );
						fputcsv( $output, $row );
					}
				}

			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the forum reply relationships CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_forum_reply_relationships_csv() {

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-forum-reply-relationships.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array( 'user_id','user_email', 'in_reply_to_user_id', 'in_reply_to_user_email', 'in_topic_by_user_id', 'in_topic_by_user_email', 'in_forum_by_user_id', 'in_forum_by_user_email', 'in_forum_id', 'post_date' );
		fputcsv( $output, $row );

		$replies = new WP_QUERY( array(
			'post_type' => 'reply',
			'posts_per_page' => -1,
			'cache_results' => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			) );

		if ( ! empty( $replies->posts ) ) {
			foreach ( $replies->posts as $item ) {
				$poster = get_userdata( $item->post_author );
				$row = array( $item->post_author, $poster->user_email );

				$meta = get_post_meta( $item->ID );

				if ( ! empty( $meta['_bbp_reply_to'] ) ) {
					$reply_auth_id = get_post_field( 'post_author', $meta['_bbp_reply_to'][0] );
					$reply_auth = get_userdata( $reply_auth_id );
					$row[] = $reply_auth_id;
					$row[] = $reply_auth->user_email;
				} else {
					$row[] = '';
					$row[] = '';
				}

				if ( ! empty( $meta['_bbp_topic_id'] ) ) {
					$topic_auth_id = get_post_field( 'post_author', $meta['_bbp_topic_id'][0] );
					$topic_auth = get_userdata( $topic_auth_id );
					$row[] = $topic_auth_id;
					$row[] = $topic_auth->user_email;
				} else {
					$row[] = '';
					$row[] = '';
				}

				if ( ! empty( $meta['_bbp_forum_id'] ) ) {
					$forum_auth_id = get_post_field( 'post_author', $meta['_bbp_forum_id'][0] );
					$forum_auth = get_userdata( $forum_auth_id );
					$row[] = $forum_auth_id;
					$row[] = $forum_auth->user_email;
					$row[] = $meta['_bbp_forum_id'][0];
				} else {
					$row[] = '';
					$row[] = '';
					$row[] = '';
				}

				$row[] = $item->post_date;

				fputcsv( $output, $row );
			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the forum topic favorites CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_forum_topic_favorites_csv() {

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-forum-topic-favorites.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen('php://output', 'w');

		// Write a header row.
		$row = array( 'user_id', 'user_email', 'favorited_topic_id', 'favorited_topic_title', 'favorited_topic_by_user_id', 'favorited_topic_by_user_email', 'topic_date' );
		fputcsv( $output, $row );

		// Use a WP_User_Query meta_query to find users who have subscribed to forums.
		$args = array(
			'meta_key'     => 'wp__bbp_favorites',
			'meta_compare' => 'EXISTS',
		);
		$user_query = new WP_User_Query( $args );

		// User Loop
		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				$favorites = bbp_get_user_favorites_topic_ids( $user->ID );
				if ( empty( $favorites ) ) {
					continue;
				}

				$topics = new WP_QUERY( array(
					'post_type' => 'topic',
					'post__in' => $favorites,
					'cache_results' => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				) );

				if ( ! empty( $topics->posts ) ) {
					foreach ( $topics->posts as $item ) {
						$op = get_userdata( $item->post_author );
						$row = array( $user->ID, $user->user_email, $item->ID, $item->post_title, $item->post_author, $op->user_email, $item->post_date );
						fputcsv( $output, $row );
					}
				}

			}
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the Salud America CSV when requested.
	 *
	 * @since    1.1.0
	 */
	public function run_sa_hub_members_csv( $include = 'email' ) {
		global $wpdb;
		$bp = buddypress();

		if ( ! function_exists( 'sa_get_group_id' ) ) {
			return;
		}

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-sa-hub-members-' . $include . '.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen( 'php://output', 'w' );
		//add BOM to fix UTF-8 in Excel
		fputs( $output, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ) );

		// Should we return all members or only those who have agreed to receive emails?
		// First, we find all members in the group, whether they've agreed to receive mail or not.
		$user_query = groups_get_group_members( array( 'group_id' => sa_get_group_id() ) );
		if ( 'email' == $include ) {
			// In this case, we only include group members who have also agreed to be contacted.
			// Get the IDs of those who wish to be contacted.
			$email_ids = $wpdb->get_col( "SELECT d.user_id FROM {$bp->profile->table_name_data} d WHERE d.field_id = 1382 AND d.value LIKE '%receive%'", 0 );
		}

		// User Loop
		if ( ! empty( $user_query['members'] ) ) {
			// We want to exclude groups that aren't the base or SA group
			$profile_groups = bp_xprofile_get_groups();
			$profile_groups_ids = wp_list_pluck( $profile_groups, 'id' );
			$desired_groups = array( 1, 5 );
			$exclude_group_ids = array_diff( $profile_groups_ids, $desired_groups );
			$i = 1;

		    foreach ( $user_query['members'] as $user ) {
		        $profile = bp_xprofile_get_groups( array(
					'user_id'                => $user->ID,
					'fetch_fields'           => true,
					'fetch_field_data'       => true,
					'fetch_visibility_level' => true,
					'exclude_groups'         => $exclude_group_ids,
					'exclude_fields'         => array( 470 ),
					'update_meta_cache'      => true,
				) );

				// If this is the first result, we need to create the column header row.
				if ( 1 == $i ) {
					$row = array( 'user_id', 'user_email' );
					foreach ( $profile as $profile_group_obj ) {
						if ( strpos( $profile_group_obj->name, 'Salud' ) !== false ) {
							$is_salud_pfg = true;
						} else {
							$is_salud_pfg = false;
						}
						foreach ( $profile_group_obj->fields as $field ) {
							$towrite .= '"';
							if ( $is_salud_pfg ) {
								$row[] = 'SA: ' . $field->name;
							} else {
								$row[] = $field->name;
							}
						}
					}
					$row[] = 'date_joined';
					// Write the row.
					fputcsv( $output, $row );
				}

				// If this is the email-only version, skip this record if this member doesn't want to be contacted.
				if ( 'email' == $include && ! in_array( $user->ID, $email_ids) ) {
					$i++;
					continue;
				}

				// Write the user ID and email address
				$row = array( $user->ID, $user->user_email );
				// Record the user's data
				foreach ( $profile as $profile_group_obj ) {
					foreach ( $profile_group_obj->fields as $field ) {
						if ( 'public' == $field->visibility_level || 5 == $profile_group_obj->id ) {
							// Account for various field situations
							switch ( $field->id ) {
								case '1312':
									if ( ! empty( $field->data->value ) ) {
										$row[] = 'yes';
									} else {
										$row[] = '';
									}
									break;
								default:
									$value = maybe_unserialize( $field->data->value );
									if ( is_array( $value ) ) {
										$value = implode( ', ', preg_replace('/\s+|\r|\n/', ' ', trim( strip_tags( stripslashes( $value ) ) ) ) );
									}

									$row[] = preg_replace( '/\s+|\r|\n/', ' ', trim( strip_tags( stripslashes( $value ) ) ) );
									break;
							}
						} elseif ( 1218 == $field->id ) {
							// Affiliation field
							$value = maybe_unserialize( $field->data->value );
							if ( is_array( $value ) ) {
								$value = implode( ', ', $value );
							}

							$row[] = $value;
						} else {
							// If this shouldn't be included, add an empty array member/placeholder.
							$row[] = '';
						}
					}
				}
				$row[] = $user->date_modified;
				// Write the row.
				fputcsv( $output, $row );

				$i++;
		    }
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the single hub member list CSV when requested.
	 *
	 * @since    1.3.0
	 */
	public function run_single_hub_member_list_csv() {
		global $wpdb;
		$bp = buddypress();

		// Which group?
		$group_id = isset( $_POST['group_id'] ) ? (int) $_POST['group_id'] : 0;

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-hub-members-in-hub-id-' . $group_id . '.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen( 'php://output', 'w' );
		//add BOM to fix UTF-8 in Excel
		fputs( $output, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ) );

		// First, we find all members in the group.
		$user_query = groups_get_group_members( array( 'group_id' => $group_id, 'exclude_admins_mods' => false ) );

		// Create the column header row.
		$row = array( 'user_id', 'user_email', 'user_login', 'display_name', 'group_role', 'group_membership_modified', 'user_registered', 'last_activity' );
		// Write the row.
		fputcsv( $output, $row );

		// User Loop
		if ( ! empty( $user_query['members'] ) ) {
		    foreach ( $user_query['members'] as $user ) {
				// Write the user info.
				$row = array( $user->ID, $user->user_email, $user->user_login, $user->display_name, $user->user_title, $user->date_modified, $user->user_registered, $user->last_activity );

				// Write the row.
				fputcsv( $output, $row );
		    }
		}
		fclose( $output );
		exit();
	}

	/**
	 * Create the CHI CSV when requested.
	 *
	 * @since    1.4.0
	 */
	public function run_chi_hub_member_list_csv() {
		global $wpdb;
		$bp = buddypress();

		if ( ! function_exists( 'cdc_get_target_group_id' ) ) {
			return;
		}
		// @TODO: This could be generalized and added to the general Hub Member CSV, I think.
		$chi_hub_id = cdc_get_target_group_id();

		// Output headers so that the file is downloaded rather than displayed.
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cc-chi-hub-members.csv');

		// Create a file pointer connected to the output stream.
		$output = fopen( 'php://output', 'w' );
		//add BOM to fix UTF-8 in Excel
		fputs( $output, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ) );

		// Find all members in the group, whether they've agreed to receive mail or not.
		$user_query = groups_get_group_members( array( 'group_id' => $chi_hub_id ) );

		// User Loop
		if ( ! empty( $user_query['members'] ) ) {
			// We want to exclude groups that aren't the base or SA group
			$profile_groups = bp_xprofile_get_groups();
			$profile_groups_ids = wp_list_pluck( $profile_groups, 'id' );

			$desired_groups = array();
			$hub_pfg_ids = 0;
			if ( function_exists( 'grpf_get_associated_field_groups' ) ) {
				$hub_pfg_ids = grpf_get_associated_field_groups( $chi_hub_id );
				$desired_groups = $hub_pfg_ids;
			}
			$desired_groups[] = 1 ;
			$exclude_group_ids = array_diff( $profile_groups_ids, $desired_groups );
			$i = 1;

		    foreach ( $user_query['members'] as $user ) {
		        $profile = bp_xprofile_get_groups( array(
					'user_id'                => $user->ID,
					'fetch_fields'           => true,
					'fetch_field_data'       => true,
					'fetch_visibility_level' => true,
					'exclude_groups'         => $exclude_group_ids,
					'update_meta_cache'      => true,
				) );

				// If this is the first result, we need to create the column header row.
				if ( 1 == $i ) {
					$row = array( 'user_id', 'user_email' );
					foreach ( $profile as $profile_group_obj ) {
						if ( $profile_group_obj->id != 1 ) {
							$is_hub_pfg = true;
						} else {
							$is_hub_pfg = false;
						}
						foreach ( $profile_group_obj->fields as $field ) {
							$towrite .= '"';
							if ( $is_hub_pfg ) {
								$row[] = 'CHI: ' . $field->name;
							} else {
								$row[] = $field->name;
							}
						}
					}
					$row[] = 'date_joined';
					// Write the row.
					fputcsv( $output, $row );
				}

				// Write the user ID and email address
				$row = array( $user->ID, $user->user_email );
				// Record the user's data
				foreach ( $profile as $profile_group_obj ) {
					foreach ( $profile_group_obj->fields as $field ) {
						// Allow "public" data, hub-specific data and ZIP code fields.
						if ( 'public' == $field->visibility_level || in_array( $profile_group_obj->id, $hub_pfg_ids ) || 470 == $field->id ) {
							// Account for various field situations
							switch ( $field->id ) {
								case '1312':
									if ( ! empty( $field->data->value ) ) {
										$row[] = 'yes';
									} else {
										$row[] = '';
									}
									break;
								default:
									$value = maybe_unserialize( $field->data->value );
									if ( is_array( $value ) ) {
										$value = implode( ', ', preg_replace('/\s+|\r|\n/', ' ', trim( strip_tags( stripslashes( $value ) ) ) ) );
									}

									$row[] = preg_replace( '/\s+|\r|\n/', ' ', trim( strip_tags( stripslashes( $value ) ) ) );
									break;
							}
						} elseif ( 1218 == $field->id ) {
							// Affiliation field
							$value = maybe_unserialize( $field->data->value );
							if ( is_array( $value ) ) {
								$value = implode( ', ', $value );
							}

							$row[] = $value;
						} else {
							// If this shouldn't be included, add an empty array member/placeholder.
							$row[] = '';
						}
					}
				}
				$row[] = $user->date_modified;
				// Write the row.
				fputcsv( $output, $row );

				$i++;
		    }
		}
		fclose( $output );
		exit();
	}

	/**
	 * Check for errors in our stats page.
	 *
	 * @since    1.2.0
	 */
	public function maybe_add_admin_notice() {
		global $plugin_page;

		// Is this our screen?
		if ( $this->plugin_slug != $plugin_page ) {
			return;
		}

		if ( isset( $_REQUEST['error'] ) ) {
			add_action( 'admin_notices', array( $this, 'format_admin_error_notice' ) );
		}
	}

	/**
	 * Format admin notices.
	 *
	 * @since    1.2.0
	 */
	public function format_admin_error_notice() {
		if ( 'bad-nonce' == $_REQUEST['error'] ) {
			$class = 'error';
			$message = 'There&rsquo;s a problem with your security nonce. Please try again.';
		} elseif ( 'unknown-stat' == $_REQUEST['error'] ){
			$class = 'error';
			$message = 'I couldn&rsquo;t handle your request. Please try again.';
		} else {
			return;
		}
		?>
		<div class="<?php echo $class; ?>"><p><?php echo $message; ?></p></div>
		<?php
	}

}
