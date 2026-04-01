<script>
  import { birthData } from '../stores/birthData.js';
  import Navigation from './Navigation.svelte';
  import Details from './Details.svelte';
  import { pGlyph, sGlyph, aGlyph } from '../stores/glyph.js';
  import { padding, decodeHtml } from '../utils/utils.js';

  let checkTplanets = Array.from({ length: 10 }, () => false);
  let checkRplanets = Array.from({ length: 13 }, () => false);
  let checkAspects = Array.from({ length: 8 }, () => false);
  let showForm = true;
  let isLoading = false;

    const apiUrl= 'https://astro.astrosofica.nl/php/prog-list.php';

    let data = {};
    let tplanets = [];
    let rplanets = [];
    let aspects = [];
    let errors = [];
    let startDate = '';
    let endDate = '';
    let hingress = '';
    let tingress = '';
    let outputData= [];
    let startcounter = 0;
    let endcounter = 0;

      // Use the $ sign to create reactive variables
  $: longitude = $birthData.longitude;
  $: latitude = $birthData.latitude;
  $: utcTime = $birthData.utcTime;
  $: utcDateStr = $birthData.utcDateStr;

  const planets = {
  0: 'Zon',
  1: 'Maan',
  2: 'Mercurius',
  3: 'Venus',
  4: 'Mars',
  5: 'Jupiter',
  6: 'Saturnus',
  7: 'Uranus',
  8: 'Neptunus',
  9: 'Pluto',
  10: 'Noordknoop',
  11: 'Ascendant',
  12: 'Midhemel',
  20: 'Ram',
  21: 'Stier',
  22: 'Tweelingen',
  23: 'Kreeft',
  24: 'Leeuw',
  25: 'Maagd',
  26: 'Weegschaal',
  27: 'Schorpioen',
  28: 'Boogschutter',
  29: 'Steenbok',
  30: 'Waterman',
  31: 'Vissen',
  40: 'Cusp 1',
  41: 'Cusp 2',
  42: 'Cusp 3',
  43: 'Cusp 4',
  44: 'Cusp 5',
  45: 'Cusp 6',
  46: 'Cusp 7',
  47: 'Cusp 8',
  48: 'Cusp 9',
  49: 'Cusp 10',
  50: 'Cusp 11',
  51: 'Cusp 12',
  60: 'gaat Retrograde',
  61: 'gaat Direct',
};

  // const planets 0-12 is the same as pGlyph 0-12
  for (let i = 0; i <= 12; i++) {
    planets[i] = pGlyph[i];
  }

  for (let i = 20; i <= 31; i++) {
    planets[i] = sGlyph[i-20];
  }

  for (let i = 40; i <= 51; i++) {
    planets[i] = 'H'+(i-39);
  }

  // Main function in module  
  function handleSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  tplanets = formData.getAll('tplanet[]');
  rplanets = formData.getAll('rplanet[]');
  aspects = formData.getAll('aspect[]');
  startDate = formData.get('startDate');
  endDate = formData.get('endDate');
  hingress = formData.get('hingress');
  tingress = formData.get('tingress');

  if (!startDate) {
    errors.push('Startdatum is verplicht');
  }

  if (!endDate) {
    errors.push('Einddatum is verplicht');
  }
  // end date must be after start date
  if (startDate && endDate) {
    if (startDate > endDate) {
      errors.push('Einddatum moet na startdatum zijn');
    }
  }

  if (tplanets.length === 0 && rplanets.length === 0 && aspects.length === 0) {
    errors.push('Tenmiste één planeet of aspect is verplicht per rij');
  }

  if (errors.length > 0) {
  let errorContainer = form.querySelector('.error-container');
  let errorList;

  if (!errorContainer) {
    // No error container found, create a new one
    errorContainer = document.createElement('div');
    errorContainer.classList.add('error-container');
    // Adding inline style to the errorContainer
    errorContainer.style.color = "red";
    errorContainer.style.backgroundColor = "#eee";
    errorContainer.style.border = "1px solid #ddd";
    errorContainer.style.borderRadius = "5px";
    errorContainer.style.padding = "5px";
    form.appendChild(errorContainer);

    // Create a new error list inside the container
    errorList = document.createElement('ul');
    errorContainer.appendChild(errorList);
  } else {
    // An error container is found, clear its content
    errorList = errorContainer.querySelector('ul');
    errorList.innerHTML = '';
  }

  errors.forEach(error => {
    const errorItem = document.createElement('li');
    errorItem.innerText = error;
    // Adding inline style to the errorItem
    errorItem.style.fontSize = "14px";
    errorList.appendChild(errorItem);
  });
  
  // clear the errors array after the errors have been displayed
  errors.length = 0;
} // end if errors.length > 0

 else {
    // Send data to API
    startcounter = Date.now();  // start timing in miliseconds
    isLoading = true;
    const data = {
      tplanets: tplanets,
      rplanets: rplanets,
      aspects: aspects,
      startDate: startDate,
      endDate: endDate,
      hingress: hingress,
      tingress: tingress,
      longitude: longitude,
      latitude: latitude,
      utcTime: utcTime,
      utcDateStr: utcDateStr,
    };

    // Send data to API and get response. 
    fetch(apiUrl, {
      method: 'POST',
      body: JSON.stringify(data),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })
      .then(response => response.json())
      .then(data => {
        // create output
        outputData = data;
        endcounter = Date.now();
        isLoading = false;
      })
      .catch((error) => {
        console.error('Error:', error); // bad errorhandling but it works for debugging
        isLoading = false;
      });

    // Clear error messages and highlight invalid fields
    const errorContainer = form.querySelector('.error-container');
    if (errorContainer) {
      form.removeChild(errorContainer);
    }
  }
}

