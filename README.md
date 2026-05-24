# Horoscoopberekening

Een webapplicatie voor astrologische horoscoop berekeningen met Swiss Ephemeris.

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
- **MySQL/SQLite** - Database
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
- MySQL of SQLite database
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

## Directory Structuur

```
tijd/
├── public/           # Web-accessible files
│   ├── index.php     # Hoofdpagina
│   ├── dashboard.php # Gebruikers dashboard
│   ├── css/          # Stylesheets
│   └── js/           # JavaScript
├── src/              # PHP classes
│   ├── Calculation/  # Calculators
│   ├── Ephemeris/    # Swiss Ephemeris wrapper
│   ├── Entity/       # Data entities
│   └── Database/     # Database access
├── config/           # Configuration
└── var/              # Logs and cache
```

## API Endpoints

| Endpoint | Beschrijving |
|----------|-------------|
| `/` | Horoscoop formulier en resultaten |
| `/dashboard.php` | Opgeslagen horoscopen |
| `/horoscope/save.php` | Horoscoop opslaan |
| `/login.php` | Gebruikers login |
| `/register.php` | Nieuwe account |

## Contributing

Zie [AGENTS.md](AGENTS.md) voor development guidelines (private documentatie).

## License

Copyright © 2026 Astrosofica. Alle rechten voorbehouden.

## Contact

- **Repository**: [github.com/Astrosofica/Horoscoopberekening](https://github.com/Astrosofica/Horoscoopberekening)
