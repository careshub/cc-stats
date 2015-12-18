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
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		// add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Check for requests that stats be run.
		add_action( 'admin_init', array( $this, 'maybe_run_stats' ) );

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
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Plugin_Name::VERSION );
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
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Plugin_Name::VERSION );
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

		// What is the complete list of actions we're checking for?
		$actions = array(
			'hub-csv',
			'member-favorites',
			'forum-subscriptions',
			'forum-topic-subscriptions',
			);

		// Has anything been requested? Is this our screen?
		if ( ! isset( $_REQUEST['stat'] ) || $this->plugin_slug != $plugin_page ) {
			return;
		}

		// Is it a request we can handle?
		if ( ! in_array( $_REQUEST['stat'], $actions ) ) {
			return;
		}

		// Is the nonce good?
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'cc-stats-' . get_current_user_id() ) ) {
			return;
		}

		// All's well, run the stat.
		switch ( $_REQUEST['stat'] ) {
			case 'hub-csv':
				$this->run_stat_hub_csv();
				break;
			case 'member-favorites':
				$this->run_member_favorites_csv();
				break;
			case 'forum-subscriptions':
				$this->run_forum_subscriptions_csv();
				break;
			case 'forum-topic-subscriptions':
				$this->run_forum_topic_subscriptions_csv();
				break;
			default:
				// Do nothing if we don't know what we're doing.
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
		$row = array( 'Hub ID', 'Name', 'Slug', 'Status', 'Date Created', 'Parent Hub ID', 'Total Members', 'Creator ID', 'Creator Email', 'Forum', 'BP Docs', 'CC Hub Home Page', 'CC Hub Pages', 'CC Hub Narratives', 'Custom Plugins' );
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
				$group_object = groups_get_group( array( 'group_id' => (int) $group_id ) );

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
		global $wpdb;
		$bp = buddypress();

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
	 * Create the forum subscriptions CSV when requested.
	 *
	 * @since    1.0.0
	 */
	public function run_forum_subscriptions_csv() {
		global $wpdb;
		$bp = buddypress();

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
		global $wpdb;
		$bp = buddypress();

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
}