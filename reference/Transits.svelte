<script>
  import { birthData } from '../stores/birthData.js';
  import Navigation from './Navigation.svelte';
  import Details from './Details.svelte';
  import { pGlyph, sGlyph, aGlyph } from '../stores/glyph.js';
  import { padding, decodeHtml } from '../utils/utils.js';
 

  let checkboxes = Array.from({ length: 13 }, () => false);
  let checkAspects = Array.from({ length: 8 }, () => false);
  checkAspects = [true, false, false, true, false, false, false, true]; // set defailt aspects
  let showForm = true;
  let isLoading = false;
  let planets = {};

    const apiUrl= 'https://astro.astrosofica.nl/php/transits.php';

    let data = {};
    let tplanets = [];
    let rplanets = [];
    let aspects = [];
    let errors = [];
    let startDate = '';
    let endDate = '';
    let hingress = '';
    let outputData= [];
    let startcounter = 0;
    let endcounter = 0;

      // Use the $ sign to create reactive variables
  $: longitude = $birthData.longitude;
  $: latitude = $birthData.latitude;
  $: utcTime = $birthData.utcTime;
  $: utcDateStr = $birthData.utcDateStr;

  // const planets 0-12 is the same as pGlyph 0-12
  for (let i = 0; i < 13; i++) {
    planets[i] = pGlyph[i];
  }

  planets[101] = 'H1';
  planets[102] = 'H2';
  planets[103] = 'H3';
  planets[104] = 'H4';
  planets[105] = 'H5';
  planets[106] = 'H6';
  planets[107] = 'H7';
  planets[108] = 'H8';
  planets[109] = 'H9';
  planets[110] = 'H10';
  planets[111] = 'H11';
  planets[112] = 'H12';

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
  // startdate and enddata must be after 1-1-1930 and before 1-1-2040
  if (startDate && endDate) {
    if (startDate < '1930-01-01' || endDate < '1930-01-01') {
      errors.push('Datums moeten na 1-1-1930 zijn');
    }
    if (startDate > '2040-01-01' || endDate > '2040-01-01') {
      errors.push('Datums moeten voor 1-1-2040 zijn');
    }
  }

  if (tplanets.length === 0 || rplanets.length === 0 || aspects.length === 0) {
    errors.push('Tenmiste één planeet of aspect is verplicht per rij');
  }

  if (errors.length > 0) {
  let errorContainer = form.querySelector('.error-container');
  let errorList;

  if (!errorContainer) {
    errorContainer = document.createElement('div');
    errorContainer.classList.add('error-container');
    // Adding inline style to the errorContainer// try to solve with global or @html
    errorContainer.style.color = "red";
    errorContainer.style.backgroundColor = "#eee";
    errorContainer.style.border = "1px solid #ddd";
    errorContainer.style.borderRadius = "5px";
    errorContainer.style.padding = "5px";
    const submitButton = form.querySelector('#calcButton');
    form.insertBefore(errorContainer, submitButton);
    // Create a new error list inside the container
    errorList = document.createElement('ul');
    errorContainer.appendChild(errorList);
  } else {// An error container is found, clear its content
    errorList = errorContainer.querySelector('ul');
    errorList.innerHTML = '';
  }

  errors.forEach(error => {
    const errorItem = document.createElement('li');
    errorItem.innerText = error;
    errorItem.style.fontSize = "14px"; // problem with ccs, hence inline style
    errorList.appendChild(errorItem);
  });
  
  // clear the errors array after the errors have been displayed
  errors.length = 0;
}

 else { // prepare and send data to API and get response.
    startcounter = Date.now();  // start timing in miliseconds
    isLoading = true;
    const data = {
      tplanets: tplanets,
      rplanets: rplanets,
      aspects: aspects,
      startDate: startDate,
      endDate: endDate,
      hingress: hingress,
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
        outputData = data.transit;
        endcounter = Date.now();        // stop timing in miliseconds
        isLoading = false;
      })
      .catch((error) => {
        console.error('Error:', error); // must create better error handling
        isLoading = false;
      });

    // Clear error messages and highlight invalid fields
    const errorContainer = form.querySelector('.error-container');
    if (errorContainer) {
      form.removeChild(errorContainer);
    }
  }
}

