TwitterWP
=========

A tool for connecting to the twitter 1.1 API (via WordPress http API) using your Twitter app credentials.

To get started, simply pass your twitter app's credentials to `Twitter::start( $your_credentials )`.
The only thing that matters is that this array is in this order, the keys can be anything. You can even pass them in with a query string:
`'0=YOUR_CONSUMER_KEY&1=YOUR_CONSUMER_SECRET&2=YOUR_ACCESS_TOKEN&3=YOUR_ACCESS_TOKEN_SECRET'`

I tried to purposely leave the names of these values out of the source code so that your app's credentials are somewhat secured through obscurity. I preferred this since my plugin that depends on this library will be distributed.

#### To initate TwitterWP:
```php
$app = array(
	'consumer_key'        => 'YOUR CONSUMER KEY',
	'consumer_secret'     => 'YOUR CONSUMER SECRET',
	'access_token'        => 'YOUR ACCESS TOKEN',
	'access_token_secret' => 'YOUR ACCESS TOKEN SECRET',
);
// initiate your app
$TwitterWP = TwitterWP::start( $app );
```

#### Available methods:

* Check if a user exits
	```php
	$TwitterWP->user_exists( $user = '' );
	```

* Get a number of a user's tweets
	```php
	$TwitterWP->get_tweets( $user = '', $count = 1 );
	```

* Get a number of search term tweets
	```php
	$TwitterWP->get_search_results( $search, $count = 100 );
	```

* Get a number of tweets from a user's list
	```php
	$TwitterWP->get_list_tweets( $user = '', $list = '', $count = 1 );
	```

* Get a number of tweets from a user's favorites
	```php
	$TwitterWP->get_favorite_tweets( $user = '', $count = 1 );
	```

* Access the user profile endpoint
	```php
	$TwitterWP->get_user( $user = '' );
	```

* Check your apps rate limit status
	```php
	$TwitterWP->rate_limit_status( $params = array() );
	```

* A generic helper for querying twitter via the bearer token.
	```php
	$TwitterWP->token_endpoint( $trail, $params = array() );
	```

* Returns your twitter app credentials (if they exist)
	```php
	$TwitterWP->get_app_creds();
	```

#### From example.php:
```php
<?php
/**
 * Example TwitterWP usage
 */
function twitterwp_example_test() {

	require_once( 'lib/TwitterWP.php' );

	// app credentials
	// (must be in this order)
	$app = array(
		'consumer_key'        => 'CONSUMER_KEY',
		'consumer_secret'     => 'CONSUMER_SECRET',
		'access_token'        => 'ACCESS_TOKEN',
		'access_token_secret' => 'ACCESS_TOKEN_SECRET',
	);
	// initiate your app
	$tw = TwitterWP::start( $app );

	// Also works:
	// $tw = TwitterWP::start( '0=CONSUMER_KEY&1=CONSUMER_SECRET&2=ACCESS_TOKEN&3=ACCESS_TOKEN_SECRET' );

	$user = 'jtsternberg';
	// bail here if the user doesn't exist
	if ( ! $tw->user_exists( $user ) ) {
		return;
	}

	echo '<div id="message" class="updated">';
	echo '<pre>'. print_r( $tw->get_tweets( $user, 5 ), true ) .'</pre>';
	echo '</div>';

	// Now let's check our app's rate limit status
	$rate_status = $tw->rate_limit_status();
	echo '<div id="message" class="updated">';

	if ( is_wp_error( $rate_status ) ) {
		$tw->show_wp_error( $rate_status );
	} else {
		echo '<pre>'. print_r( $rate_status, true ) .'</pre>';
	}

	echo '</div>';

}
add_action( 'all_admin_notices', 'twitterwp_example_test' );
```

#### Changelog

* 1.1.3

	* Added tweet_mode=>extended by default in order to get full text of tweet.
		- See: https://developer.twitter.com/en/docs/tweets/tweet-updates.html
		- And: https://www.drupal.org/project/tweet_feed/issues/2861466

* 1.1.2
	* Allow additional args to be passed to Twitter API through methods (e.g. `max_id`: https://dev.twitter.com/rest/public/timelines).

* 1.1.1
	* Replace `esc_url` with `esc_url_raw` so query parameter values are not converted.

* 1.1.0
	* added filters for every url: tweets_url(), list_tweets_url(), favorites_url(), user_url(). Props [@danstefancu](https://github.com/danstefancu), [#3](https://github.com/jtsternberg/TwitterWP/pull/3).
	* added consistent filter for search_url(). Props [@danstefancu](https://github.com/danstefancu), [#3](https://github.com/jtsternberg/TwitterWP/pull/3).
	* fixed get_user() filter for header args. Props [@danstefancu](https://github.com/danstefancu), [#3](https://github.com/jtsternberg/TwitterWP/pull/3).
	* updated inline documentation. Props [@danstefancu](https://github.com/danstefancu), [#3](https://github.com/jtsternberg/TwitterWP/pull/3).

* 1.0.3
	* get_favorite_tweets() Get a number of a user's favorite tweets
	* favorites_url() Request url for retrieving a user's favorite tweets

* 1.0.2
	* get_app_creds() Returns the credentials being used for TwitterWP
	* get_list_tweets() Get a number of tweets from a list
	* list_tweets_url() Request url for retrieving a user's list tweets

* 1.0.1
	* get_search_results() Get a number of search tweets
	* search_url() Request url for tweets search

* 1.0.0
	* Hello World!
