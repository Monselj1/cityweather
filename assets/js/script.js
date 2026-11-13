document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    if (
        typeof cityWeather === 'undefined' ||
        !cityWeather.i18n
    ) {
        return;
    }

    const i18n = cityWeather.i18n;

    /**
     * Get a translated string with a fallback.
     *
     * @param {string} key      Translation key.
     * @param {string} fallback Fallback text.
     * @return {string}
     */
    function getText(key, fallback) {
        return typeof i18n[key] === 'string' && i18n[key] !== ''
            ? i18n[key]
            : fallback;
    }

    /**
     * Create a DOM element safely.
     *
     * @param {string} tag       HTML tag.
     * @param {string} className CSS class.
     * @param {string} text      Text content.
     * @return {HTMLElement}
     */
    function element(tag, className, text) {
        const node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (typeof text === 'string') {
            node.textContent = text;
        }

        return node;
    }

    /**
     * Convert Celsius to the selected temperature unit.
     *
     * @param {number|string} celsius Temperature in Celsius.
     * @param {string} unit           C or F.
     * @return {number|string}
     */
    function convertTemperature(celsius, unit) {
        const value = Number(celsius);

        if (!Number.isFinite(value)) {
            return '--';
        }

        const temperature = unit === 'F'
            ? (value * 9 / 5) + 32
            : value;

        return Math.round(temperature);
    }

    /**
     * Get the wind direction from degrees.
     *
     * @param {number|string} degrees Wind direction in degrees.
     * @return {string}
     */
    function getWindDirection(degrees) {
        const value = Number(degrees);

        if (
            degrees === null ||
            typeof degrees === 'undefined' ||
            !Number.isFinite(value)
        ) {
            return getText('notAvailable', 'N/A');
        }

        const directions = [
            'N', 'NE', 'E', 'SE',
            'S', 'SW', 'W', 'NW'
        ];

        const normalizedDegrees = ((value % 360) + 360) % 360;

        return directions[Math.round(normalizedDegrees / 45) % 8];
    }

    /**
     * Return the appropriate local SVG for each weather condition.
     *
     * @param {string} condition   OpenWeather condition.
     * @param {string} description Weather description.
     * @return {string}
     */
    
