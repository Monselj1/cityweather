# CityWeather

A lightweight WordPress plugin that displays current weather conditions for cities worldwide using the OpenWeather API.

CityWeather provides a clean, responsive weather display that allows visitors to search for a city and view its current weather information.

## Features

- Search weather conditions by city and country.
- Display current temperature in Celsius or Fahrenheit.
- Show weather conditions and weather icons.
- Display humidity, wind speed, wind direction, and atmospheric pressure.
- Responsive design for desktop and mobile devices.
- Simple integration using a WordPress shortcode.
- Powered by the OpenWeather API.

## Requirements

- WordPress 6.0 or higher.
- PHP 7.4 or higher.
- An API key from [OpenWeather](https://openweathermap.org/api).

## Installation

1. Download or clone this repository.
2. Upload the `cityweather` folder to your WordPress installation's `wp-content/plugins/` directory.
3. Activate CityWeather from the WordPress Plugins menu.
4. Go to the plugin settings page.
5. Enter your OpenWeather API key and save the settings.

## OpenWeather API Setup

CityWeather requires an API key from OpenWeather to retrieve weather information.

1. Visit [OpenWeather](https://openweathermap.org/).
2. Create an account or sign in.
3. Generate an API key from your account.
4. Copy your API key.
5. Open the CityWeather settings page in WordPress.
6. Paste your API key and save the settings.

For more information, visit the [OpenWeather API documentation](https://openweathermap.org/api).

## Usage

After activating the plugin and configuring your API key, add the following shortcode to any WordPress page or post:

`[cityweather]`

Visitors will be able to enter a city and country to retrieve current weather conditions.

## License

CityWeather is licensed under the GNU General Public License v2 or later (GPL-2.0-or-later).

See [LICENSE.txt](LICENSE.txt) for the full license.

## Credits

Weather data provided by [OpenWeather](https://openweathermap.org/).