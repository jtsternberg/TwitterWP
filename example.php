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
		'consumer_key'        => 'YOUR CONSUMER KEY';
		'consumer_secret'     => 'YOUR CONSUMER SECRET';
		'access_token'        => 'YOUR ACCESS TOKEN';
		'access_token_secret' => 'YOUR ACCESS TOKEN SECRET';
	);
	// initiate your app
	$tw = TwitterWP::start( $app );
?>
<div id="message" class="updated">
	<p><?php $tw->hello(); ?></p>
	<pre><?php print_r( $tw->authenticate_user( 'jtsternberg' ) ); ?></pre>
	<pre><?php print_r( $tw->get_tweets( 'jtsternberg', 5 ) ); ?></pre>
</div>
<?php

}