<?php
/**
 * Connect to Twitter's API 1.1 using WordPress APIs
 *
 * @author  Justin Sternberg <justin@dsgnwrks.pro>
 * @package TwitterWP
 * @version 1.1.1
 */

if ( ! class_exists( 'TwitterWP' ) ) :

	class TwitterWP {

		protected $error_message     = 'Could not access Twitter feed.';
		protected $url               = 'https://api.twitter.com/1.1/';
		protected $base_url          = '';
		public static $user          = false;
		public static $result_type   = 'mixed';

		private static $bearer_token = false;
		// A single instance of this class.
		private static $instance     = null;
		// Get Twitter app credentials at https://dev.twitter.com
		private static $app          = array();

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @since  1.0.0
		 *
		 * @param array $app
		 *
		 * @return TwitterWP A single instance of this class.
		 */
		public static function start( $app = array() ) {

			if ( null == self::$instance ) {
				$check = new self( $app );
				if ( true !== $check ) {
					return $check;
				}

				self::$instance = $check;
			}

			return self::$instance;
		}

		/**
		 * Checks the apps credentials
		 *
		 * @since  1.0.0
		 *
		 * @param array $app App credentials
		 *
		 * @return TwitterWP true if app config seems good, wp_error if not
		 */
		private function __construct( $app = array() ) {
			if ( empty( self::$app ) ) {

				$app = self::app_setup_error( $app, true );
				if ( is_wp_error( $app ) ) {
					return $app;
				}

				self::$app = array_values( $app );
			}

			return true;
		}

		/**
		 * Checks if a user exists (a cheater method that avoids an api count)
		 *
		 * @since  1.0.0
		 *
		 * @param  string $user Twitter username
		 *
		 * @return boolean      User exists or not
		 */
		public static function user_exists( $user = '' ) {

			self::$user = $user ? $user : self::$user;

			$response =
				wp_remote_get( 'http://twitter.com/' . urlencode( self::$user ), array( 'sslverify' => false ) );

			if ( is_wp_error( $response ) || ! isset( $response['response']['code'] ) || $response['response']['code'] != 200 ) {
				return false;
			}

			return true;
		}

		/**
		 * Get a number of user's tweets
		 *
		 * @since  1.0.0
		 *
		 * @param  string $user Twitter username
		 * @param  integer $count Number of tweets to return
		 *
		 * @return string|WP_Error Response or wp_error object
		 */
		public function get_tweets( $user = '', $count = 1 ) {
			if ( $error = self::app_setup_error() ) {
				return $error;
			}

			self::$user = $user ? $user : self::$user;

			$args = apply_filters( 'twitterwp_get_tweets', $this->header_args( '', array( 'count' => $count ) ) );
			$response = wp_remote_get( $this->tweets_url( $count ), $args );

			if ( is_wp_error( $response ) ) {
				return '<strong>ERROR:</strong> ' . $response->get_error_message();
			}

			return $this->return_data( $response, $error );
		}

		/**
		 * Get a number of search tweets
		 *
		 * @since  1.0.1
		 *
		 * @param  string|array $search Search query, can be string or array
		 * @param  integer $count Number of tweets to return
		 *
		 * @return string|WP_Error    Response or wp_error object
		 */
		public function get_search_results( $search, $count = 100 ) {
			if ( $error = self::app_setup_error() ) {
				return $error;
			}

			$args = apply_filters( 'twitterwp_get_search_results', $this->header_args( '', array( 'count' => $count ) ) );
			$response = wp_remote_get( $this->search_url( $search, $count ), $args );

			if ( is_wp_error( $response ) ) {
				return '<strong>ERROR:</strong> ' . $response->get_error_message();
			}

			return $this->return_data( $response, $error );
		}

		/**
		 * Get a number of tweets from a list
		 *
		 * @since  1.0.2
		 *
		 * @param  string $user Twitter username
		 * @param  string $list Search query, can be string or array
		 * @param  integer $count Number of tweets to return
		 *
		 * @return string|WP_Error Response or wp_error object
		 */
		public function get_list_tweets( $user, $list, $count = 100 ) {
			if ( $error = self::app_setup_error() ) {
				return $error;
			}

			self::$user = $user ? $user : self::$user;

			$args = apply_filters( 'twitterwp_get_list_tweets', $this->header_args( '', array( 'count' => $count ) ) );
			$response = wp_remote_get( $this->list_tweets_url( $list, $count ), $args );

			if ( is_wp_error( $response ) ) {
				return '<strong>ERROR:</strong> ' . $response->get_error_message();
			}

			return $this->return_data( $response, $error );
		}

		/**
		 * Get a number of user's favorite tweets
		 *
		 * @since  1.0.3
		 *
		 * @param  string $user Twitter username
		 * @param  integer $count Number of tweets to return
		 *
		 * @return string|WP_Error Response or wp_error object
		 */
		public function get_favorite_tweets( $user = '', $count = 1 ) {
			if ( $error = self::app_setup_error() ) {
				return $error;
			}

			self::$user = $user ? $user : self::$user;

			$args = apply_filters( 'twitterwp_get_favorite_tweets', $this->header_args( '', array( 'count' => $count ) ) );
			$response = wp_remote_get( $this->favorites_url( $count ), $args );

			if ( is_wp_error( $response ) ) {
				return '<strong>ERROR:</strong> ' . $response->get_error_message();
			}

			return $this->return_data( $response, $error );
		}

		/**
		 * Access the user profile endpoint
		 *
		 * @since  1.0.0
		 *
		 * @param  string $user Twitter username
		 *
		 * @return string|WP_Error Response or wp_error object
		 */
		public function get_user( $user = '' ) {
			if ( $error = self::app_setup_error() ) {
				return $error;
			}

			self::$user = $user ? $user : self::$user;

			if ( ! self::$user ) {
				return new WP_Error( 'twitterwp_error', __( 'ERROR: You need to provide a user.' ) );
			}

			$args = apply_filters( 'twitterwp_get_user', $this->header_args( 'oauth' ) );
			$response = wp_remote_get( $this->user_url(), $args );

			if ( is_wp_error( $response ) ) {
				return '<strong>ERROR:</strong> ' . $response->get_error_message();
			}

			return $this->return_data( $response, $error );
		}

		/**
		 * Check your apps rate limit status
		 *
		 * @since  1.0.0
		 *
		 * @param  array $params 'resources' is a list of services to check
		 *
		 * @return array          Array of Status objects
		 */
		public function rate_limit_status( $params = array() ) {
			$params = wp_parse_args( $params, array(
				'resources' => 'help,users,search,statuses,friends,trends,application',
			) );
			$status = $this->token_endpoint( 'application/rate_limit_status.json', $params );

			return $status;

		}

		/**
		 * A generic helper for querying twitter via the bearer token.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $trail Endpoint trail after the main api url
		 * @param  array $params Parameters to pass to api_url
		 *
		 * @return string|WP_Error Response or wp_error object
		 */
		public function token_endpoint( $trail, $params = array() ) {
			if ( $error = self::app_setup_error() ) {
				return $error;
			}

			$args = $this->header_args();
			if ( is_wp_error( $args ) ) {
				return $args;
			}

			$url = $this->api_url( $params, $trail );
			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			return $this->return_data( $response, 'Could not access Twitter data.' );
		}

		/**
		 * Retrieve the bearer token from the site's option if it exists
		 *
		 * @since  1.0.0
		 * @return string|WP_Error Successful token or wp_error object
		 */
		public function get_token() {

			if ( $error = self::app_setup_error() ) {
				return $error;
			}

			if ( self::$bearer_token ) {
				return self::$bearer_token;
			}

			if ( $token = get_option( 'twitterwptoken' ) ) {
				self::$bearer_token = $token;

				return self::$bearer_token;
			}

			return $this->api_bearer_token();
		}

		/**
		 * Retrieve the bearer token for accessing certain endpoints
		 *
		 * @since  1.0.0
		 * @return string|WP_Error Successful token or wp_error object
		 */
		public function api_bearer_token() {
			if ( $error = self::app_setup_error() ) {
				return $error;
			}

			$response = wp_remote_post(
				'https://api.twitter.com/oauth2/token',
				$this->header_args( 'Basic' )
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body = $this->return_data( $response, 'Could not retrieve token.' );
			if ( is_wp_error( $body ) ) {
				return $body;
			}

			if ( ! isset( $body->access_token ) ) {
				return new WP_Error( 'twitterwp_error', __( 'ERROR: Could not retrieve bearer token.' ) );
			}

			self::$bearer_token = $body->access_token;
			update_option( 'twitterwptoken', self::$bearer_token );

			return self::$bearer_token;
		}

		/**
		 * Builds our request's header based on the Authentication type
		 *
		 * @since  1.0.0
		 *
		 * @param  string $auth Authentication type
		 * @param  array $header_args Optional additional arguments
		 *
		 * @return array               Header arguments array
		 */
		protected function header_args( $auth = 'bearer', $header_args = array() ) {

			if ( 'oauth' == strtolower( $auth ) ) {
				return $this->header_args_ouath( $header_args );
			} elseif ( 'basic' == strtolower( $auth ) ) {
				return $this->header_args_basic();
			}

			$token = $this->get_token();

			if ( is_wp_error( $token ) ) {
				return $token;
			}

			$header_args = array(
				'sslverify' => false,
				'headers'   => array(
					'Authorization' => 'Bearer ' . $token,
				),
			);

			return $header_args;
		}

		/**
		 * Builds request's 'basic' authentication arguments
		 *
		 * @since  1.0.0
		 *
		 * @return array       Request arguments array
		 */
		protected function header_args_basic() {
			return array(
				'sslverify' => false,
				'headers'   => array(
					'Authorization' => 'Basic ' . base64_encode( urlencode( self::$app[0] ) . ':' . urlencode( self::$app[1] ) ),
					'Content-Type'  => 'application/x-www-form-urlencoded;charset=UTF-8',
				),
				'body'      => array(
					'grant_type' => 'client_credentials',
				),
			);
		}

		/**
		 * Builds request's 'OAuth' authentication arguments
		 *
		 * @since  1.0.0
		 *
		 * @param  array $header_args Optional additional arguments
		 *
		 * @return array       Request arguments array
		 */
		protected function header_args_ouath( $header_args = array() ) {

			// Set our oauth data
			$oauth = wp_parse_args( $header_args, array(
				'oauth_consumer_key' => self::$app[0],
				'oauth_nonce' => time(),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_token' => self::$app[2],
				'oauth_timestamp' => time(),
				'oauth_version' => '1.0',
			) );

			// add our screen_name to the parameters
			if ( isset( self::$user ) && self::$user ) {
				$oauth['screen_name'] = self::$user;
			}

			// create our unique oauth signature
			$oauth['oauth_signature'] = $this->oauth_signature( $oauth );

			return array(
				'sslverify' => false,
				'headers'   => array(
					'Authorization'   => 'OAuth ' . $this->authorize_header( $oauth ),
					'Expect'          => false,
					'Accept-Encoding' => false,
					'Content-Type'    => 'application/x-www-form-urlencoded;charset=UTF-8',
				),
			);
		}

		/**
		 * Creates an oauth signature for the api call.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $params Header arguments array
		 *
		 * @return string         Unique Oauth signature
		 */
		protected function oauth_signature( $params ) {
			$base = array();
			ksort( $params );
			foreach ( $params as $key => $value ) {
				$base[] = $key . '=' . $value;
			}

			$data =
				'GET&' . rawurlencode( $this->base_url() ) . '&' . rawurlencode( implode( '&', $base ) );

			$composite_key = rawurlencode( self::$app[1] ) . '&' . rawurlencode( self::$app[3] );

			return base64_encode( hash_hmac( 'sha1', $data, $composite_key, true ) );
		}

		/**
		 * Creates a string out of the header arguments array
		 *
		 * @since  1.0.0
		 *
		 * @param  array $params Header arguments array
		 *
		 * @return string         Header arguments array in string format
		 */
		protected function authorize_header( $params ) {
			$header = '';
			$values = array();
			ksort( $params );
			foreach ( $params as $key => $value ) {
				if ( 'screen_name' == $key || 'count' == $key ) {
					continue;
				}
				$values[] = $key . '="' . rawurlencode( $value ) . '"';
			}

			$header .= implode( ', ', $values );

			return $header;
		}

		/**
		 * Gets the api url and appends endpoint trail and query args
		 *
		 * @since  1.0.0
		 *
		 * @param  array $params Query arguments
		 * @param  string $trail Endpoint Trail
		 *
		 * @return string         Url for request
		 */
		public function api_url( $params = array(), $trail = 'statuses/user_timeline.json' ) {

			// append trailing path
			$this->base_url = $this->url . $trail;

			// append query args
			return ! empty( $params ) ? esc_url_raw( add_query_arg( $params, $this->base_url ) ) : $this->base_url;
		}

		/**
		 * Gets the base api url or creates one
		 *
		 * @since  1.0.0
		 * @return string base api url
		 */
		protected function base_url() {
			// set it up
			if ( ! isset( $this->base_url ) ) {
				$this->api_url();
			}

			return $this->base_url;
		}

		/**
		 * Request url for retrieving a user's tweets
		 *
		 * @since  1.0.0
		 *
		 * @param  integer $count Number of tweets to return
		 *
		 * @return string         Endpoint url for request
		 */
		protected function tweets_url( $count = 1 ) {
			$this->base_url = $this->api_url();

			$params = apply_filters( 'twitterwp_tweets_url',
				array(
					 'screen_name' => self::$user,
					 'count'       => $count,
				),
				$count,
				self::$user
			);

			return $this->api_url( $params );
		}

		/**
		 * Request url for retrieving a user's list tweets
		 *
		 * @since  1.0.0
		 *
		 * @param string $list
		 * @param  integer $count Number of tweets to return
		 *
		 * @return string         Endpoint url for request
		 */
		protected function list_tweets_url( $list, $count = 1 ) {
			$this->base_url = $this->api_url();

			$params = apply_filters( 'twitterwp_list_tweets_url',
				array(
					 'slug'              => $list,
					 'owner_screen_name' => self::$user,
					 'count'             => $count,
				),
				$count,
				self::$user,
				$list
			);

			return $this->api_url( $params, 'lists/statuses.json' );
		}

		/**
		 * Request url for retrieving a user's favorite tweets
		 *
		 * @since  1.0.3
		 *
		 * @param  integer $count Number of tweets to return
		 *
		 * @return string
		 */
		protected function favorites_url( $count = 1 ) {
			$this->base_url = $this->api_url();

			$params = apply_filters( 'twitterwp_favorites_url',
				array(
					 'screen_name' => self::$user,
					 'count'       => $count,
				),
				$count,
				self::$user
			);

			return $this->api_url( $params, 'favorites/list.json' );
		}

		/**
		 * Request url for tweets search
		 *
		 * @since  1.0.1
		 *
		 * @param  string|array $search Search query, can be string or array
		 * @param  integer $count Number of tweets to return
		 *
		 * @return string               Endpoint url for request
		 */
		protected function search_url( $search, $count = 100 ) {
			$this->base_url = $this->api_url();
			$tags_array = array();
			if ( is_array( $search ) ) {
				foreach ( $search as $term ) {
					$tags_array[] = trim( $term );
				}
				$query = urlencode( implode( ' ', $tags_array ) );
			} elseif ( is_string( $search ) ) {
				$query = urlencode( $search );
			} else {
				return false;
			}

			$params = apply_filters( 'twitterwp_search_url',
				array(
					 'q'           => $query,
					 'result_type' => self::$result_type,
					 'count'       => absint( $count ),
				),
				$count,
				$query
			);

			return $this->api_url( $params, 'search/tweets.json' );
		}

		/**
		 * Request url for retrieving a user's profile
		 *
		 * @since  1.0.0
		 *
		 * @return string         Endpoint url for request
		 */
		protected function user_url() {
			$this->base_url = $this->api_url();

			$params = apply_filters( 'twitterwp_user_url',
				array(
					 'screen_name' => self::$user,
				),
				self::$user
			);

			return $this->api_url( $params, 'users/lookup.json' );
		}

		/**
		 * Parse's a http response for errors
		 *
		 * @since  1.0.0
		 *
		 * @param  array $response request's response array
		 * @param  string $error_message fallback error message
		 *
		 * @return string|WP_Error     JSON encoded response or error
		 */
		protected function return_data( $response, $error_message = '' ) {

			$body = wp_remote_retrieve_body( $response );
			$json = json_decode( $body );

			if ( isset( $json->errors ) ) {

				$errors = new WP_Error( 'twitterwp_error', $error_message ? $error_message : $this->error_message );

				$addictional_info = '';
				if ( isset( $response['response']['message'] ) ) {
					$code = isset( $response['response']['code'] ) ? $response['response']['code'] . ': ' : '';
					$addictional_info = ' (' . $code . $response['response']['message'] . ')';
				}
				/** @noinspection PhpUnusedLocalVariableInspection */
				foreach ( $json->errors as $key => $error ) {

					$errors->add( 'twitterwp_error', '<strong>ERROR ' . $error->code . ':</strong> ' . $error->message . $addictional_info );
				}

				return $errors;
			}

			return $json;
		}

		/**
		 * Check if app credentials exist and are in the right format
		 *
		 * @since  1.0.0
		 *
		 * @param  array $app App credentials
		 * @param  boolean $return Whether to return app credentials or boolean
		 *
		 * @return array|boolean|WP_Error  App credentials, boolean, or wp_error
		 */
		public static function app_setup_error( $app = array(), $return = false ) {
			$app = ! empty( $app ) ? $app : self::$app;
			if ( $to_return = self::app_creds( $app ) ) {
				return $return ? $to_return : false;
			}

			return new WP_Error( 'twitterwp_error', __( 'ERROR: Missing Twitter App credentials.' ) );
		}

		/**
		 * Check if we have proper app credentials
		 *
		 * @since  1.0.0
		 *
		 * @param  array|string $app App credentials
		 *
		 * @return array             App credentials array
		 */
		public static function app_creds( $app ) {

			if ( is_array( $app ) ) {
				$app_arr =& $app;
			} else {
				wp_parse_str( $app, $app_arr );
			}

			$app_arr = array_filter( (array) $app_arr );
			if ( empty( $app_arr ) || ! is_array( $app_arr ) || 4 !== count( $app_arr ) ) {
				return false;
			}

			return $app_arr;
		}

		/**
		 * Returns the credentials being used for TwitterWP
		 *
		 * @since  1.0.2
		 * @return array App credentials (or empty) array
		 */
		public function get_app_creds() {
			return self::$app;
		}

		/**
		 * Helper method to display our wp_error objects
		 *
		 * @since  1.0.0
		 *
		 * @param  wp_error $error The wp_error object to display
		 * @param  boolean $echo Whether to echo or only return error
		 *
		 * @return string          wp_error messages
		 */
		public static function show_wp_error( $error, $echo = true ) {
			if ( ! is_wp_error( $error ) ) {
				return '';
			}

			$errors =
				'<p class="error">' . implode( '<br/>', $error->get_error_messages( 'twitterwp_error' ) ) . '</p>';

			if ( $echo ) {
				echo $errors;
			}

			return $errors;
		}

	}

endif; // end class_exists check
