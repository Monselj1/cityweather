<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the plugin settings page.
 */
function cwwp_add_settings_page() {
	add_options_page(
		__( 'CityWeather Settings', 'cityweather' ),
		__( 'CityWeather', 'cityweather' ),
		'manage_options',
		'cityweather',
		'cwwp_render_settings_page'
	);
}
add_action( 'admin_menu', 'cwwp_add_settings_page' );

/**
 * Preserve the saved API key when the settings field is left blank.
 *
 * @param string $value Submitted API key.
 * @return string
 */
function cwwp_sanitize_api_key( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return (string) get_option( 'cwwp_api_key', '' );
	}

	return sanitize_text_field( $value );
}

/**
 * Register plugin settings.
 */
function cwwp_register_settings() {
	register_setting(
		'cwwp_settings_group',
		'cwwp_api_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'cwwp_sanitize_api_key',
			'default'           => '',
		)
	);

	add_settings_section(
		'cwwp_api_section',
		__( 'OpenWeather API settings', 'cityweather' ),
		'cwwp_render_api_section_description',
		'cityweather'
	);

	add_settings_field(
		'cwwp_api_key',
		__( 'API key', 'cityweather' ),
		'cwwp_render_api_key_field',
		'cityweather',
		'cwwp_api_section'
	);
}
add_action( 'admin_init', 'cwwp_register_settings' );

/**
 * Render the API section description.
 */
function cwwp_render_api_section_description() {
	echo '<p>' . esc_html__( 'Add your OpenWeather API key to enable weather searches.', 'cityweather' ) . '</p>';
}


/**
 * Render the API key field without printing the saved secret into the page.
 */
function cwwp_render_api_key_field() {
	$has_key = '' !== (string) get_option( 'cwwp_api_key', '' );
	?>
	<input
		type="password"
		name="cwwp_api_key"
		id="cwwp_api_key"
		value=""
		class="regular-text"
		autocomplete="new-password"
		placeholder="<?php echo esc_attr( $has_key ? __( 'API key saved. Enter a new key to replace it.', 'cityweather' ) : __( 'Enter your OpenWeather API key', 'cityweather' ) ); ?>"
	>
	<p class="description">
		<?php
		echo esc_html(
			$has_key
				? __( 'A key is currently saved. Leave this field blank to keep it unchanged.', 'cityweather' )
				: __( 'Enter your OpenWeather API key to enable weather searches.', 'cityweather' )
		);
		?>
	</p>
	<p class="description">
		<?php esc_html_e( 'Your API key is stored on your WordPress site and is never exposed to visitors in the browser.', 'cityweather' ); ?>
	</p>
	<?php
}

/**
 * Render the settings page.
 */
function cwwp_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'CityWeather', 'cityweather' ); ?></h1>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'cwwp_settings_group' );
			do_settings_sections( 'cityweather' );
			submit_button( __( 'Save settings', 'cityweather' ) );
			?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'How to get an OpenWeather API key', 'cityweather' ); ?></h2>
		<ol>
			<li>
				<?php esc_html_e( 'Create an account on', 'cityweather' ); ?>
				<a href="https://home.openweathermap.org/users/sign_up" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'OpenWeather', 'cityweather' ); ?>
				</a>.
			</li>
			<li>
				<?php esc_html_e( 'If you already have an account,', 'cityweather' ); ?>
				<a href="https://home.openweathermap.org/users/sign_in" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'sign in here', 'cityweather' ); ?>
				</a>.
			</li>
			<li>
				<?php esc_html_e( 'Open the', 'cityweather' ); ?>
				<a href="https://home.openweathermap.org/api_keys" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'API keys page', 'cityweather' ); ?>
				</a>
				<?php esc_html_e( 'to view or generate an API key.', 'cityweather' ); ?>
			</li>
			<li>
				<?php esc_html_e( 'Paste your key into the field above and click Save settings.', 'cityweather' ); ?>
			</li>
		</ol>
		<p>
			<strong><?php esc_html_e( 'Note:', 'cityweather' ); ?></strong>
			<?php esc_html_e( 'A newly created API key may take some time to become active. Check your OpenWeather account if requests continue to fail.', 'cityweather' ); ?>
		</p>

		<hr>

		<h2><?php esc_html_e( 'Display the weather form', 'cityweather' ); ?></h2>
		<p><?php esc_html_e( 'Add this shortcode to any page or post using the Shortcode block:', 'cityweather' ); ?></p>
		<p><code>[cityweather]</code></p>
	</div>
	<?php
}