// function for displaying the output
  function getAspectName(index) {
    const aspectlist = [ 0, 45, 60, 90, 120, 135, 150, 180 ];
    return aspectlist[index];
  }
function dag(date){ // retreive yyyy-mm-dd hh:mm:ss return dd-mm-yyyy. Make sure month and day have 2 digits.
  let d = new Date(date);
  let day = d.getDate();
  let month = d.getMonth() + 1;
  let year = d.getFullYear();
  if (day < 10) {
    day = '0' + day;
  }
  if (month < 10){
    month = '0' + month;
  }
  return day + '-' + month + '-' + year;
}

function retro(speed){ // retreive speed and return R or D
  if (speed < 0){
    return 'R';
  }
  else {
    return 'D';
  }
}

  function makeReadable(pos, signs) {
    if (pos > 360) pos -= 360;
    if (pos < 0) pos += 360;
    const signIndex = Math.floor(pos / 30);
    const signPos = pos - signIndex * 30;
    const signDeg = Math.floor(signPos);
    const signMin = Math.floor((signPos - signDeg) * 60);
    const signSec = Math.round(((signPos - signDeg) * 60 - signMin) * 60);
    const formattedDeg = signDeg < 10 ? `0${signDeg}` : signDeg;
    const formattedMin = signMin < 10 ? `0${signMin}` : signMin;
    const formattedSec = signSec < 10 ? `0${signSec}` : signSec;
    return `${formattedDeg}°<span class="astro-font">${decodeHtml(signs[signIndex])}</span>${formattedMin}'<span class="transitdesktop">${formattedSec}"</span>`;
  }

  function toggleForm() {
    showForm = !showForm;
  }

 function reverseDate(date){ // reverse yyyy-mm-dd to dd-mm-yyyy
  let d = new Date(date);
  let day = d.getDate();
  let month = d.getMonth() + 1;
  let year = d.getFullYear();
  if (day < 10) {
    day = '0' + day;
  }
  if (month < 10){
    month = '0' + month;
  }
  return day + '-' + month + '-' + year;
 }

 function toggleTplanets(event) {
    checkTplanets = checkTplanets.map(() => event.target.checked);
 }
 function toggleRplanets(event) {
    checkRplanets = checkRplanets.map(() => event.target.checked);
  }
  function toggleAspects(event) {
    checkAspects = checkAspects.map(() => event.target.checked);
  }

  function toggleCallenderYear(event) {                 // set startdate and enddate to 1-1-currentYear and 12-31-currentYear
    document.getElementById('twoYear').checked = false; // uncheck the twoYear checkbox
    const currentYear = new Date().getFullYear();
    const startDate = `${currentYear}-01-01`;
    const endDate = `${currentYear}-12-31`;
    document.getElementById('startDate').value = startDate;
    document.getElementById('endDate').value = endDate;
  }

  function toggleTwoYears(event) {                      // set startdate and enddate to 1 year ago and 1 year from now
    document.getElementById('calYear').checked = false; // uncheck the callenderYear checkbox 
    const currentDate = new Date();
    const startDate = `${currentDate.getFullYear() - 1}-${padding(currentDate.getMonth() + 1)}-${padding(currentDate.getDate())}`;
    const endDate = `${currentDate.getFullYear() + 1}-${padding(currentDate.getMonth() + 1)}-${padding(currentDate.getDate())}`;
    document.getElementById('startDate').value = startDate;
    document.getElementById('endDate').value = endDate;
  }

  function getGlyph(degValue) { // could be made global
  degValue=parseInt(degValue);
  const glyphObj = aGlyph.find(item => item.deg === degValue);
  return glyphObj ? glyphObj.glyph : '';
 }
