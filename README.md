TwitterWP
=========

A tool for connecting to the twitter 1.1 API (via WordPress http API) using your Twitter app credentials.

To get started, simply pass your twitter app's credentials to `Twitter::start( $your_credentials )`.
The only thing that matters is that this array is in this order, the keys can be anything. You can even pass them in with a query string:
`'0=YOUR_CONSUMER_KEY&1=YOUR_CONSUMER_SECRET&2=YOUR_ACCESS_TOKEN&3=YOUR_ACCESS_TOKEN_SECRET'`

I tried to purposely leave the names of these values out of the source code so that your app's credentials are somewhat secured through obscurity. I preferred this since my plugin that depends on this library will be distributed.

#### From example.php:
```php
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
```
