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
		'climnexa',
		CWWP_URL . 'assets/css/style.css',
		array(),
		file_exists( $css_path ) ? filemtime( $css_path ) : CWWP_VERSION
	);

	wp_enqueue_script(
		'climnexa',
		CWWP_URL . 'assets/js/script.js',
		array(),
		file_exists( $js_path ) ? filemtime( $js_path ) : CWWP_VERSION,
		true
	);

	wp_localize_script(
		'climnexa',
		'cityWeather',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'cwwp_weather_nonce' ),
			'iconBaseUrl' => trailingslashit( CWWP_URL . 'assets/icons' ),
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
function cwwp_weather_shortcode() {
	cwwp_enqueue_frontend_assets();

	$instance_id = wp_unique_id( 'cwwp-' );

	ob_start();
	?>
	<div class="cwwp-container">
		<form class="cwwp-form" data-cwwp-form="<?php echo esc_attr( $instance_id ); ?>">
			<div class="cwwp-field">
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

			<div class="cwwp-field">
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

		<div class="cwwp-result" aria-live="polite" aria-atomic="true"></div>
	</div>
	<?php

	return ob_get_clean();
}
add_shortcode( 'climnexa', 'cwwp_weather_shortcode' );

/**
 * Add suggested privacy policy text.
 */
function cwwp_add_privacy_policy_content() {
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
add_action( 'admin_init', 'cwwp_add_privacy_policy_content' );
