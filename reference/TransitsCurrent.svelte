<script>
  import { onMount } from "svelte";
  import Navigation from "./Navigation.svelte";
  import { birthData } from "../stores/birthData.js";
  import Details from "./Details.svelte";
  const apiUrl = "https://astro.astrosofica.nl/php/";
  import { pGlyph, sGlyph, aGlyph } from '../stores/glyph.js';
  import { padding, makeReadable, decodeHtml } from '../utils/utils.js';

  // Use the $ sign to create reactive variables
  $: longitude = $birthData.longitude;
  $: latitude = $birthData.latitude;
  $: utcTime = $birthData.utcTime;
  $: utcDateStr = $birthData.utcDateStr;
/*
  const planet= [
    "Zon", "Maan", "Mercurius", "Venus", "Mars", "Jupiter", "Saturnus", "Uranus", "Neptunus", "Pluto", "Noordknoop"
  ]
*/
  // retreive data from the API
  let curTransit = [];
  async function getTransit(utcDateStr, utcTime, longitude, latitude) {
  const response = await fetch(`${apiUrl}transits-current.php`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      date: utcDateStr,
      utc: utcTime,
      long: longitude,
      lat: latitude,
    }),
  });
  const data = await response.json();
  if (!Array.isArray(data)) {
    throw new Error('Invalid API response');
  }
  return data;
}

  // find out if the data is already loaded, if not, load it
  async function loadData() {
  if (utcDateStr && utcTime && longitude && latitude && (!curTransit || !curTransit.length)) {
    const newTransit = await getTransit(utcDateStr, utcTime, longitude, latitude);
    if (newTransit) {
      curTransit = newTransit;
    } else {
      console.error('Failed to load transit data'); // TODO: show error to user
    }
  }
}
  // load data on mount
  onMount(async () => {
    await loadData();
  });
  // determine if curTransit has data
  $: hasData = curTransit.length > 0;

  // Helper functions
  function dateTimeNow() {
    const date = new Date();
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const hours = date.getHours();
    const minutes = date.getMinutes();
    return `${day}-${month}-${year} ${padding(hours)}:${padding(minutes)}`;
  }

  function direction(snel){
    if (snel > 0) {
      return "D";
    } else if (snel < 0) {
      return "R";
    } else {
      return "S";
    }
  }
  </script>

  <div class="container">
  <Navigation />
  <Details />
  <h3>Transits op {dateTimeNow()}</h3>
  {#if hasData}
  <table>
    <thead>
      <tr>
        <th>Planeet</th>
        <th></th>
        <th>Positie</th>
        <th>Huis</th>
      </tr>
    </thead>
    <tbody>
      {#each curTransit as transit, index (index)}
      <tr>
        <td class="planeet astro-font">{@html pGlyph[transit.planet]}</td>
        <td align="right">{direction(transit.snel)}</td>
        <td>{@html makeReadable(transit.pos, sGlyph)}</td>
        <td><span class="desktop">staat in huis </span>{transit.house}</td>
      </tr>
      {/each}
    </tbody>
  </table>
  {:else}
  <p>Geen transits gevonden</p>
  {/if}
</div>

<style>
    @media (max-width: 400px) {
    .desktop {
      display: none;
    }
  }
</style>