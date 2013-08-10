<?php
/**
 * Connect to Twitter's API 1.1 using WordPress APIs
 *
 * @author  Justin Sternberg <justin@dsgnwrks.pro>
 * @package TwitterWP
 * @version 1.0.0
 */

class TwitterWP {

	/**
	 * Get Twitter app credentials at https://dev.twitter.com
	 */
	private   $app           = array();
	protected $error_message = 'Could not access Twitter feed.';
	protected $url           = 'https://api.twitter.com/1.1/';
	protected $user;
	// A single instance of this class.
	private static $instance = null;

	/**
	 * Creates or returns an instance of this class.
	 * @return TwitterWP A single instance of this class.
	 * @since  1.0.0
	 */
	public static function start( $app = array() ) {

		if ( empty( self::$app ) ) {

			if ( $error = $this->app_setup_error( $app ) )
				return $error;

			self::$app = array_values( $app );
		}

		self::$instance = null == self::$instance ? new self : self::$instance;

		return self::$instance;
	}

	/**
	 * @DEV testing
	 */
	public function hello() {
		echo 'hello';
	}

	public function get_tweets( $user = '', $count = 1 ) {
		if ( $error = $this->app_setup_error() )
			return $error;

		$this->user = $user;

		$args = apply_filters( 'twitterwp_get_tweets', $this->header_args( array( 'count' => $count ) ) );
		$response = wp_remote_get( $this->tweets_url( $count ), $args );

		if( is_wp_error( $response ) )
		   return '<strong>ERROR:</strong> '. $response->get_error_message();

		return $this->return_data( $response, $error );
	}

	public function authenticate_user( $user = '' ) {
		if ( $error = $this->app_setup_error() )
			return $error;

		$this->user = $user;

		$args = apply_filters( 'twitterwp_authenticate_user', $this->header_args() );
		$response = wp_remote_get( $this->user_url(), $args );

		if ( is_wp_error( $response ) )
		   return false;

		$error = 'Could not access Twitter user.';
		return $this->return_data( $response, $error );
	}

	protected function return_data( $response, $error_message = '' ) {

		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body );

		if ( isset( $json->errors ) ) {

			$errors = new WP_Error( 'twitterwp_error', $error_message ? $error_message : $this->error_message );

			foreach ( $json->errors as $key => $error ) {

				$errors->add( 'twitterwp_error', '<strong>ERROR '. $error->code .':</strong> '. $error->message );
			}
			return $errors;
		}

		return $json;
	}

	protected function header_args( $args = array() ) {

		if ( !isset( $this->user ) || ! $this->user )
			return null;

		// Set our oauth data
		$defaults = array(
			'screen_name' => $this->user,
			'oauth_consumer_key' => self::$app[0],
			'oauth_nonce' => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_token' => self::$app[2],
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0'
		);

		$oauth = wp_parse_args( $args, $defaults );

		$base_info = $this->build_base( $this->base_url(), $oauth );
		$composite_key = self::$app[1] .'&'. self::$app[3];
		// create our oauth signature
		$oauth['oauth_signature'] = base64_encode( hash_hmac( 'sha1', $base_info, $composite_key, true ) );

		$auth_args = array(
			'sslverify' => false,
			'headers' => array(
				'Authorization' => 'OAuth '. $this->authorize_header( $oauth ),
				'Expect' => false,
				'Accept-Encoding' => false
			),
		);

		return $auth_args;
	}

	protected function build_base( $baseURI, $params ) {
		$base = array();
		ksort( $params );
		foreach( $params as $key => $value ){
			$base[] = $key .'='. rawurlencode( $value );
		}

		return 'GET&'. rawurlencode( $baseURI ) .'&'. rawurlencode( implode( '&', $base ) );

	}

	protected function authorize_header( $oauth ) {
		$header = '';
		$values = array();
		foreach( $oauth as $key => $value ) {
			if ( $key == 'screen_name' || $key == 'count' )
				continue;
			$values[] = $key .'="'. rawurlencode( $value ) .'"';
		}

		$header .= implode( ', ', $values );

		return $header;
	}

	public function api_url( $params = false, $trail = 'statuses/user_timeline.json' ) {

		// append trailing path
		$this->base_url = $this->url . $trail;
		// append query args
		return $params ? add_query_arg( $params, $this->base_url ) : $this->base_url;
	}

	protected function base_url() {
		// set it up
		if ( !isset( $this->base_url ) )
			$this->api_url();

		return $this->base_url;
	}

	protected function tweets_url( $count = 1 ) {
		return $this->api_url( array( 'screen_name' => $this->user, 'count' => $count ) );
	}

	protected function user_url() {
		return $this->api_url( array( 'screen_name' => $this->user ), 'users/lookup.json' );
	}

	protected function app_setup_error( $app = array() ) {
		if ( $this->app_creds( !empty( $app ) ? $app : self::$app )
			return false;

		return '<strong>ERROR:</strong> Missing Twitter App credentials.';
	}

	protected function app_creds( $app ) {
		$app = array_filter( (array) $app );
		if ( empty( $app ) || !is_array( $app ) || count( $app ) !== 4 )
			return false;
		return $app;
	}
}