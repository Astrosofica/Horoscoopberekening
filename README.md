# Horoscoopberekening

Een webapplicatie voor astrologische horoscoop berekeningen met Swiss Ephemeris.
Licentie: [AGPL v3.0](https://www.gnu.org/licenses/agpl-3.0.html)

## Features

- **Horoscoop berekeningen** - Planeetposities, huizen, aspecten
- **Progressies** - Secundaire progressies met events
- **Transits** - Transit events met datumranges
- **Spiegelpunten (Antiscia)** - Antiscia en contrantiscia
- **Midpunten** - Planeet en teken midpunten
- **Opslag** - Horoscopen opslaan in database
- **Authenticatie** - Gebruikersaccounts met login/registratie

## Technologie

- **PHP 8.0+** - Backend logic
- **Swiss Ephemeris** - Astrologische berekeningen via FFI
- **MySQL/MariaDB** - Database
- **Google Maps API** - Geocoding en timezone
- **CSS3** - Responsive design
- **Vanilla JavaScript** - Frontend interactions

## Architectuur

```
SwissEphemeris (FFI → libswe.so)
    ↓
PlanetCalculator::calculateForTimestamp($timestamp)
    ↓
HoroscopeCalculator::calculate(Horoscope $horoscope)
```

## Installatie

### Vereisten

- PHP 8.0+
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

2. **Installeer dependencies**
   ```bash
   composer install
   ```

3. **Configureer environment**
   ```bash
   cp .env.example .env
   # Edit .env met database credentials en API keys
   ```

4. **Plaats Swiss Ephemeris**
   ```bash
   # Plaats libswe.so in /usr/local/lib/ of configurabele locatie
   ```

5. **Start applicatie**
   ```bash
   # Start PHP server of configureer web server (Apache/Nginx)
   php -S localhost:8000 -t public
   ```

## Gebruik

### Nieuwe Horoscoop

1. Open de homepage
2. Voer geboortegegevens in (naam, datum, tijd, locatie)
3. Klik "Bereken"
4. Bekijk resultaten in tabs: Planeten/Huizen, Aspecten, Progressies, etc.

### Opgeslagen Horoscopen

1. Login of registreer
2. Bereken horoscoop
3. Klik "Opslaan"
4. Bekijk opgeslagen horoscopen in Dashboard

### Timezone Opties

- **Automatisch** - IANA timezone lookup via Google API
- **UTC** - Handmatige UTC offset
- **LMT** - Lokale middentijd berekening

## Bewuste Keuzes

- **Huizensysteem:** Alleen Koch (meest gebruikt in westerse astrologie)
- **Planeten:** Zon t/m Pluto + Noordknoop + Chiron
- **Aspecten:** 8 standaard aspecten (0°, 45°, 60°, 90°, 120°, 135°, 150°, 180°)
- **Ephemeris:** Dagelijkse posities voor buitenplaneten (Jupiter-Pluto)

## Taal

De interface is momenteel alleen beschikbaar in het Nederlands. Internationalisatie (i18n) is gepland voor een toekomstige versie.

## Credits

- **Allen Edwall** — Inspiratie PHP horoscoop visualisatie
- **Michael Erlewine** — Originele BASIC code (1980)
- **Astrodienst Zürich** — Swiss Ephemeris library
- **Google** — Geocoding API

## License

AGPL v3.0 — [GNU Affero General Public License](https://www.gnu.org/licenses/agpl-3.0.html)

Swiss Ephemeris is eigendom van Astrodienst Zürich en onderhevig aan een eigen license.
Google Maps Platform gebruik is onderhevig aan de [Google Maps Platform Terms of Service](https://cloud.google.com/maps-platform/terms).

## Contact

- **Issues**: [GitHub Issues](https://github.com/Astrosofica/Horoscoopberekening/issues)
- **Repository**: [github.com/Astrosofica/Horoscoopberekening](https://github.com/Astrosofica/Horoscoopberekening)
