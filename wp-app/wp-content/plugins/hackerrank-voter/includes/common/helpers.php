<?php
/**
 * Plugin helpers.
 *
 * @package HackerRankVoter
 * @version 1.0.0
 */

namespace Hacker_Rank\Voter\Common;

if ( ! defined( 'ABSPATH' ) ) :
	wp_die( 'Silence is gold!' );
endif;

/**
 * Plugin Helpers.
 */
final class Helpers {

	/**
	 * Outputs template from plugin `template` directory.
	 *
	 * @param  string  $template Name of the template.
	 * @param  mixed[] $args Array with arguments to pass to the template.
	 * @return void
	 */
	public static function output_template( $template, $args = array() ) {
		echo self::get_template( $template, $args );
	}


	/**
	 * Gets template HTML from plugin `template` directory.
	 *
	 * @param  string  $template Name of the template.
	 * @param  mixed[] $args Array with arguments to pass to the template.
	 * @return string
	 */
	public static function get_template( $template, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$template_html = '';

		$template_file = HACKER_RANK_VOTER_DIR . '/templates/' . $template . '.php';

		if ( file_exists( $template_file ) ) :
			ob_start();
			include $template_file;
			$template_html = ob_get_clean();
		endif;

		return $template_html;
	}

	/**
	 * Returns action name to be used on settings page.
	 *
	 * @return string Name of the save settings action.
	 */
	public static function get_save_action() {
		return 'hacker-rank-voter-save-settings';
	}

	/**
	 * Get plugin settings.
	 *
	 * @return mixed[] Plugin settings.
	 */
	public static function get_settings() {
		return get_option( 'hacker_rank_voter_settings', self::get_default_settings() );
	}

	/**
	 * Saves plugin settings.
	 *
	 * @param  mixed[] $settings Plugin settings to be saved.
	 * @return void
	 */
	public static function save_settings( $settings ) {

		$default_settings = self::get_default_settings();

		// if no text was value, default to default value.
		if ( ! $settings['question_text'] ) :
			$settings['question_text'] = $default_settings['question_text'];
		endif;

		// if no text was value, default to default value.
		if ( ! $settings['success_text'] ) :
			$settings['success_text'] = $default_settings['success_text'];
		endif;

		update_option( 'hacker_rank_voter_settings', $settings );
	}

	/**
	 * Default plugin settings.
	 *
	 * @return mixed[] Default plugin settings.
	 */
	public static function get_default_settings() {
		return array(
			'question_text' => __( 'Was this article helpful?', 'hacker-rank-voter' ),
			'success_text'  => __( 'Thank you for your feedback.', 'hacker-rank-voter' ),
			'embed_type'    => 'auto',
			'post_types'    => array(
				'post',
			),
		);
	}

	/**
	 * Get all saved votes for a specified post.
	 *
	 * @param  int $post_id Post ID.
	 * @return mixed[] Array of all saved votes.
	 */
	public static function get_votes_for_post( $post_id ) {

		$votes = get_post_meta( $post_id, '_hacker_rank_voter', true );

		if ( ! $votes ) :
			$votes = array();
		endif;

		return $votes;
	}

	/**
	 * Save votes for a specified post.
	 *
	 * @param  int     $post_id Post ID.
	 * @param  mixed[] $votes Array of votes to be saved.
	 * @return void
	 */
	public static function save_votes_for_post( $post_id, $votes ) {
		update_post_meta( $post_id, '_hacker_rank_voter', $votes );
	}

