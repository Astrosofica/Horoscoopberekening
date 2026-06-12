# Horoscoopberekening

A web application for astrological horoscope calculations using Swiss Ephemeris.
License: [AGPL v3.0](https://www.gnu.org/licenses/agpl-3.0.html)

Horoscoopberekening is a personal project by Jan van der Velde, astrology teacher at [The Center for Humanistic and Transpersonal Astrology](https://www.astrologie.ws). It was built as a practical tool for students of our courses, which explains some of the design choices — such as the Dutch interface, the focus on the Koch house system, and the selection of planets and aspects taught in our curriculum.

> **Language:** The user interface is currently only available in Dutch. Internationalization (i18n) is in progress — date/time formatting already supports multiple locales via `ext-intl`.

## Features

- **Horoscope calculations** - Planet positions, houses, aspects
- **Progressions** - Secondary progressions with events
- **Transits** - Transit events with date ranges
- **Antiscia** - Antiscia and contra-antiscia
- **Midpoints** - Planet and sign midpoints
- **Storage** - Save horoscopes to database
- **Authentication** - User accounts with login/registration

## Technology

- **PHP 8.0+** - Backend logic
- **ext-intl** - Internationalization (date/time formatting)
- **Swiss Ephemeris** - Astrological calculations via FFI
- **MySQL/MariaDB** - Database
- **Google Maps API** - Geocoding and timezone
- **CSS3** - Responsive design
- **Vanilla JavaScript** - Frontend interactions

## Architecture

```
SwissEphemeris (FFI → libswe.so)
    ↓
PlanetCalculator::calculateForTimestamp($timestamp)
    ↓
HoroscopeCalculator::calculate(Horoscope $horoscope)
```

## Installation

### Requirements

- PHP 8.0+
- PHP ext-intl
- Composer
- Swiss Ephemeris library (`libswe.so`)
- MySQL/MariaDB database
- Google Maps API key

### Setup

1. **Clone repository**
   ```bash
   git clone https://github.com/Astrosofica/Horoscoopberekening.git
   cd Horoscoopberekening
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with database credentials and API keys
   ```

4. **Place Swiss Ephemeris**
   ```bash
   # Place libswe.so in /usr/local/lib/ or configurable location
   ```

5. **Start application**
   ```bash
   # Start PHP server or configure web server (Apache/Nginx)
    php -S localhost:8000 -t public_html
   ```

## Usage

### New Horoscope

1. Open the homepage
2. Enter birth data (name, date, time, location)
3. Click "Bereken" (Calculate)
4. View results in tabs: Planets/Houses, Aspects, Progressions, etc.

### Saved Horoscopes

1. Login or register
2. Calculate a horoscope
3. Click "Opslaan" (Save)
4. View saved horoscopes in the Dashboard

### Timezone Options

- **Automatic** - IANA timezone lookup via Google API
- **UTC** - Manual UTC offset
- **LMT** - Local Mean Time calculation

## Design Choices

- **House system:** Koch only (most used in Western astrology)
- **Planets:** Sun through Pluto + North Node + Chiron
- **Aspects:** 8 standard aspects (0°, 45°, 60°, 90°, 120°, 135°, 150°, 180°)
- **Ephemeris:** Daily positions for outer planets (Jupiter–Pluto)

## Credits

- **Allen Edwall** — Inspiration for PHP horoscope visualization
- **Michael Erlewine** — Original BASIC code (1980)
- **Astrodienst Zürich** — Swiss Ephemeris library
- **Google** — Geocoding API

## License

AGPL v3.0 — [GNU Affero General Public License](https://www.gnu.org/licenses/agpl-3.0.html)

Swiss Ephemeris is property of Astrodienst Zürich and subject to its own license.
Google Maps Platform usage is subject to the [Google Maps Platform Terms of Service](https://cloud.google.com/maps-platform/terms).

## Contact

- **Issues**: [GitHub Issues](https://github.com/Astrosofica/Horoscoopberekening/issues)
- **Repository**: [github.com/Astrosofica/Horoscoopberekening](https://github.com/Astrosofica/Horoscoopberekening)
