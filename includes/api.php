<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send a safe GET request to OpenWeather and decode its JSON response.
 *
 * @param string $url API URL.
 * @return array|WP_Error
 */
function cwwp_api_request( $url ) {
	$response = wp_safe_remote_get(
		$url,
		array(
			'timeout'             => 12,
			'redirection'         => 2,
			'limit_response_size' => 1024 * 100,
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'cwwp_connection_error',
			__( 'Could not connect to OpenWeather. Please try again later.', 'cityweather' )
		);
	}

	$status_code = (int) wp_remote_retrieve_response_code( $response );
	$body        = wp_remote_retrieve_body( $response );
	$data        = json_decode( $body, true );

	if ( 200 !== $status_code ) {
		$message = __( 'OpenWeather could not complete the request. Check your API key and account status.', 'cityweather' );

		if ( 401 === $status_code ) {
			$message = __( 'The OpenWeather API key was not accepted. Check the key in plugin settings.', 'cityweather' );
		}

		return new WP_Error(
			'cwwp_api_error',
			$message,
			array( 'status' => $status_code )
		);
	}

	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
		return new WP_Error(
			'cwwp_invalid_response',
			__( 'OpenWeather returned an invalid response.', 'cityweather' )
		);
	}

	return $data;
}

/**
 * Get coordinates for a city and country code.
 *
 * @param string $city City name.
 * @param string $country Two-letter country code.
 * @return array|WP_Error
 */
function cwwp_get_coordinates( $city, $country ) {
	$api_key = (string) get_option( 'cwwp_api_key', '' );

	if ( '' === $api_key ) {
		return new WP_Error(
			'cwwp_missing_api_key',
			__( 'The site administrator needs to add an OpenWeather API key in Settings > CityWeather.', 'cityweather' )
		);
	}

	$city    = sanitize_text_field( $city );
	$country = strtoupper( sanitize_text_field( $country ) );

	if (
		'' === $city ||
		strlen( $city ) > 200 ||
		! preg_match( '/^[A-Z]{2}$/', $country )
	) {
		return new WP_Error(
			'cwwp_invalid_location',
			__( 'Enter a valid city name and a two-letter country code.', 'cityweather' )
		);
	}

	$cache_key   = 'cwwp_geo_' . md5( strtolower( $city . '|' . $country ) );
	$coordinates = get_transient( $cache_key );

	if (
		is_array( $coordinates ) &&
		isset( $coordinates['lat'], $coordinates['lon'], $coordinates['name'], $coordinates['country'] ) &&
		is_numeric( $coordinates['lat'] ) &&
		is_numeric( $coordinates['lon'] ) &&
		(float) $coordinates['lat'] >= -90 &&
		(float) $coordinates['lat'] <= 90 &&
		(float) $coordinates['lon'] >= -180 &&
		(float) $coordinates['lon'] <= 180
	) {
		return $coordinates;
	}

	$url = add_query_arg(
		array(
			'q'     => $city . ',' . $country,
			'limit' => 1,
			'appid' => $api_key,
		),
		'https://api.openweathermap.org/geo/1.0/direct'
	);

	$result = cwwp_api_request( $url );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if (
		empty( $result[0] ) ||
		! is_array( $result[0] ) ||
		! isset(
			$result[0]['lat'],
			$result[0]['lon'],
			$result[0]['name'],
			$result[0]['country']
		) ||
		! is_numeric( $result[0]['lat'] ) ||
		! is_numeric( $result[0]['lon'] ) ||
		(float) $result[0]['lat'] < -90 ||
		(float) $result[0]['lat'] > 90 ||
		(float) $result[0]['lon'] < -180 ||
		(float) $result[0]['lon'] > 180 ||
		! is_string( $result[0]['name'] ) ||
		! is_string( $result[0]['country'] )
	) {
		return new WP_Error(
			'cwwp_city_not_found',
			__( 'City not found. Check the city name and country code.', 'cityweather' )
		);
	}

	$coordinates = array(
		'lat'     => (float) $result[0]['lat'],
		'lon'     => (float) $result[0]['lon'],
		'name'    => sanitize_text_field( $result[0]['name'] ),
		'country' => sanitize_text_field( $result[0]['country'] ),
	);

	set_transient( $cache_key, $coordinates, 30 * DAY_IN_SECONDS );

	return $coordinates;
}

/**
 * Get current weather data.
 *
 * @param string $city City name.
 * @param string $country Two-letter country code.
 * @return array|WP_Error
 */
