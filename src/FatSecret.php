<?php
/**
 * FatSecret Client Rest API Access
 *
 * @author  Andrei Surdu.
 * @website http://zerowp.com/
 *
 * @version 0.2
 */

namespace Awps;

class FatSecret {

	/**
	 * @var string $api_base The base URL to the API.
	 */
	protected $api_base = 'http://platform.fatsecret.com/rest/server.api';

	/**
	 * @var string $consumer_key The public key.
	 */
	protected $consumer_key;

	/**
	 * @var string $consumer_secret The secret key.
	 */
	protected $consumer_secret;

	/**
	 * @var array $params The URL parameters.
	 */
	protected $params = array();

	/**
	 * @var bool $partial_method
	 */
	protected $partial_method = false;

	public function __construct( $consumer_key, $consumer_secret ) {
		$this->consumer_key    = $consumer_key;
		$this->consumer_secret = $consumer_secret;

		$this->setFormat( 'json' );
		$this->setConsumerKey( $this->consumer_key );
		$this->setTimeStamp( current_time( 'timestamp' ) );

		$this->setParameter( 'oauth_signature_method', 'HMAC-SHA1' );
		$this->setParameter( 'oauth_version', '1.0' );
	}

	/**
	 * Set API response format
	 *
	 * @param string $value `json` or `xml`
	 *
	 * @return $this
	 */
	public function setFormat( $value ) {
		$this->setParameter( 'format', $value );

		return $this;
	}

	/**
	 * Set Consumer Key
	 *
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setConsumerKey( $value ) {
		$this->setParameter( 'oauth_consumer_key', $value );

		return $this;
	}

	/**
	 * Set Time Stamp
	 *
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setTimeStamp( $value ) {
		$this->setParameter( 'oauth_timestamp', $value );

		return $this;
	}

	/**
	 * Set Nonce
	 *
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setNonce( $value ) {
		$this->setParameter( 'oauth_nonce', $value );

		return $this;
	}

	/**
	 * Set Method
	 *
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setMethod( $value ) {
		$this->setParameter( 'method', $value );

		return $this;
	}

	/**
	 * Set Method
	 *
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setPartialMethod( $value ) {
		$this->partial_method = $value;

		return $this;
	}

	/**
	 * Set a generic URL parameter.
	 *
	 * @param string $parameter
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setParameter( $parameter, $value ) {
		$this->params[ $this->sanitizeKey( $parameter ) ] = $this->sanitizeKey( $value );

		return $this;
	}

	/**
	 * !!! Form "Premier" plan only
	 *
	 * @param $value
	 *
	 * @return $this
	 */
	public function setRegion( $value ) {
		$this->setParameter( 'region', $value );

		return $this;
	}

	/**
	 * !!! Form "Premier" plan only
	 *
	 * @param $value
	 *
	 * @return $this
	 */
	public function setLanguage( $value ) {
		$this->setParameter( 'language', $value );

		return $this;
	}

	/**
	 * 'food' or 'foods' access.
	 *
	 * @return $this
	 */
	public function food() {
		$this->setPartialMethod( 'food' );

		return $this;
	}

	/**
	 * 'recipe' or 'recipes' access.
	 *
	 * @return $this
	 */
	public function recipe() {
		$this->setPartialMethod( 'recipe' );

		return $this;
	}

	/**
	 * Search for element
	 *
	 * @param     $search_expression
	 * @param int $page_number
	 * @param int $max_results
	 *
	 * @return array|mixed|object|string
	 */
	public function search( $search_expression, $page_number = 0, $max_results = 10 ) {
		$this->setParameter( 'search_expression', $search_expression );
		$this->setParameter( 'page_number', $page_number );
		$this->setParameter( 'max_results', $max_results );

		if ( 'food' === $this->partial_method ) {
			$this->setMethod( 'foods.search' );
		}
		elseif ( 'recipe' === $this->partial_method ) {
			$this->setMethod( 'recipes.search' );
		}

		return $this->getRemoteJson();
	}

	/**
	 * Display a list of strings for autocomplete.
	 *
	 * !!! Form "Premier" plan only
	 *
	 * @param string $expression  Search keyword
	 * @param int    $max_results Between 1-10 inclusive.
	 *
	 * @return array|mixed|object|string
	 */
	public function autocomplete( $expression, $max_results = 10 ) {
		$this->setParameter( 'expression', $expression );
		$this->setParameter(
			'max_results',
			( $max_results > 0 && $max_results <= 10 ? $max_results : 10 ) // The range allowed by API is 1-10
		);

		if ( 'food' === $this->partial_method ) {
			$this->setMethod( 'foods.autocomplete' );
		}

		return $this->getRemoteJson();
	}

	/**
	 * Get element data.
	 *
	 * @param $id
	 *
	 * @return array|mixed|object|string
	 */
	public function get( $id ) {
		if ( 'food' === $this->partial_method ) {
			$this->setMethod( 'food.get' );
			$this->setParameter( 'food_id', $id );
		}
		elseif ( 'recipe' === $this->partial_method ) {
			$this->setMethod( 'recipe.get' );
			$this->setParameter( 'recipe_id', $id );
		}

		return $this->getRemoteJson();
	}

