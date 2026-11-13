<?php
/**
 * Plugin Name: CityWeather
 * Description: Display current weather for cities worldwide using the OpenWeather API.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Monselj1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cityweather
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CWWP_VERSION', '1.0.0' );
define( 'CWWP_FILE', __FILE__ );
define( 'CWWP_PATH', plugin_dir_path( __FILE__ ) );
define( 'CWWP_URL', plugin_dir_url( __FILE__ ) );

require_once CWWP_PATH . 'includes/admin.php';
require_once CWWP_PATH . 'includes/api.php';

/**
 * Preserve the API key when upgrading from the original Mi Clima plugin.
 */
function cwwp_activate_plugin() {
	if ( '' === (string) get_option( 'cwwp_api_key', '' ) ) {
		$legacy_api_key = (string) get_option( 'mi_clima_api_key', '' );

		if ( '' !== $legacy_api_key ) {
			update_option(
				'cwwp_api_key',
				sanitize_text_field( $legacy_api_key )
			);
		}
	}
}
register_activation_hook( CWWP_FILE, 'cwwp_activate_plugin' );


/**
 * Enqueue frontend assets only when the shortcode is used.
 */
function cwwp_enqueue_frontend_assets() {
	$css_path = CWWP_PATH . 'assets/css/style.css';
	$js_path  = CWWP_PATH . 'assets/js/script.js';

	wp_enqueue_style(
		'cityweather',
		CWWP_URL . 'assets/css/style.css',
		array(),
		file_exists( $css_path ) ? filemtime( $css_path ) : CWWP_VERSION
	);

	wp_enqueue_script(
		'cityweather',
		CWWP_URL . 'assets/js/script.js',
		array(),
		file_exists( $js_path ) ? filemtime( $js_path ) : CWWP_VERSION,
		true
	);

	wp_localize_script(
		'cityweather',
		'cityWeather',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'cwwp_weather_nonce' ),
			'iconBaseUrl' => trailingslashit( CWWP_URL . 'assets/icons' ),
			'i18n'        => array(
				'loading'          => __( 'Loading weather...', 'cityweather' ),
				'searching'        => __( 'Searching...', 'cityweather' ),
				'update'           => __( 'Update weather', 'cityweather' ),
				'connection'       => __( 'Connection error. Please try again.', 'cityweather' ),
				'invalid'          => __( 'Enter a city and a valid two-letter country code.', 'cityweather' ),
				'feelsLike'        => __( 'Feels like', 'cityweather' ),
				'humidity'         => __( 'Humidity', 'cityweather' ),
				'wind'             => __( 'Wind', 'cityweather' ),
				'pressure'         => __( 'Pressure', 'cityweather' ),
				'windDirection'    => __( 'Wind direction', 'cityweather' ),
				'updated'          => __( 'Updated', 'cityweather' ),
				'notAvailable'     => __( 'N/A', 'cityweather' ),
				'celsius'          => __( 'Celsius', 'cityweather' ),
				'fahrenheit'       => __( 'Fahrenheit', 'cityweather' ),
				'temperatureUnit'  => __( 'Temperature unit', 'cityweather' ),
				'currentWeather'   => __( 'Current weather', 'cityweather' ),
			),
		)
	);
}

/**
 * Render the weather search form.
 *
 * @return string
 */
function cwwp_weather_shortcode() {
	cwwp_enqueue_frontend_assets();

	$instance_id = wp_unique_id( 'cwwp-' );

	ob_start();
	?>
	<div class="cwwp-container">
		<form class="cwwp-form" data-cwwp-form="<?php echo esc_attr( $instance_id ); ?>">
			<div class="cwwp-field">
				<label for="<?php echo esc_attr( $instance_id ); ?>-city">
					<?php esc_html_e( 'City', 'cityweather' ); ?>
				</label>
				<input
					type="text"
					id="<?php echo esc_attr( $instance_id ); ?>-city"
					name="city"
					placeholder="<?php echo esc_attr__( 'e.g. London', 'cityweather' ); ?>"
					autocomplete="address-level2"
					required
				>
			</div>

			<div class="cwwp-field">
				<label for="<?php echo esc_attr( $instance_id ); ?>-country">
					<?php esc_html_e( 'Country code (2 letters)', 'cityweather' ); ?>
				</label>
				<input
					type="text"
					id="<?php echo esc_attr( $instance_id ); ?>-country"
					name="country"
					placeholder="<?php echo esc_attr__( 'e.g. GB', 'cityweather' ); ?>"
					maxlength="2"
					minlength="2"
					pattern="[A-Za-z]{2}"
					autocomplete="country"
					required
				>
			</div>

			<button type="submit">
				<?php esc_html_e( 'Get weather', 'cityweather' ); ?>
			</button>
		</form>

		<div class="cwwp-result" aria-live="polite" aria-atomic="true"></div>
	</div>
	<?php

	return ob_get_clean();
}
add_shortcode( 'cityweather', 'cwwp_weather_shortcode' );

/**
 * Add suggested privacy policy text.
 */
function cwwp_add_privacy_policy_content() {
	if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		$content = '<p>' .
			esc_html__(
				'CityWeather sends the city and country entered by a visitor to the website server. The server requests current weather data from OpenWeather using the site owner\'s API key. The plugin temporarily stores weather and geocoding responses in WordPress transients to reduce repeated API requests. A temporary, pseudonymized value derived from the visitor\'s IP address may be used for rate limiting. Review OpenWeather\'s privacy policy for information about its processing.',
				'cityweather'
			) .
			' <a href="https://openweather.co.uk/privacy-policy" target="_blank" rel="noopener noreferrer">OpenWeather privacy policy</a>.</p>';

		wp_add_privacy_policy_content(
			__( 'CityWeather', 'cityweather' ),
			wp_kses_post( $content )
		);
	}
}
add_action( 'admin_init', 'cwwp_add_privacy_policy_content' );