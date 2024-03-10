<?php
/**
 * Plugin entry file.
 *
 * Initializes plugins and sets up plugin specific constants.
 *
 * @package HackerRankVoter
 */

/*
Plugin Name: Hacker Rank Voter
Description: Allows voting whether the post is helpful or not.
Author: Njegos Vukadin
Version: 1.0.0
Text Domain: hacker-rank-voter
*/

namespace Hacker_Rank\Voter;

use Hacker_Rank\Voter\Core\Plugin;

if ( ! defined( 'ABSPATH' ) ) :
	wp_die( 'Silence is gold!' );
endif;

define( 'HACKER_RANK_VOTER_FILE', __FILE__ );
define( 'HACKER_RANK_VOTER_DIR', __DIR__ );
define( 'HACKER_RANK_VOTER_VERSION', '1.0.0' );

require 'autoload.php';

$hacker_rank_voter = Plugin::get_instance();