	/**
	 * Get the servings for a single food by ID.
	 *
	 * Each serving is stored in measurement key.
	 *
	 * @param int $food_id
	 *
	 * @return array
	 */
	public function getServings( $food_id ) {
		$result = $this->food()->get( absint( $food_id ) );

		$servings = array();

		if ( ! empty( $result['food']['servings']['serving'] ) ) {
			$serving = $result['food']['servings']['serving'];

			// The serving may be an array of servings.
			if ( ! empty( $serving[0] ) ) {
				foreach ( $serving as $serve ) {
					$servings[ $serve['measurement_description'] ] = $serve;
				}
			}

			// or a directly a single unique serving
			else {
				$servings[ $serving['measurement_description'] ] = $serving;
			}
		}

		if ( empty( $servings ) ) {
			return $servings;
		}

		// TODO: See bottom...
		$servings = $this->parseServings( $servings );
		$servings = $this->parseTheServings( $servings );

		return $servings;
	}

	protected function parseTheServings( $servings ) {
		$new_servings = array();

		if ( is_array( $servings ) ) {
			foreach ( $servings as $serving => $serving_value ) {
				if ( 'serving' === $serving && ! empty( $serving_value['serving_description'] ) ) {
					$new_servings[ $serving . '(' . $serving_value['serving_description'] . ')' ] = $serving_value;
				}
				elseif ( is_numeric( $serving ) ) {
					continue; // We need only valid units, not numbers
				}
				else {
					$new_servings[ $serving ] = $serving_value;
				}
			}

			$servings = $new_servings;
		}

		return $servings;
	}

	// TODO: Make this ....
	protected function parseServings( $servings ) {
		$fields_to_parse = array(
			'calories',
			'carbohydrate',
			'cholesterol',
			'fat',
			'fiber',
			'iron',
			//'measurement_description',
			'metric_serving_amount',
			//'metric_serving_unit',
			'number_of_units',
			'protein',
			'saturated_fat',
			//'serving_description',
			//'serving_id',
			//'serving_url',
			'sodium',
			'sugar',
			'vitamin_a',
			'vitamin_c',
		);

		// Get the array 'metric_serving_unit => measurement_description'
		$unique_metric_units = array_column( $servings, 'measurement_description', 'metric_serving_unit' );
		//update_option( 'test', $unique_metric_units );
		if ( ! empty( $unique_metric_units ) ) {
			foreach ( $unique_metric_units as $metric_unit => $serving ) {
				if ( ! empty( $metric_unit ) && ! empty( $servings[ $metric_unit ] ) ) {
					continue;
				}

				$the_serving = $servings[ $serving ];
				$amount      = $the_serving['metric_serving_amount'];

				foreach ( $fields_to_parse as $field ) {
					$servings[ $metric_unit ][ $field ] = round(
						floatval( $the_serving[ $field ] ) / floatval( $amount ),
						3
					);
				}

				$servings[ $metric_unit ]['number_of_units']         = 1;
				$servings[ $metric_unit ]['measurement_description'] = $metric_unit;
				$servings[ $metric_unit ]['metric_serving_unit']     = $metric_unit;
				$servings[ $metric_unit ]['serving_description']     = $metric_unit;
			}
		}

		return $servings;
	}

	public function getMeasurements( $food_id ) {
		$servings = $this->getServings( $food_id );

		if ( ! empty( $servings ) ) {
			return array_keys( $servings );
		}

		return array();
	}

	/**
	 * Get the remote response and parse it as JSON.
	 *
	 * @return array|mixed|object|string
	 */
	protected function getRemoteJson() {
		$response = wp_remote_get( $this->getFullUrl() );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			return "Error: $error_message";
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! empty( $body ) ) {
			$body = json_decode( $body, true );
		}

		return $body;
	}

	/**
	 * Sanitize a parameter key or value for the URL.
	 *
	 * @param string $key
	 *
	 * @return null|string|string[]
	 */
	protected function sanitizeKey( $key ) {
		return preg_replace( '/[^A-Za-z0-9_\-\.]/', '', $key );
	}

	/**
	 * Generate nonce.
	 *
	 * @return string
	 */
	protected function generateNonce() {
		return md5( uniqid() );
	}

	/**
	 * Generate hash signature for authentication.
	 *
	 * @return string
	 */
	protected function generateSignature() {
		$params        = $this->getParameters();
		$params_string = '';

		foreach ( $params as $param => $param_val ) {
			$params_string .= $param . '=' . $param_val . '&';
		}

		$params_string = rtrim( $params_string, '&' );

		$base = "GET&" . rawurlencode( $this->api_base ) . "&" . rawurlencode( $params_string );

		$signature = base64_encode( hash_hmac(
			'sha1',
			$base,
			$this->consumer_secret . "&",
			true
		) );

		return rawurlencode( $signature );
	}

	/**
	 * Get the fully qualified URL which includes the authentication hash.
	 *
	 * @return string
	 */
	public function getFullUrl() {
		$this->setNonce( $this->generateNonce() );

		return add_query_arg( 'oauth_signature', $this->generateSignature(), $this->getUrl() );
	}

	/**
	 * Get the formatted URL.
	 *
	 * @return string
	 */
	public function getUrl() {
		return add_query_arg( $this->getParameters(), $this->api_base );
	}

	/**
	 * Get the base URL.
	 *
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->api_base;
	}

	/**
	 * Get parameters.
	 *
	 * @return array
	 */
	public function getParameters() {
		$params = $this->params;

		ksort( $params );

		return $params;
	}

}