</script>

  <div class="container">
  <Navigation />
  <Details />
  <h1>Progressies</h1>
  <div class="select not-print"><h3>Selectie</h3><button class="toggle" on:click={toggleForm}>{showForm ? 'Verberg Selectie' : 'Toon Selectie'}</button></div>
  {#if showForm}
  <form class="not-print" id="trForm" on:submit={handleSubmit}>
    <div id="transit-form">
    <div class="form-column min">
      <h3>Tijdvak</h3>
  
      <label>Startdatum: <input type="date" id="startDate" name="startDate" value="{data.startDate}"></label>
      <label>Einddatum: <input type="date" id="endDate" name="endDate" value="{data.endDate}"></label>
      <span class="break"></span>
      <strong>Tijdselectie</strong>
      <span class="break"></span>
      <label><input type="checkbox" id="calYear" on:change={toggleCallenderYear}> Kalenderjaar</label>
      <label><input type="checkbox" id="twoYear" on:change={toggleTwoYears}> Twee jaar</label>
      <span class="break"></span>
      <strong>Ingress</strong>
      <span class="break"></span> 
      <label><input type="checkbox" name="hingress" value="1"> Huis Ingress</label>
      <label><input type="checkbox" name="tingress" value="1"> Teken Ingress</label>
      <span class="break"></span>
      <strong>Planeetselectie</strong>
      <span class="break"></span>
      <label><input type="checkbox" on:change={toggleTplanets}> Alle Progressief</label>
      <label><input type="checkbox" on:change={toggleRplanets}> Alle Radix</label>
      <label><input type="checkbox" on:change={toggleAspects}> Alle Aspecten</label>
    </div>
    <div class="form-column">

      <h3><span class="desktop">Progressief</span><span class="mobile">Prog</span></h3>
      {#each checkTplanets as t, index}
      <label class="astro-font">
        <input type="checkbox" bind:checked={checkTplanets[index]} name="tplanet[]" value={index}> {@html pGlyph[index]}</label>
      {/each}
      <span class="break"></span>
      <h3>Aspect</h3>
      {#each checkAspects as a, index }
      <label class="astro-font">
        <input type="checkbox" bind:checked={checkAspects[index]} name="aspect[]" value={getAspectName(index)}> {@html getGlyph(getAspectName(index))}</label>
      {/each}
      
    </div>
    <div class="form-column">
      <h3>Radix</h3>
      {#each checkRplanets as checked, index}
        <label class="astro-font">
          <input type="checkbox" bind:checked={checkRplanets[index]} name="rplanet[]" value={index}> {@html pGlyph[index]}</label>
      {/each}
    </div>
    </div>

    <button type="submit">Bereken de progressies</button>        
    {#if errors.length > 0}
    <div class="error-container">
    </div>  
    {/if}
  </form>
  {/if}
  {#if isLoading}
  <div id="output">
    <p>
      Transit Planeten: { 
        (tplanets.length == 0) 
          ? ''
          : tplanets.map(index => planets[index]).join(', ')
      }
     : Huis Ingress: {hingress ? 'Ja' : 'Nee'}
    <br>Aspects: {aspects.join(', ')} 
    <br>
      Radix Planeten: { 
        (rplanets.length == 0) 
          ? ''
          : rplanets.map(index => planets[index]).join(', ')
      }
    <br>Periode: {reverseDate(startDate)} tot en met {reverseDate(endDate)}</p>
  </div>
 
  <div class="overlay">
    <div class="spinner"></div>
  </div>
  {/if}
  {#if outputData.length > 0}
  <table>
    <thead>
        <tr>
            <th>Datum</th>
            <th></th>
            <th>P</th>
            <th></th>
            <th>R</th>
            <th><span class="desktop">progressief</span><span class="mobile">prog</span></th>
            <th>radix</th>
        </tr>
    </thead>
    <tbody>
{#each outputData as regel }
<tr>
    <td>{dag(regel.time)}</td>      
    <td>{regel.dir}</td>  
    <td class="astro-font">{@html planets[regel.planP]}</td>
    <td class="astro-font">{@html getGlyph(regel.asp)}</td>      
    <td class="{regel.planR < 35 ? 'astro-font' : ''}">{@html planets[regel.planR]}</td>
    <td>{@html makeReadable(regel.pos, sGlyph)}</td>
    <td>{@html makeReadable(regel.rLong, sGlyph)}</td>
</tr>
{/each}
    </tbody>
</table>

<span class="timer not-print">Tijd nodig voor berekening: {(endcounter - startcounter) / 1000} seconden.</span>
{/if}

</div>

<style>
 #transit-form {
  display: flex;
  align-items: top;
  justify-content: center;
  flex-wrap: wrap;
}

.form-column {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  flex: 1;
  margin: 0.5rem;
  padding: 0.2rem;
  border: 1px solid #ccc;
  border-radius: 0.5rem;
  font-size: small;
}

.form-column label {
  margin: 0rem 0.5rem;
}

#output {
  margin-top: 1rem;
  font-size: small;
}

button {
  margin-top: .5rem;
  width: 100%;
  padding: 0.5rem;
  border-radius: 0.5rem;
  border: 1px solid #ccc;
  text-align: center;
  background-color: #bad095;
}
button:hover {
  background-color: #dde8ca;
  border-color: darkolivegreen;
  cursor: pointer;
}

.toggle {
  max-width: 50%;
  margin-top: .1rem;
  margin-bottom: .1rem;
  padding: 0.1rem;
  border-radius: 0.5rem;
  border: 1px solid #ccc;
  float: right;
  background-color: #eee;
  font-size: smaller;
  margin-left: 1%;
  margin-right: 1%;
}
.toggle:hover {
  background-color: #eee;
  border-color: darkolivegreen;
  cursor: pointer;
}

h3 {
  margin: 0;
  padding-top: 5px;
  padding-bottom: 5px;
}

.error-container {
background-color: #ffe6e6;
border: 1px solid #ff9999;
border-radius: 4px;
margin-top: 0.5rem;
padding: 0.5rem;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  background: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 100; /* Ensure it's above other content */
}

.spinner {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid blue;
  width: 120px;
  height: 120px;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media print {
    .not-print {
      display: none;
    }
  }

  .select {
  margin: 0 auto;
    max-width: 100%;
    background-color: whitesmoke;
    padding: 1rem;
    padding-top: 0;
    padding-bottom: 0;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin-top: 1px;
    margin-bottom: 1px;
    display: flex;
  justify-content: space-between;
  align-items: center;
}

.min {
  min-width: 50%;
  max-width: 70%;
}

@media (max-width: 400px) {
    .form-column {
      margin: 0.1rem;
    }
  }
  @media (max-width: 500px) {
    .desktop {
      display: none;
    }
  }
  @media (min-width: 500px) {
    .mobile {
      display: none;
    }
  }

  .timer {
  font-size: xx-small;
  margin-top: 1rem;
  margin-bottom: 1rem;
}
</style>