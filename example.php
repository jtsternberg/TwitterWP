<?php

if ( ! class_exists( 'TwitterWP' ) )
	require_once( 'lib/TwitterWP.php' );

/**
 * Example TwitterWP usage
 */
add_action( 'all_admin_notices', 'twitterwp_example_test' );
function twitterwp_example_test() {
	// app credentials
	// (must be in this order)
	$app = array(
		'consumer_key'        => 'YOUR CONSUMER KEY',
		'consumer_secret'     => 'YOUR CONSUMER SECRET',
		'access_token'        => 'YOUR ACCESS TOKEN',
		'access_token_secret' => 'YOUR ACCESS TOKEN SECRET',
	);
	// initiate your app
	$tw = TwitterWP::start( $app );

	// Also works:
	// $tw = TwitterWP::start( '0=CONSUMER_KEY&1=CONSUMER_SECRET&2=ACCESS_TOKEN&3=ACCESS_TOKEN_SECRET' );

	$user = 'jtsternberg';
	// bail here if the user doesn't exist
	if ( ! $tw->user_exists( $user ) )
		return;

	echo '<div id="message" class="updated">';
	echo '<pre>'. print_r( $tw->get_tweets( 'jtsternberg', 5 ), true ) .'</pre>';
	echo '</div>';

	// Now let's check our app's rate limit status
	$rate_status = $tw->rate_limit_status();
	echo '<div id="message" class="updated">';

	if ( is_wp_error( $rate_status ) )
		$tw->show_wp_error( $rate_status );
	else
		echo '<pre>'. print_r( $rate_status, true ) .'</pre>';

	echo '</div>';

}
