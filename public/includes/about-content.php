<?php
// About content - integrated in index.php as tab
?>

<h1>Over <?= APP_NAME ?></h1>
<p class="about-version">Versie <?= BUILD_VERSION ?> (<?= BUILD_DATE ?>)</p>

<div class="about-content">
    <section class="about-section">
        <h2>Doel</h2>
        <p>
            <?= APP_NAME ?> is een astrologie berekeningsprogramma voor studenten en beoefenaars 
            van de astrologie. Het programma is gratis beschikbaar gesteld voor educatieve doeleinden 
            en biedt nauwkeurige horoscoopberekeningen met behulp van de Swiss Ephemeris library.
        </p>
    </section>

    <section class="about-section">
        <h2>Technologie</h2>
        <ul class="about-list">
            <li><strong>Swiss Ephemeris (FFI library)</strong> — Professionele astrologische berekeningen</li>
            <li><strong>PHP 8.x</strong> — Moderne backend</li>
            <li><strong>MySQL</strong> — Ephemeris data en horoscoop opslag</li>
            <li><strong>Google Maps Geocoding API</strong> — Locatie naar coördinaten conversie</li>
        </ul>
    </section>

    <section class="about-section">
        <h2>Credits &amp; Dankbetuiging</h2>
        <ul class="about-list">
            <li><strong>Allen Edwall</strong> — Originele PHP horoscoop visualisatie (met dank voor inspiratie)</li>
            <li><strong>Astrodienst</strong> — Swiss Ephemeris library</li>
            <li><strong>Michael Erlewine</strong> — Originele BASIC code (1980)</li>
            <li><strong>Google</strong> — Geocoding API</li>
        </ul>
    </section>

    <section class="about-section">
        <h2>Geschiedenis</h2>
        <div class="about-timeline">
            <div class="timeline-item">
                <span class="timeline-year">1980</span>
                <p>BASIC code door Michael Erlewine</p>
            </div>
            <div class="timeline-item">
                <span class="timeline-year">2003</span>
                <p>Visual Basic programma (v1.0), eerste iteratie</p>
            </div>
            <div class="timeline-item">
                <span class="timeline-year">2018</span>
                <p>Eerste webversie: PHP + JavaScript</p>
            </div>
            <div class="timeline-item">
                <span class="timeline-year">2023</span>
                <p>Tweede webversie: Svelte + PHP (v2.0)</p>
            </div>
            <div class="timeline-item">
                <span class="timeline-year">2026</span>
                <p>Huidige versie: PHP 8 + Swiss Ephemeris FFI (v3.0)</p>
            </div>
        </div>
    </section>

    <section class="about-section">
        <h2>Bewuste Keuzes</h2>
        <ul class="about-list">
            <li><strong>Huizensysteem:</strong> Alleen Koch (meest gebruikt in westerse astrologie)</li>
            <li><strong>Planeten:</strong> Zon t/m Pluto + Noordknoop (geen asteroïden)</li>
            <li><strong>Aspecten:</strong> 8 standaard aspecten (0°, 45°, 60°, 90°, 120°, 135°, 150°, 180°)</li>
            <li><strong>Ephemeris:</strong> Dagelijkse posities voor buitenplaneten (Jupiter-Pluto)</li>
        </ul>
    </section>

    <section id="privacy" class="about-section">
        <h2>Privacy &amp; Gebruik</h2>
        <ul class="about-list">
            <li><strong>Registratie:</strong> Vereist voor het opslaan van horoscopen</li>
            <li><strong>Opgeslagen data:</strong> Naam, geboortedatum/tijd, locatie, emailadres</li>
            <li><strong>Cookies:</strong> Alleen session cookies (geen tracking, geen third-party)</li>
            <li><strong>Data bewaren:</strong> Tot accountverwijdering door gebruiker</li>
            <li><strong>Verwijderen:</strong> Gebruikers kunnen horoscopen en account zelf verwijderen</li>
            <li><strong>Geen export:</strong> Data kan niet geëxporteerd worden</li>
            <li><strong>Geen garanties:</strong> Dagelijkse backups maar geen verantwoordelijkheid voor data-verlies</li>
            <li><strong>Geen data deling:</strong> Geen verkoop of sharing met derden</li>
        </ul>
    </section>

    <section id="licenses" class="about-section">
        <h2>License</h2>
        <p>
            <strong>AGPL v3</strong> — Dit programma is open source software onder de 
            <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noopener">
                GNU Affero General Public License v3.0
            </a>.
        </p>
        <p>
            Broncode is beschikbaar op: 
            <a href="https://github.com/Astrosofica/Horoscoopberekening" target="_blank" rel="noopener">
                github.com/Astrosofica/Horoscoopberekening
            </a>
        </p>
        <p>
            <strong>Swiss Ephemeris</strong> — Dit programma gebruikt de Swiss Ephemeris library 
            van Astrodienst Zürich. De Swiss Ephemeris is onderhevig aan een eigen license 
            en vereist naamsvermelding.
        </p>
        <p>
            <strong>Google Maps Platform</strong> — Gebruik van de Google Geocoding API is 
            onderhevig aan de 
            <a href="https://cloud.google.com/maps-platform/terms" target="_blank" rel="noopener">
                Google Maps Platform Terms of Service
            </a>.
        </p>
    </section>

    <section class="about-section">
        <h2>Contact</h2>
        <p>
            Bugs, verzoeken en bijdragen kunnen worden ingediend via 
            <a href="https://github.com/Astrosofica/Horoscoopberekening/issues" target="_blank" rel="noopener">
                GitHub Issues
            </a>.
        </p>
    </section>
</div>
