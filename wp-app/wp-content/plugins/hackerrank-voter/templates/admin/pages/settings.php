<?php
/**
 * Settings page template.
 *
 * @package HackerRankVoter
 * @version 1.0.0
 */

namespace Hacker_Rank\Voter;

use Hacker_Rank\Voter\Common\Helpers;

if ( ! defined( 'ABSPATH' ) ) :
	wp_die( 'Silence is gold!' );
endif;

$embed_types = array(
	array(
		'id'          => 'auto',
		'label'       => __( 'Automatic', 'hacker-rank-voter' ),
		'description' => __( 'Using <code>the_content</code> filter.', '' ),
	),
	array(
		'id'          => 'manual',
		'label'       => __( 'Manual', 'hacker-rank-voter' ),
		'description' => __( 'Using the following code <code>do_action("hacker_rank_voter")</code> will output UI in the desired place.', 'hacker-rank-voter' ),
	),
)
?>
<div class="wrap" >
	<h2><?php _e( 'Hacker Rank Voter Settings', 'hacker-rank-voter' ); ?></h2>
	<p><?php _e( 'Please configure plugin labels, select type of embedding and post types on which voter should be enabled.', 'hacker-rank-voter' ); ?></p>
	<form id="hacker-rank-settings-form" method="post" >
		<table class="form-table" >
			<tr>
				<th><label for="hacker-rank-voter-question-text" ><?php _e( 'Question Text', 'hacker-rank-voter' ); ?></label></th>
				<td><input type="text" class="large-text" name="question_text" id="hacker-rank-voter-question-text" value="<?php echo esc_attr( $args['settings']['question_text'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="hacker-rank-voter-success-text" ><?php _e( 'Success Text', 'hacker-rank-voter' ); ?></label></th>
				<td><input type="text" class="large-text" name="success_text" id="hacker-rank-voter-success-text" value="<?php echo esc_attr( $args['settings']['success_text'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="hacker-rank-voter-success-text" ><?php _e( 'Embed Type:', 'hacker-rank-voter' ); ?></label></th>
				<td>
					<ul>
						<?php
						foreach ( $embed_types as $embed_type ) :

							$is_checked = $embed_type['id'] === $args['settings']['embed_type'];

							printf(
								'<li %5$s><label><input type="radio" name="embed_type" value="%1$s" %4$s /> %2$s</label><div class="description" >%3$s</div></li>',
								esc_attr( $embed_type['id'] ),
								$embed_type['label'],
								$embed_type['description'],
								checked( $is_checked, true, false ),
								$is_checked ? ' class="active"' : '',
							);

						endforeach;
						?>
					</ul>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Enabled on Post Type:', 'hacker-rank-voter' ); ?></th>
				<td>
					<ul>
					<?php
					$public_post_types = get_post_types(
						array(
							'public' => true,
						),
						'objects'
					);

					foreach ( $public_post_types as $post_type ) :

						if ( 'attachment' === $post_type->name ) :
							continue;
						endif;

							$is_checked = in_array( $post_type->name, $args['settings']['post_types'], true );

						printf(
							'<li><label><input type="checkbox" name="post_types[]" value="%1$s" %3$s /> %2$s</label></li>',
							esc_attr( $post_type->name ),
							esc_html( $post_type->label ),
							checked( $is_checked, true, false ),
						);
					endforeach;
					?>
					</ul>
				</td>
			</tr>
		</table>
		<input type="hidden" name="action" value="<?php echo esc_attr( Helpers::get_save_action() ); ?>" />
		<?php wp_nonce_field( Helpers::get_save_action() ); ?>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'hacker-rank-voter' ); ?>" />
	</form>
</div>
