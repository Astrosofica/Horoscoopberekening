<script>
  import Navigation from "./Navigation.svelte";
  import { astroData } from "../stores/astroData.js";
  import Details from "./Details.svelte";

  $: planeten = $astroData.planets;
  $: huizen = $astroData.houses;

  let pllon = [];
  let midpoints = [];

  $: if (planeten && huizen) {
    // Initialize pllon and hoek arrays

    pllon = [...planeten];
    pllon.push({ pos: huizen[0].long, naam: "Ascendant" });
    pllon.push({ pos: huizen[9].long, naam: "Midhemel" });

    let teller = 0;
    for (let p1 = 0; p1 <= 11; p1++) {
      for (let p2 = p1 + 1; p2 <= 12; p2++) {
        let graad;
        // check if p1 is a new planet to insert a blank line
        if (p1 > 0 && p2 === p1 + 1) {
          midpoints.push({
            naam1: "",
            naam2: "",
            graad: "",
          });
        }
        if (parseFloat(pllon[p1].pos) > parseFloat(pllon[p2].pos)) {
          graad = parseFloat(pllon[p1].pos) - parseFloat(pllon[p2].pos);
          if (graad > 180) {
            graad = parseFloat(pllon[p2].pos) + 360 - parseFloat(pllon[p1].pos);
            graad = graad / 2 + parseFloat(pllon[p1].pos);
          } else {
            graad = graad / 2 + parseFloat(pllon[p2].pos);
          }
        } else {
          graad = parseFloat(pllon[p2].pos) - parseFloat(pllon[p1].pos);
          if (graad > 180) {
            graad = parseFloat(pllon[p1].pos) + 360 - parseFloat(pllon[p2].pos);
            graad = graad / 2 + parseFloat(pllon[p2].pos);
          } else {
            graad = graad / 2 + parseFloat(pllon[p1].pos);
          }
        }

        midpoints.push({
          naam1: pllon[p1].naam,
          naam2: pllon[p2].naam,
          graad: graad,
        });

        teller = teller + 1;
      }
    }
  }

  // Helper functions
  function makeReadable(pos) {
    if (pos > 360) pos -= 360;
    if (pos < 0) pos += 360;
    const signs = [
      "Ram",
      "Stier",
      "Tweelingen",
      "Kreeft",
      "Leeuw",
      "Maagd",
      "Weegschaal",
      "Schorpioen",
      "Boogschutter",
      "Steenbok",
      "Waterman",
      "Vissen",
    ];
    const signIndex = Math.floor(pos / 30);
    const signPos = pos - signIndex * 30;
    const signDeg = Math.floor(signPos);
    const signMin = Math.floor((signPos - signDeg) * 60);
    const signSec = Math.round(((signPos - signDeg) * 60 - signMin) * 60);
    const formattedDeg = signDeg < 10 ? `0${signDeg}` : signDeg;
    const formattedMin = signMin < 10 ? `0${signMin}` : signMin;
    const formattedSec = signSec < 10 ? `0${signSec}` : signSec;
    return `${formattedDeg}° ${formattedMin}' ${formattedSec}" ${signs[signIndex]}`;
  }
</script>

<div class="container">
  <Navigation />
  <Details />
  <h3>Midpunten per planeet</h3>
  <table class="alternate">
    {#each midpoints as midpoint, i}
      {#if midpoint.naam1 === "" && midpoint.naam2 === "" && midpoint.graad === ""}
        <tr class={i < midpoints.length - 1 ? "space-after" : ""}>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      {:else}
        <tr class={i < midpoints.length - 1 ? "space-after" : ""}>
          <td>
            <span class="planeet">{midpoint.naam1}</span> /
            <span class="planeet">{midpoint.naam2}</span>
          </td>
          <td>{makeReadable(midpoint.graad)}</td>
        </tr>
      {/if}
    {/each}
  </table>
</div>

<style>
  h3 {
    color: #333;
    font-size: 1.5rem;
    margin-bottom: 15px;
  }

  .alternate {
    border-collapse: collapse;
    width: 100%;
  }

  .alternate td {
    border: 1px solid #ddd;
    padding: 2px;
    text-align: left;
  }
/*
  .alternate th {
    background-color: #f2f2f2;
    color: #333;
  }
*/
  .alternate tr {
    background-color: #fff;
  }

  .alternate tr:nth-child(even) {
    background-color: #f2f2f2;
  }

  .alternate tr:hover {
    background-color: #ddd;
  }

  .planeet {
    font-weight: bold;
  }
</style>
