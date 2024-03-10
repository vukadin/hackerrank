<?php
/**
 * Main plugin class.
 *
 * @package HackerRankVoter
 * @version 1.0.0
 */

namespace Hacker_Rank\Voter\Core;

use Hacker_Rank\Voter\Common\Helpers;

if ( ! defined( 'ABSPATH' ) ) :
	wp_die( 'Silence is gold!' );
endif;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Class instance.
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Get class instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {

		if ( ! self::$instance ) :
			self::$instance = new self();
		endif;

		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	private function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		add_action( 'wp_ajax_nopriv_hacker_rank_voter_vote', array( $this, 'ajax_vote' ) );
		add_action( 'wp_ajax_hacker_rank_voter_vote', array( $this, 'ajax_vote' ) );

		$settings = Helpers::get_settings();

		switch ( $settings['embed_type'] ) :

			case 'auto':
				add_filter( 'the_content', array( $this, 'output_ui_via_filter' ), PHP_INT_MAX );
				break;

			case 'manual':
				add_action( 'hacker_rank_voter', array( $this, 'output_ui_via_action' ) );
				break;

		endswitch;

		if ( ! empty( $settings['post_types'] ) ) :
			add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		endif;

		add_action( 'activated_plugin', array( $this, 'redirect_after_activation' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( HACKER_RANK_VOTER_FILE ), array( $this, 'register_plugin_links' ) );
	}

	/**
	 * Enqueue frontend JS & CSS.
	 *
	 * @return void
	 */
	public function enqueue_front_assets() {
		wp_enqueue_script( 'hacker-rank-voter', plugins_url( 'assets/js/front.js', HACKER_RANK_VOTER_FILE ), array( 'jquery' ), HACKER_RANK_VOTER_VERSION );
		wp_localize_script(
			'hacker-rank-voter',
			'HackerRankVoter',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'errors'  => array(
					'generic' => __( 'Voting failed.', 'hacker-rank-voter' ),
				),
			)
		);

		wp_register_style( 'google-fonts-Lato', 'https://fonts.googleapis.com/css2?family=Lato&display=swap' );
		wp_enqueue_style( 'hacker-rank-voter', plugins_url( 'assets/css/front.css', HACKER_RANK_VOTER_FILE ), array( 'google-fonts-Lato' ), HACKER_RANK_VOTER_VERSION );
	}

	/**
	 * Enqueue backend JS & CSS.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script( 'hacker-rank-voter', plugins_url( 'assets/js/admin.js', HACKER_RANK_VOTER_FILE ), array( 'jquery' ), HACKER_RANK_VOTER_VERSION );

		wp_enqueue_style( 'hacker-rank-voter', plugins_url( 'assets/css/admin.css', HACKER_RANK_VOTER_FILE ), null, HACKER_RANK_VOTER_VERSION );
	}

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'Hacker Rank Voter', 'hacker-rank-voter' ),
			__( 'Hacker Rank Voter', 'hacker-rank-voter' ),
			'manage_options',
			'hacker-rank-voter-settings',
			array( $this, 'output_settings_page' )
		);
	}

	/**
	 * Output settings page template.
	 *
	 * @return void
	 */
	public function output_settings_page() {

		$args = array(
			'settings' => Helpers::get_settings(),
		);
		Helpers::output_template( 'admin/pages/settings', $args );
	}

	/**
	 * Check if current user can save settings & process save.
	 *
	 * @return void
	 */
	public function save_settings() {

		if ( ! current_user_can( 'manage_options' ) ) :
			return;
		endif;

		$action = isset( $_POST['action'] ) ? $_POST['action'] : '';

		if ( Helpers::get_save_action() !== $action || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], Helpers::get_save_action() ) ) :
			return;
		endif;

		$settings = array(
			'post_types'    => isset( $_POST['post_types'] ) ? $_POST['post_types'] : array(),
			'question_text' => sanitize_text_field( $_POST['question_text'] ),
			'success_text'  => sanitize_text_field( $_POST['success_text'] ),
			'embed_type'    => $_POST['embed_type'],
		);

		Helpers::save_settings( $settings );

		$success_url = add_query_arg( 'status', 'success', admin_url( 'options-general.php?page=hacker-rank-voter-settings' ) );

		wp_redirect( $success_url );
	}

	/**
	 * Display success notice after settings are saved successfully.
	 *
	 * @return void
	 */
	public function display_admin_notices() {

		if ( 'settings_page_hacker-rank-voter-settings' !== get_current_screen()->id ) :
			return;
		endif;

		if ( isset( $_GET['status'] ) && 'success' === $_GET['status'] ) :
			printf(
				'<div class="notice notice-success notice-hacker-rank-voter-success is-dismissible"><p>%1$s</p></div>',
				__( 'Successfully saved settings!', 'hacker-rank-voter' ),
			);
		endif;
	}

	/**
	 * Outputs frontend form via `the_content` filter.
	 * Working only when `Embed Type` setting is set to `auto`.
	 *
	 * @param  string $content Post content.
	 * @return string Post content that may have been updated.
	 */
	public function output_ui_via_filter( $content ) {

		if ( is_singular() && is_main_query() && in_the_loop() ) :

			$settings = Helpers::get_settings();

			$post = get_queried_object();

			if ( in_array( $post->post_type, $settings['post_types'], true ) ) :
				$content .= Helpers::get_front_ui( $post->ID );
			endif;

		endif;

		return $content;
	}

	/**
	 * Outputs frontend form via `hacker_rank_voter` action.
	 * Working only when `Embed Type` setting is set to `manual`.
	 *
	 * @return void
	 */
	public function output_ui_via_action() {

		if ( is_singular() ) :

			$settings = Helpers::get_settings();

			$post = get_queried_object();

			if ( in_array( $post->post_type, $settings['post_types'], true ) ) :
				Helpers::output_front_ui( $post->ID );
			endif;

		endif;
	}

	/**
	 * Ajax action that process voting form.
	 * It returns JSON response, that might have `status` => "OK" if it succeeded,
	 * or `status` = "error" accompanied with `message` if it failed.
	 *
	 * @throws \Exception Throws an exception if post doesn't exist or post type doesn't have voting enabled.
	 * @throws \Exception Throws an exception if user already voted on this post.
	 * @return void
	 */
	public function ajax_vote() {

		try {

			$post_id = isset( $_POST['id'] ) ? $_POST['id'] : false;
			$type    = isset( $_POST['type'] ) ? $_POST['type'] : false;
			$nonce   = isset( $_POST['nonce'] ) ? $_POST['nonce'] : false;

			$allowed_types = array(
				'negative',
				'positive',
			);

			if ( ! $post_id || ! in_array( $type, $allowed_types, true ) || ! $nonce || ! wp_verify_nonce( $nonce, Helpers::get_vote_action( $post_id ) ) ) :
				throw new \Exception( __( 'Something went wrong, please try again later.', 'hacker-rank-voter' ) );
			endif;

			$settings = Helpers::get_settings();

			$post = get_post( $post_id );

			if ( ! $post || ! in_array( $post->post_type, $settings['post_types'], true ) ) :
				throw new \Exception( __( 'Voting is not allowed on this article.', 'hacker-rank-voter' ) );
			endif;

			$added_vote = Helpers::add_vote( $post_id, $type );

			if ( ! $added_vote ) :
				throw new \Exception( __( 'You already voted on this article.', 'hacker-rank-voter' ) );
			endif;

			$response = array(
				'status' => 'OK',
				'html'   => Helpers::get_front_ui( $post_id ),
			);
		} catch ( \Exception $e ) {

			$response = array(
				'status'  => 'error',
				'message' => $e->getMessage(),
			);
		}

		wp_send_json( $response );
	}

	/**
	 * Register `Votes` metabox.
	 *
	 * @return void
	 */
	public function register_meta_boxes() {

		$current_screen = get_current_screen();
		$settings       = Helpers::get_settings();

		// Add metabox only to the `Edit Post` screen.
		if ( 'post' === $current_screen->base && 'add' !== $current_screen->action && in_array( $current_screen->post_type, $settings['post_types'], true ) ) :

			add_meta_box(
				'hacker_rank_votes',
				__( 'Votes', 'hacker-rank-voter' ),
				array( $this, 'output_votes_metabox' ),
				$current_screen->post_type,
				'side',
				'low',
			);

		endif;
	}

	/**
	 * Output `Votes` metabox.
	 *
	 * @param  int $post Post ID.
	 * @return void
	 */
	public function output_votes_metabox( $post ) {

		$args = array(
			'stats' => Helpers::get_votes_stats( $post->ID ),
		);
		Helpers::output_template( 'admin/meta_boxes/votes', $args );
	}

	/**
	 * Redirect to settings page after plugin's activation.
	 *
	 * @param string $plugin Basename of the plugin that was activated.
	 * @return void
	 */
	public function redirect_after_activation( $plugin ) {
		if ( plugin_basename( HACKER_RANK_VOTER_FILE ) === $plugin ) :
			wp_redirect( admin_url( 'options-general.php?page=hacker-rank-voter-settings' ) );
			exit;
		endif;
	}

	/**
	 * Add link to settings page of the plugin on `Plugins' page in admin.
	 *
	 * @param  mixed $links Array of links for the specific plugin.
	 * @return mixed[] Array of links for the specific plugin.
	 */
	public function register_plugin_links( $links ) {
		$links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=hacker-rank-voter-settings' ) ),
			__( 'Settings', 'hacker-rank-voter' ),
		);
		return $links;
	}
}
