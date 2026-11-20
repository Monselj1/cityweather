<?php
/**
 * Plugin Name: Climnexa - Weather Information
 * Description: Display current weather for cities worldwide using the OpenWeather API.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Monselj1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: climnexa
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CLIMNEXA_VERSION', '1.0.0' );
define( 'CLIMNEXA_FILE', __FILE__ );
define( 'CLIMNEXA_PATH', plugin_dir_path( __FILE__ ) );
define( 'CLIMNEXA_URL', plugin_dir_url( __FILE__ ) );

require_once CLIMNEXA_PATH . 'includes/admin.php';
require_once CLIMNEXA_PATH . 'includes/api.php';

/**
 * Enqueue frontend assets only when the shortcode is used.
 */
function climnexa_enqueue_frontend_assets() {
	$css_path = CLIMNEXA_PATH . 'assets/css/style.css';
	$js_path  = CLIMNEXA_PATH . 'assets/js/script.js';

	wp_enqueue_style(
		'climnexa',
		CLIMNEXA_URL . 'assets/css/style.css',
		array(),
		file_exists( $css_path ) ? filemtime( $css_path ) : CLIMNEXA_VERSION
	);

	wp_enqueue_script(
		'climnexa',
		CLIMNEXA_URL . 'assets/js/script.js',
		array(),
		file_exists( $js_path ) ? filemtime( $js_path ) : CLIMNEXA_VERSION,
		true
	);

	wp_localize_script(
		'climnexa',
		'climnexaWeather',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'climnexa_weather_nonce' ),
			'iconBaseUrl' => trailingslashit( CLIMNEXA_URL . 'assets/icons' ),
			'i18n'        => array(
				'loading'          => __( 'Loading weather...', 'climnexa' ),
				'searching'        => __( 'Searching...', 'climnexa' ),
				'update'           => __( 'Update weather', 'climnexa' ),
				'connection'       => __( 'Connection error. Please try again.', 'climnexa' ),
				'invalid'          => __( 'Enter a city and a valid two-letter country code.', 'climnexa' ),
				'feelsLike'        => __( 'Feels like', 'climnexa' ),
				'humidity'         => __( 'Humidity', 'climnexa' ),
				'wind'             => __( 'Wind', 'climnexa' ),
				'pressure'         => __( 'Pressure', 'climnexa' ),
				'windDirection'    => __( 'Wind direction', 'climnexa' ),
				'updated'          => __( 'Updated', 'climnexa' ),
				'notAvailable'     => __( 'N/A', 'climnexa' ),
				'celsius'          => __( 'Celsius', 'climnexa' ),
				'fahrenheit'       => __( 'Fahrenheit', 'climnexa' ),
				'temperatureUnit'  => __( 'Temperature unit', 'climnexa' ),
				'currentWeather'   => __( 'Current weather', 'climnexa' ),
			),
		)
	);
}

/**
 * Render the weather search form.
 *
 * @return string
 */
function climnexa_weather_shortcode() {
	climnexa_enqueue_frontend_assets();

	$instance_id = wp_unique_id( 'climnexa-' );

	ob_start();
	?>
	<div class="climnexa-container">
		<form class="climnexa-form" data-climnexa-form="<?php echo esc_attr( $instance_id ); ?>">
			<div class="climnexa-field">
				<label for="<?php echo esc_attr( $instance_id ); ?>-city">
					<?php esc_html_e( 'City', 'climnexa' ); ?>
				</label>
				<input
					type="text"
					id="<?php echo esc_attr( $instance_id ); ?>-city"
					name="city"
					placeholder="<?php echo esc_attr__( 'e.g. London', 'climnexa' ); ?>"
					autocomplete="address-level2"
					required
				>
			</div>

			<div class="climnexa-field">
				<label for="<?php echo esc_attr( $instance_id ); ?>-country">
					<?php esc_html_e( 'Country code (2 letters)', 'climnexa' ); ?>
				</label>
				<input
					type="text"
					id="<?php echo esc_attr( $instance_id ); ?>-country"
					name="country"
					placeholder="<?php echo esc_attr__( 'e.g. GB', 'climnexa' ); ?>"
					maxlength="2"
					minlength="2"
					pattern="[A-Za-z]{2}"
					autocomplete="country"
					required
				>
			</div>

			<button type="submit">
				<?php esc_html_e( 'Get weather', 'climnexa' ); ?>
			</button>
		</form>

		<div class="climnexa-result" aria-live="polite" aria-atomic="true"></div>
	</div>
	<?php

	return ob_get_clean();
}
add_shortcode( 'climnexa', 'climnexa_weather_shortcode' );

/**
 * Add suggested privacy policy text.
 */
function climnexa_add_privacy_policy_content() {
	if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		$content = '<p>' .
			esc_html__(
				'Climnexa sends the city and country entered by a visitor to the website server. The server requests current weather data from OpenWeather using the site owner\'s API key. The plugin temporarily stores weather and geocoding responses in WordPress transients to reduce repeated API requests. A temporary, pseudonymized value derived from the visitor\'s IP address may be used for rate limiting. Review OpenWeather\'s privacy policy for information about its processing.',
				'climnexa'
			) .
			' <a href="https://openweather.co.uk/privacy-policy" target="_blank" rel="noopener noreferrer">OpenWeather privacy policy</a>.</p>';

		wp_add_privacy_policy_content(
			__( 'Climnexa', 'climnexa' ),
			wp_kses_post( $content )
		);
	}
}
add_action( 'admin_init', 'climnexa_add_privacy_policy_content' );