<section id="tab-solaar" class="tab-content<?= $currentTab !== 'solaar' ? ' tab-content--hidden' : '' ?>">
    <div class="card card--large card--solaar">
        <h2>Solaar Horoscoop</h2>
        <p class="solaar-intro">
            De Zon keert jaarlijks terug op exact dezelfde positie als bij je geboorte.
            Er wordt een nieuwe horoscoop gemaakt voor dat moment. Je kunt daarna zelf
            de locatie invullen waar je je op dat moment bevindt.
        </p>
        <form method="POST" class="solaar-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="solaar_year">Jaar</label>
                    <input type="number" id="solaar_year" name="solaar_year"
                           value="<?= date('Y') ?>" min="1900" max="2100" required>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" name="calculate_solaar" value="1">
                    Bereken de Solaar
                </button>
            </div>
        </form>
    </div>
</section>