function cwwp_get_current_weather( $city, $country ) {
	$api_key = (string) get_option( 'cwwp_api_key', '' );

	if ( '' === $api_key ) {
		return new WP_Error(
			'cwwp_missing_api_key',
			__( 'The site administrator needs to add an OpenWeather API key in Settings > CityWeather.', 'cityweather' )
		);
	}

	$location = cwwp_get_coordinates( $city, $country );

	if ( is_wp_error( $location ) ) {
		return $location;
	}

	$cache_key = 'cwwp_weather_' . md5( $location['lat'] . '|' . $location['lon'] );
	$weather   = get_transient( $cache_key );

	if ( is_array( $weather ) ) {
		return $weather;
	}

	$url = add_query_arg(
		array(
			'lat'   => $location['lat'],
			'lon'   => $location['lon'],
			'units' => 'metric',
			'lang'  => 'en',
			'appid' => $api_key,
		),
		'https://api.openweathermap.org/data/2.5/weather'
	);

	$weather = cwwp_api_request( $url );

	if ( is_wp_error( $weather ) ) {
		return $weather;
	}

	if (
		! isset(
			$weather['main']['temp'],
			$weather['main']['feels_like'],
			$weather['main']['humidity'],
			$weather['main']['pressure'],
			$weather['weather'][0]['description'],
			$weather['weather'][0]['main'],
			$weather['weather'][0]['icon'],
			$weather['name'],
			$weather['sys']['country'],
			$weather['wind']['speed']
		) ||
		! is_array( $weather['weather'][0] ) ||
		! is_numeric( $weather['main']['temp'] ) ||
		! is_numeric( $weather['main']['feels_like'] ) ||
		! is_numeric( $weather['main']['humidity'] ) ||
		! is_numeric( $weather['main']['pressure'] ) ||
		! is_numeric( $weather['wind']['speed'] ) ||
		(float) $weather['main']['humidity'] < 0 ||
		(float) $weather['main']['humidity'] > 100 ||
		(float) $weather['main']['pressure'] <= 0 ||
		(float) $weather['wind']['speed'] < 0 ||
		! is_string( $weather['weather'][0]['description'] ) ||
		! is_string( $weather['weather'][0]['main'] ) ||
		! is_string( $weather['weather'][0]['icon'] ) ||
		! is_string( $weather['name'] ) ||
		! is_string( $weather['sys']['country'] )
	) {
		return new WP_Error(
			'cwwp_incomplete_weather',
			__( 'The weather data received from OpenWeather was incomplete.', 'cityweather' )
		);
	}

	set_transient( $cache_key, $weather, 10 * MINUTE_IN_SECONDS );

	return $weather;
}

/**
 * Apply a basic transient-based rate limit.
 *
 * The anonymized IP-derived key is stored only as a short-lived transient.
 *
 * @return true|WP_Error
 */
function cwwp_check_rate_limit() {
	if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return new WP_Error(
			'cwwp_rate_limit_unavailable',
			__( 'The request could not be verified. Please try again.', 'cityweather' )
		);
	}

	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return new WP_Error(
			'cwwp_rate_limit_unavailable',
			__( 'The request could not be verified. Please try again.', 'cityweather' )
		);
	}

	$ip = wp_privacy_anonymize_ip( $ip );

	$transient_key = 'cwwp_rate_' . hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	$count         = (int) get_transient( $transient_key );

	if ( $count >= 10 ) {
		return new WP_Error(
			'cwwp_rate_limit',
			__( 'Too many requests. Please wait a few minutes and try again.', 'cityweather' )
		);
	}

	set_transient( $transient_key, $count + 1, 10 * MINUTE_IN_SECONDS );

	return true;
}

/**
 * Get an appropriate HTTP status code for a weather request error.
 *
 * @param WP_Error $error Request error.
 * @return int
 */
function cwwp_get_error_status( $error ) {
	$error_code = $error->get_error_code();

	if ( 'cwwp_invalid_location' === $error_code ) {
		return 400;
	}

	if ( 'cwwp_city_not_found' === $error_code ) {
		return 404;
	}

	if ( 'cwwp_missing_api_key' === $error_code ) {
		return 503;
	}

	return 502;
}

/**
 * Handle the public AJAX weather request.
 */
function cwwp_ajax_get_weather() {
	check_ajax_referer( 'cwwp_weather_nonce', 'nonce' );

	$city    = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
	$country = isset( $_POST['country'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country'] ) ) ) : '';

	if (
		'' === $city ||
		strlen( $city ) > 200 ||
		! preg_match( '/^[A-Z]{2}$/', $country )
	) {
		wp_send_json_error(
			array(
				'message' => __( 'Enter a valid city name and a two-letter country code.', 'cityweather' ),
			),
			400
		);
	}

	$rate_limit = cwwp_check_rate_limit();

	if ( is_wp_error( $rate_limit ) ) {
		wp_send_json_error(
			array( 'message' => $rate_limit->get_error_message() ),
			429
		);
	}

	$weather = cwwp_get_current_weather( $city, $country );

	if ( is_wp_error( $weather ) ) {
		wp_send_json_error(
			array( 'message' => $weather->get_error_message() ),
			cwwp_get_error_status( $weather )
		);
	}

	$weather_item = $weather['weather'][0];
	$timezone     = isset( $weather['timezone'] ) && is_numeric( $weather['timezone'] )
		? (int) $weather['timezone']
		: 0;
	$timestamp    = isset( $weather['dt'] ) && is_numeric( $weather['dt'] )
		? (int) $weather['dt']
		: time();

	$data = array(
		'city'           => sanitize_text_field( $weather['name'] ),
		'country'        => sanitize_text_field( $weather['sys']['country'] ),
		'temperature'    => round( (float) $weather['main']['temp'] ),
		'feels_like'     => round( (float) $weather['main']['feels_like'] ),
		'humidity'       => (int) $weather['main']['humidity'],
		'wind_speed'     => round( (float) $weather['wind']['speed'] * 3.6, 1 ),
		'wind_direction' => isset( $weather['wind']['deg'] ) && is_numeric( $weather['wind']['deg'] )
			? (int) $weather['wind']['deg']
			: null,
		'pressure'       => (int) $weather['main']['pressure'],
		'description'    => sanitize_text_field( $weather_item['description'] ),
		'icon'           => sanitize_text_field( $weather_item['icon'] ),
		'condition'      => sanitize_key( strtolower( $weather_item['main'] ) ),
		'local_time'     => gmdate( 'g:i A', $timestamp + $timezone ),
		'updated_at'     => gmdate( 'M j, Y g:i A', $timestamp + $timezone ),
	);

	wp_send_json_success( $data );
}
add_action( 'wp_ajax_cwwp_get_weather', 'cwwp_ajax_get_weather' );
add_action( 'wp_ajax_nopriv_cwwp_get_weather', 'cwwp_ajax_get_weather' );