	/**
	 * Returns votes stats for specified post.
	 *
	 * @param  mixed $post_id Post ID.
	 * @return mixed[] Array that has total count, positive and negative counts and positive and negative percentages.
	 */
	public static function get_votes_stats( $post_id ) {

		$votes = self::get_votes_for_post( $post_id );

		$positive_count = 0;
		$negative_count = 0;

		foreach ( $votes as $vote ) :

			switch ( $vote['type'] ) :
				case 'positive':
					++$positive_count;
					break;

				case 'negative':
					++$negative_count;
					break;
			endswitch;

		endforeach;

		$total_count = $positive_count + $negative_count;

		$positive_percentage = $total_count > 0 ? round( ( $positive_count / $total_count ) * 100 ) : 0;
		$negative_percentage = $total_count > 0 ? 100 - $positive_percentage : 0;

		return array(
			'total_count'         => $total_count,
			'positive_count'      => $positive_count,
			'positive_percentage' => $positive_percentage,
			'negative_count'      => $negative_count,
			'negative_percentage' => $negative_percentage,
		);
	}


	/**
	 * Get user's vote type from the specified post.
	 *
	 * @param  int $post_id Post ID.
	 * @return string User's vote type, returns `none` if he hasn't voted yet.
	 */
	public static function get_vote( $post_id ) {

		// account for proxied IP too.
		$current_ip      = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$current_ip_hash = self::hash_ip( $current_ip );
		$current_user_id = get_current_user_id();

		$votes = self::get_votes_for_post( $post_id );

		foreach ( $votes as $vote ) :

			// check if vote matches either user's ID or IP.
			if ( ( $current_user_id && (int) $vote['user'] === $current_user_id ) || $vote['ip_hash'] === $current_ip_hash ) :
				return $vote['type'];
			endif;

		endforeach;

		return 'none';
	}

	/**
	 * Check if user already voted on the specified post.
	 *
	 * @param  int $post_id Post ID.
	 * @return bool Whether user voted on the specified post.
	 */
	public static function has_vote( $post_id ) {
		return 'none' !== self::get_vote( $post_id );
	}

	/**
	 * Adds a new vote the the specified post.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  string $type Vote type, can be `positive` or `negative`.
	 * @return bool Returns `true` if vote was added and `false` if it failed.
	 */
	public static function add_vote( $post_id, $type ) {

		$current_ip      = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$current_ip_hash = self::hash_ip( $current_ip );
		$current_user_id = get_current_user_id();

		$votes = self::get_votes_for_post( $post_id );

		foreach ( $votes as $vote ) :

			// check if vote matches either user's ID or IP.
			if ( ( $current_user_id && (int) $vote['user'] === $current_user_id ) || $vote['ip_hash'] === $current_ip_hash ) :
				return false;
			endif;

		endforeach;

		$votes[] = array(
			'user'      => $current_user_id,
			'ip_hash'   => $current_ip_hash,
			'timestamp' => time(),
			'type'      => $type,
		);

		self::save_votes_for_post( $post_id, $votes );

		return true;
	}

	/**
	 * Outputs frontend form's HTML.
	 *
	 * @param  int $post_id Post ID.
	 * @return void
	 */
	public static function output_front_ui( $post_id ) {

		echo self::get_front_ui( $post_id );
	}

	/**
	 * Returns frontend form's HTML.
	 *
	 * @param  int $post_id Post ID.
	 * @return string Form HTML.
	 */
	public static function get_front_ui( $post_id ) {

		$settings = self::get_settings();

		$args = array(
			'settings' => $settings,
			'stats'    => self::get_votes_stats( $post_id ),
			'vote'     => self::get_vote( $post_id ),
			'post_id'  => $post_id,
			'nonce'    => wp_create_nonce( self::get_vote_action( $post_id ) ),
		);

		return self::get_template( 'front/vote_form', $args );
	}

	/**
	 * Get vote action to be used in frontend form.
	 *
	 * @param  int $post_id Post ID.
	 * @return string Vote action.
	 */
	public static function get_vote_action( $post_id ) {
		return 'hacker-rank-voter-vote-' . $post_id;
	}

	/**
	 * Hashes IP address with a salt defined in the plugin.
	 *
	 * @param  string $ip IP address.
	 * @return string Hashed IP address.
	 */
	public static function hash_ip( $ip ) {
		return md5( $ip . HACKER_RANK_VOTER_HASH_SALT );
	}
}
