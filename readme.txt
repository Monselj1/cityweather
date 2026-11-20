=== Climnexa - Weather Information ===
Contributors: Monselj1
Tags: weather, weather-widget, openweather, current-weather
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display current weather for cities worldwide using the OpenWeather API.

== Description ==

Display current weather conditions for cities worldwide on your WordPress site using data from the OpenWeather API.

Features:
* Current temperature with Celsius and Fahrenheit selection.
* Weather condition with custom weather icons.
* Feels-like temperature.
* Local time for the searched city.
* Humidity, wind speed, wind direction, and atmospheric pressure.
* Responsive design for desktop and mobile devices.
* Short-lived caching to reduce repeated API requests.
* Basic request rate limiting.

The plugin requires an API key from OpenWeather. Each site owner must provide their own key in Settings > Climnexa.

== Installation ==

1. Upload the `climnexa` folder to the `/wp-content/plugins/` directory, or install the ZIP through the WordPress Plugins screen.
2. Activate Climnexa.
3. Create an account at https://home.openweathermap.org/users/sign_up.
4. Generate or copy an API key at https://home.openweathermap.org/api_keys.
5. Go to Settings > Climnexa and save your API key.
6. Add the `[climnexa]` shortcode to a page or post using the Shortcode block.

== Frequently Asked Questions ==

= Do I need an OpenWeather account? =

Yes. You must create an OpenWeather account and enter your own API key in the plugin settings.

= Which shortcode should I use? =

Use `[climnexa]`.

= Can visitors choose between Celsius and Fahrenheit? =

Yes. Visitors can switch between Celsius and Fahrenheit using the temperature selector in the weather card.

= Why does my API key not work immediately? =

A newly created OpenWeather API key may take some time to become active. Check your OpenWeather account and API plan if requests continue to fail.

= Does the plugin send my API key to visitors? =

No. Weather requests are sent from your WordPress server. The API key is not included in the frontend JavaScript.

== Privacy ==

When a visitor submits a weather search, the city and country code are sent to the site's server and then to OpenWeather to retrieve weather data. The plugin temporarily caches geocoding results and current weather responses in WordPress transients.

A temporary pseudonymized value derived from the visitor's IP address is used for basic request rate limiting. This value is stored in a short-lived WordPress transient.

Climnexa is an independent plugin and is not affiliated with or endorsed by OpenWeather.

Geocoding results are cached for 30 days, current weather responses for 10 minutes, and rate-limiting data for 10 minutes. These temporary records expire automatically.

OpenWeather is a third-party service. Its processing is subject to its own terms and privacy policy:
https://openweather.co.uk/privacy-policy

== External Services ==

This plugin connects to OpenWeather to retrieve geocoding and current weather information.

The site owner must provide an OpenWeather API key. When a visitor searches for a city, the plugin sends the city name, country code, and the site owner's API key from the WordPress server to the following OpenWeather endpoints:

* https://api.openweathermap.org/geo/1.0/direct
* https://api.openweathermap.org/data/2.5/weather

The plugin temporarily caches geocoding and weather responses to reduce repeated requests.

OpenWeather's terms and privacy policy apply:
https://openweathermap.org/terms
https://openweather.co.uk/privacy-policy

== Changelog ==

= 1.0.0 =
* Initial public release.