function getWeatherIcon(condition, description) {
    const weatherCondition = (
        typeof condition === 'string' ? condition : ''
    ).toLowerCase();

    const weatherDescription = (
        typeof description === 'string' ? description : ''
    ).toLowerCase();

    if (weatherCondition === 'clear') {
        return 'soleado.svg';
    }

    if (weatherCondition === 'clouds') {
        if (weatherDescription.includes('few clouds')) {
            return 'parcialmente-nublado.svg';
        }

        return 'nublado.svg';
    }

    if (weatherCondition === 'drizzle') {
        return 'llovizna.svg';
    }

    if (weatherCondition === 'rain') {
        return 'lluvia.svg';
    }

    if (weatherCondition === 'thunderstorm') {
        return 'tormenta-electrica.svg';
    }

    if (weatherCondition === 'snow') {
        return 'nieve.svg';
    }

    const atmosphericConditions = [
        'mist',
        'fog',
        'haze',
        'smoke',
        'dust',
        'sand',
        'ash',
        'squall',
        'tornado',
        'atmosphere'
    ];

    if (atmosphericConditions.includes(weatherCondition)) {
        return 'niebla.svg';
    }

    return 'nublado.svg';
}


    /**
     * Create a temperature unit button.
     *
     * @param {string} unit   C or F.
     * @param {boolean} active Whether the button is active.
     * @return {HTMLButtonElement}
     */
    function createUnitButton(unit, active) {
        const isCelsius = unit === 'C';

        const button = element(
            'button',
            'cwwp-unit-button' + (active ? ' is-active' : ''),
            '°' + unit
        );

        button.type = 'button';

        button.setAttribute(
            'aria-pressed',
            active ? 'true' : 'false'
        );

        button.setAttribute(
            'aria-label',
            isCelsius
                ? getText('celsius', 'Celsius')
                : getText('fahrenheit', 'Fahrenheit')
        );

        return button;
    }

    /**
     * Render the weather information.
     *
     * @param {Object} weather Weather data.
     * @param {HTMLElement} result Result container.
     */
    function renderWeather(weather, result) {
        if (!weather || typeof weather !== 'object') {
            throw new Error(getText('connection', 'Unable to load weather data.'));
        }

        const card = element('section', 'cwwp-weather-card');

        const condition = (
            typeof weather.condition === 'string'
                ? weather.condition
                : 'clouds'
        ).toLowerCase();

        const theme = condition === 'clear' ? 'clear'
            : condition === 'rain' || condition === 'drizzle' ? 'rain'
            : condition === 'snow' ? 'snow'
            : condition === 'thunderstorm' ? 'storm'
            : condition === 'mist' ||
                condition === 'fog' ||
                condition === 'haze' ? 'mist'
            : 'clouds';

        card.classList.add('cwwp-' + theme);

        // Location and temperature selector.
        const top = element('div', 'cwwp-card-top');
        const locationGroup = element('div', 'cwwp-location-group');

        const city = typeof weather.city === 'string'
            ? weather.city
            : '';

        const country = typeof weather.country === 'string'
            ? weather.country
            : '';

        locationGroup.appendChild(
            element(
                'div',
                'cwwp-location',
                city + (country ? ', ' + country : '')
            )
        );

        const localTime = element(
            'time',
            'cwwp-local-time',
            typeof weather.local_time === 'string'
                ? weather.local_time
                : ''
        );

        locationGroup.appendChild(localTime);

        const unitSelector = element('div', 'cwwp-unit-selector');

        unitSelector.setAttribute('role', 'group');
        unitSelector.setAttribute(
            'aria-label',
            getText('temperatureUnit', 'Temperature unit')
        );

        const celsiusButton = createUnitButton('C', true);
        const fahrenheitButton = createUnitButton('F', false);

        unitSelector.appendChild(celsiusButton);
        unitSelector.appendChild(fahrenheitButton);

        top.appendChild(locationGroup);
        top.appendChild(unitSelector);

        card.appendChild(top);

        // Main temperature section.
        const bottom = element('div', 'cwwp-card-bottom');
        const temperatureGroup = element('div', 'cwwp-temperature-group');

        // Local weather SVG icon.
        const weatherIcon = document.createElement('img');

        weatherIcon.className = 'cwwp-weather-icon';
        weatherIcon.src = String(cityWeather.iconBaseUrl || '') +
            getWeatherIcon(weather.condition, weather.description);

        weatherIcon.alt = typeof weather.description === 'string'
            ? weather.description
            : getText('currentWeather', 'Current weather');

        weatherIcon.width = 110;
        weatherIcon.height = 110;
        weatherIcon.loading = 'eager';
        weatherIcon.decoding = 'async';

        temperatureGroup.appendChild(weatherIcon);

        // Temperature.
        const temperature = element(
            'div',
            'cwwp-temperature',
            convertTemperature(weather.temperature, 'C') + '°C'
        );

        temperatureGroup.appendChild(temperature);
        bottom.appendChild(temperatureGroup);

        // Weather description and feels-like temperature.
        const summary = element('div', 'cwwp-summary');

        const description = element(
            'div',
            'cwwp-description',
            typeof weather.description === 'string'
                ? weather.description
                : ''
        );

        const feelsLike = element(
            'div',
            'cwwp-feels-like',
            getText('feelsLike', 'Feels like') + ' ' +
            convertTemperature(weather.feels_like, 'C') + '°C'
        );

        summary.appendChild(description);
        summary.appendChild(feelsLike);

        bottom.appendChild(summary);
        card.appendChild(bottom);

        // Additional weather information.
        const details = element('div', 'cwwp-details');

        const humidity = Number(weather.humidity);
        const windSpeed = Number(weather.wind_speed);
        const pressure = Number(weather.pressure);

        const items = [
            [
                getText('humidity', 'Humidity'),
                Number.isFinite(humidity) ? humidity + '%' : '--'
            ],
            [
                getText('wind', 'Wind'),
                Number.isFinite(windSpeed) ? windSpeed + ' km/h' : '--'
            ],
            [
                getText('windDirection', 'Wind direction'),
                getWindDirection(weather.wind_direction)
            ],
            [
                getText('pressure', 'Pressure'),
                Number.isFinite(pressure) ? pressure + ' hPa' : '--'
            ]
        ];

        items.forEach(function (item) {
            const detail = element('div', 'cwwp-detail');

            detail.appendChild(
                element('span', 'cwwp-detail-label', item[0])
            );

            detail.appendChild(
                element('strong', 'cwwp-detail-value', item[1])
            );

            details.appendChild(detail);
        });

        card.appendChild(details);

        // Last update information.
        card.appendChild(
            element(
                'p',
                'cwwp-updated',
                getText('updated', 'Updated') + ': ' +
                (
                    typeof weather.updated_at === 'string'
                        ? weather.updated_at
                        : ''
                )
            )
        );

        // Temperature selector behavior.
        function updateUnit(unit) {
            const symbol = unit === 'F' ? '°F' : '°C';

            temperature.textContent =
                convertTemperature(weather.temperature, unit) + symbol;

            feelsLike.textContent =
                getText('feelsLike', 'Feels like') + ' ' +
                convertTemperature(weather.feels_like, unit) + symbol;

            const isCelsius = unit === 'C';

            celsiusButton.classList.toggle('is-active', isCelsius);
            fahrenheitButton.classList.toggle('is-active', !isCelsius);

            celsiusButton.setAttribute(
                'aria-pressed',
                isCelsius ? 'true' : 'false'
            );

            fahrenheitButton.setAttribute(
                'aria-pressed',
                isCelsius ? 'false' : 'true'
            );
        }

        celsiusButton.addEventListener('click', function () {
            updateUnit('C');
        });

        fahrenheitButton.addEventListener('click', function () {
            updateUnit('F');
        });

        result.replaceChildren(card);
    }

    // Initialize all weather search forms.
    const forms = document.querySelectorAll('.cwwp-form');

    forms.forEach(function (form) {
        const container = form.closest('.cwwp-container');

        const result = container
            ? container.querySelector('.cwwp-result')
            : null;

        const cityInput = form.querySelector('input[name="city"]');
        const countryInput = form.querySelector('input[name="country"]');
        const button = form.querySelector('button[type="submit"]');

        if (!result || !cityInput || !countryInput || !button) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const city = cityInput.value.trim();
            const country = countryInput.value.trim().toUpperCase();

            if (!city || !/^[A-Z]{2}$/.test(country)) {
                result.textContent = getText(
                    'invalid',
                    'Please enter a valid city and country code.'
                );

                return;
            }

            button.disabled = true;
            button.textContent = getText('searching', 'Searching...');

            result.setAttribute('aria-busy', 'true');
            result.textContent = getText('loading', 'Loading weather...');

            const params = new URLSearchParams();

            params.append('action', 'cwwp_get_weather');
            params.append('nonce', cityWeather.nonce);
            params.append('city', city);
            params.append('country', country);

            fetch(cityWeather.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            })
                .then(function (response) {
                    return response.json()
                        .catch(function () {
                            throw new Error(
                                getText('connection', 'Connection error. Please try again.')
                            );
                        })
                        .then(function (payload) {
                            if (!response.ok) {
                                throw new Error(
                                    payload &&
                                    payload.data &&
                                    typeof payload.data.message === 'string'
                                        ? payload.data.message
                                        : getText(
                                            'connection',
                                            'Connection error. Please try again.'
                                        )
                                );
                            }

                            return payload;
                        });
                })
                .then(function (payload) {
                    if (
                        !payload ||
                        !payload.success ||
                        !payload.data
                    ) {
                        throw new Error(
                            payload &&
                            payload.data &&
                            typeof payload.data.message === 'string'
                                ? payload.data.message
                                : getText(
                                    'connection',
                                    'Connection error. Please try again.'
                                )
                        );
                    }

                    renderWeather(payload.data, result);
                })
                .catch(function (error) {
                    result.textContent = error.message ||
                        getText('connection', 'Connection error. Please try again.');
                })
                .finally(function () {
                    button.disabled = false;
                    button.textContent = getText('update', 'Update');

                    result.setAttribute('aria-busy', 'false');
                });
        });
    });
});