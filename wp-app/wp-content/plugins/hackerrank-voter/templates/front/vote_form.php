<?php
/**
 * Template for the frontend form.
 *
 * @package HackerRankVoter
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) :
	wp_die( 'Silence is gold!' );
endif;

if ( 'none' === $args['vote'] ) : ?>
	<div class="hrv-container" data-id="<?php echo esc_attr( $args['post_id'] ); ?>" data-nonce="<?php echo esc_attr( $args['nonce'] ); ?>" >
		<div class="hrv-legend" ><?php _e( 'feedback', 'hacker-rank-voter' ); ?></div>
		<div class="hrv-form" >
			<div class="hrv-text" ><?php _e( $args['settings']['question_text'], 'hacker-rank-voter' ); ?></div>
			<div class="hrv-btns" >
				<div class="hrv-btn hrv-btn-yes" data-type="positive" ><span><?php _e( 'Yes', 'hacker-rank-voter' ); ?></span></div>
				<div class="hrv-btn hrv-btn-no" data-type="negative" ><span><?php _e( 'No', 'hacker-rank-voter' ); ?></span></div>
			</div>
		</div>
		<div class="hrv-error" ></div>
	</div>
<?php else : ?>
	<div class="hrv-container" >
		<div class="hrv-legend" ><?php _e( 'feedback', 'hacker-rank-voter' ); ?></div>
		<div class="hrv-form" >
			<div class="hrv-text" ><?php _e( $args['settings']['success_text'], 'hacker-rank-voter' ); ?></div>
			<div class="hrv-btns" >
				<div class="hrv-btn hrv-btn-yes hrv-btn-disabled <?php echo 'positive' === $args['vote'] ? 'hrv-btn-selected' : ''; ?>" ><span><?php echo $args['stats']['positive_percentage']; ?>%</span></div>
				<div class="hrv-btn hrv-btn-no hrv-btn-disabled <?php echo 'negative' === $args['vote'] ? 'hrv-btn-selected' : ''; ?>" ><span><?php echo $args['stats']['negative_percentage']; ?>%</span></div>
			</div>
		</div>
	</div>
<?php endif; ?>
