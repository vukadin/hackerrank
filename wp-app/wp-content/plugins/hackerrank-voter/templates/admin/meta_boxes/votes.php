<?php
/**
 * Template for the votes meta box.
 *
 * @package HackerRankVoter
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) :
	wp_die( 'Silence is gold!' );
endif;

if ( $args['stats']['total_count'] > 0 ) :
	$positive_title = sprintf(
		__( '%1$d positive vote(s) out of %2$d total vote(s)', 'hacker-rank-voter' ),
		$args['stats']['positive_count'],
		$args['stats']['total_count'],
	);

	$negative_title = sprintf(
		__( '%1$d negative vote(s) out of %2$d total vote(s)', 'hacker-rank-voter' ),
		$args['stats']['negative_count'],
		$args['stats']['total_count'],
	);
	?>
	<div class="hrv-votes" >
		<div class="hrv-vote hrv-vote-yes" title="<?php echo esc_attr( $positive_title ); ?>" ><span><?php echo $args['stats']['positive_percentage']; ?>%</span></div>
		<div class="hrv-vote hrv-vote-no" title="<?php echo esc_attr( $negative_title ); ?>" ><span><?php echo $args['stats']['negative_percentage']; ?>%</span></div>
	</div>
	<?php
else :
	echo '<p>' . __( 'No votes yet.', 'hacker-rank-voter' ) . '</p>';
endif;