// functions for displaying the output
/*
function getPlanetName(index) {
    const planetNames = [
      'Zon', 'Maan', 'Mercurius', 'Venus', 'Mars', 'Jupiter', 'Saturnus', 'Uranus', 'Neptunus', 'Pluto',
      'Noordknoop', 'Ascendant', 'Midhemel'
    ];
    return planetNames[index];
  }
*/

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

  function toggleAll(event) {
    checkboxes = checkboxes.map(() => event.target.checked);
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

  function toggleForm() {
    showForm = !showForm;
  }

 function reverseDate(date){ // reverse yyyy-mm-dd to dd-mm-yyyy // Is this still needed?
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

 function getGlyph(degValue) { // could be made global
  degValue=parseInt(degValue);
  const glyphObj = aGlyph.find(item => item.deg === degValue);
  return glyphObj ? glyphObj.glyph : '';
 }
</script>

  <div class="container">
  <Navigation />
  <Details />
  <h1>Transits</h1>
  <div class="select not-print"><h3>Selectie</h3><button class="toggle" on:click={toggleForm}>{showForm ? 'Verberg Selectie' : 'Toon Selectie'}
  </button></div>
  {#if showForm}
  <form class="not-print" id="trForm" on:submit={handleSubmit}>
    <div id="transit-form">
    <div class="form-column min">  
     <h3>Tijdvak:</h3>
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
      <span class="break"></span>
      <strong>Planeetselectie</strong>
      <span class="break"></span> 
      <label><input type="checkbox" on:change={toggleAll}> Alle Radix</label>
      <label><input type="checkbox" on:change={toggleAspects}> Alle Aspecten</label>
    </div>
    <div class="form-column">
      <h3>Transit</h3>
      <label class="astro-font"><input type="checkbox" name="tplanet[]" value="5" class="astro-font"> {@html pGlyph[5]}</label>
      <label class="astro-font"><input type="checkbox" name="tplanet[]" value="6" class="astro-font"> {@html pGlyph[6]}</label>
      <label class="astro-font"><input type="checkbox" name="tplanet[]" value="7" class="astro-font"> {@html pGlyph[7]}</label>
      <label class="astro-font"><input type="checkbox" name="tplanet[]" value="8" class="astro-font"> {@html pGlyph[8]}</label>
      <label class="astro-font"><input type="checkbox" name="tplanet[]" value="9" class="astro-font"> {@html pGlyph[9]}</label>
      <span class="break"></span> 
      <h3>Aspect</h3>
    
      <label class="astro-font"><input type="checkbox" bind:checked={checkAspects[0]} name="aspect[]" value="0"> {@html getGlyph(0)}</label>
      <label class="astro-font"><input type="checkbox" bind:checked={checkAspects[1]} name="aspect[]" value="45"> {@html getGlyph(45)}</label>
      <label class="astro-font"><input type="checkbox" bind:checked={checkAspects[2]} name="aspect[]" value="60"> {@html getGlyph(60)}</label>
      <label class="astro-font"><input type="checkbox" bind:checked={checkAspects[3]} name="aspect[]" value="90"> {@html getGlyph(90)}</label>
      <label class="astro-font"><input type="checkbox" bind:checked={checkAspects[4]} name="aspect[]" value="120"> {@html getGlyph(120)}</label>
      <label class="astro-font"><input type="checkbox" bind:checked={checkAspects[5]} name="aspect[]" value="135"> {@html getGlyph(135)}</label>
      <label class="astro-font"><input type="checkbox" bind:checked={checkAspects[6]} name="aspect[]" value="150"> {@html getGlyph(150)}</label>
      <label class="astro-font"><input type="checkbox" bind:checked={checkAspects[7]} name="aspect[]" value="180"> {@html getGlyph(180)}</label>
   <!--  
    {#each checkAspects as a, index }
    <label class="astro-font">
      <input type="checkbox" bind:checked={checkAspects[index]} name="aspect[]" value={getAspectName(index)}> {@html getGlyph(getAspectName(index))}</label>
    {/each} -->
    </div>
    <div class="form-column">
      <h3>Radix</h3>
    
      {#each checkboxes as checked, index}
        <label class="astro-font">
          <input type="checkbox" bind:checked={checkboxes[index]} name="rplanet[]" value={index}>
          {@html pGlyph[index]}
        </label>
      {/each}
    </div>
    </div>
    <button id="calcButton" type="submit">Bereken de transits</button>        
  </form>
  {/if}
  {#if isLoading}
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
            <th>T</th>
            <th></th>
            <th>R</th>
            <th>Transit</th>
            <th>Radix</th>
        </tr>
    </thead>
    <tbody>
        {#each outputData as row, index (index)}
            <tr>
                <td>{dag(row.date)}</td>               
                <td>{retro(row.speed)}</td>   
                <td class="astro-font">{@html planets[row.tplanet]}</td>
                <td class="astro-font">{@html getGlyph(row.aspect)}</td>      
                <td class="{row.rplanet < 15 ? 'astro-font' : ''}">{@html planets[row.rplanet]}</td>          
                <td>{@html makeReadable(row.tlong,sGlyph)}</td>
                <td>{@html makeReadable(row.rlong,sGlyph)}</td>
            </tr>
        {/each}
    </tbody>
</table>

  <span class="timer not-print">Tijd nodig voor berekening: {(endcounter - startcounter) / 1000} seconden.</span>
{/if}

</div>

<style>
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

.min {
  min-width: 50%;
  max-width: 70%;
}
/*
button {
  margin-top: .5rem;
  width: 90%;
  padding: 0.5rem;
  border-radius: 0.5rem;
  border: 1px solid #ccc;
  text-align: center;
  margin-left: 5%;
  margin-right: 5%;
  background-color: #eee;
}
button:hover {
  background-color: #ccc;
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
*/

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

.timer {
  font-size: xx-small;
  margin-top: 1rem;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media print { /* should be made global */
    .not-print {
      display: none;
    }
  }

  @media (max-width: 400px) {
    .form-column {
      margin: 0.1rem;
    }
  }
</style>