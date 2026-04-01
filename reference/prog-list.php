<?php 
// Add headers
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('UTC');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json"); // temp removed for debugging
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('UTC');
/* // temp removed for debugging*/
// Get the JSON data from the POST request
$json_data = file_get_contents('php://input');

// Check if data was received
if (!$json_data) {
	// No data was received, return an error message
	echo json_encode(['error' => 'No data received']);
	exit;
}

// Decode the JSON into a PHP object
$data = json_decode($json_data);

// Also check if the json_decode function returned an error
if (json_last_error() !== JSON_ERROR_NONE) {
	// Invalid JSON was received, return an error message
	echo json_encode(['error' => 'Invalid JSON received']);
	exit;
}	

// User input through JSON	
$longitude =$data->longitude;
$latitude =$data->latitude;
$utcTime =$data->utcTime;
$utcDateStr =$data->utcDateStr;

// I need some more of the data from the received JSON object
$startDate = $data->startDate;
$endDate = $data->endDate;
$pplanets = $data->tplanets;
$rplanets = $data->rplanets;
$aspects = $data->aspects;
$hingress = $data->hingress; // house ingress flag
$tingress = $data->tingress; // teken ingress flag

// start en enddate hebben verkeerde format, dus even aanpassen
// convert from yyyy-mm-dd to dd.mm.yyyy
$startDate = date("d.m.Y", strtotime($startDate));
$endDate = date("d.m.Y", strtotime($endDate));


/*
echo "Startdatum: ".$startDate."<br>";
echo "Einddatum: ".$endDate."<br>";
echo "<pre>";
print_r($data);
echo "</pre>";
*/
/*

$latitude=51.673611;
$longitude=4.281111;
$utcDateStr="19.7.1963";
$utcTime="15:51:21";
$startDate = "1.1.2020";
$endDate = "31.12.2025";
$pplanets = [0,1,2,3,4,5,6,7,8,9];
$rplanets = [0,1,2,3,4,5,6,7,8,9,10,11,12];
$aspects = [0,45,60,90,120,135,180];
$hingress = 0; // house ingress flag
$tingress = 0; // teken ingress flag
*/
	$planeetnaam= [
        "Zon",
        "Maan",
        "Mercurius",
        "Venus",
        "Mars",
        "Jupiter",
        "Saturnus",
        "Uranus",
        "Neptunus",
        "Pluto",
        "Noordknoop"
    ];

    // belangrijke variabelen
	$swephsrc = '../src';
	$sweph = '../eph';
	$result="";
	$PATH="";
	
	$prResult=array(); // initialisatie van array waarin alle aspecten worden verzamelt..
	$resultcount=0;

// Bereken de planeten bij geboorte	
putenv("PATH=$PATH:$swephsrc");
exec("swetest -edir$sweph -b$utcDateStr -p0123456789t -ut$utcTime -fls -roundsec -speed -g, -head -eswe",$planets);
$teller=0; 
foreach ($planets as $planet){ // populate de array met de planeten
	$line=explode(",",$planet);
		$planeten[$teller]['naam']= $planeetnaam[$teller];
		$planeten[$teller]['pos']= $line[0];
		$planeten[$teller]['snel']= $line[1];
	$teller++; 
}

// Bereken de huizen bij geboorte
exec("swetest -edir$sweph -p -b$utcDateStr -house$longitude,$latitude,k -ut$utcTime -fl -roundsec -speed -g -head -eswe",$houses);
$teller=1;
foreach ($houses as $house){ // populate de array met de huizen
		$huizen[$teller]['naam']= "huis $teller";
		$huizen[$teller]['pos']= $house;
	$teller++;
}

// Maak Ascendant en MC ook tot onderdeel van de planeten array
$planeten[11]['pos']=$huizen[1]['pos'];
$planeten[11]['naam']="Ascendant";
$planeten[12]['pos']=$huizen[10]['pos'];
$planeten[12]['naam']="MC";

// Huiscusp en begin teken i.v.m. ingress
$planeten[20]['naam']="Ram";
$planeten[21]['naam']="Stier";
$planeten[22]['naam']="Tweelingen";
$planeten[23]['naam']="Kreeft";
$planeten[24]['naam']="Leeuw";
$planeten[25]['naam']="Maagd";
$planeten[26]['naam']="Weegschaal";
$planeten[27]['naam']="Schorpioen";
$planeten[28]['naam']="Boogschutter";
$planeten[29]['naam']="Steenbok";
$planeten[30]['naam']="Waterman";
$planeten[31]['naam']="Vissen";

// tekens moeten ook pos meekrijgen.
// loop door tekens array en zet de pos in de planeten array
for ($i=0;$i<12;$i++){
	$planeten[$i+20]['pos']=$i*30;
}

// huizen moeten ook pos meekrijgen.
// loop door huizen array en zet de pos in de planeten array
for ($i=1;$i<=12;$i++){
	$planeten[$i+39]['pos']=$huizen[$i]['pos'];
}

$planeten[40]['naam']="Huis 1";
$planeten[41]['naam']="Huis 2";
$planeten[42]['naam']="Huis 3";
$planeten[43]['naam']="Huis 4";
$planeten[44]['naam']="Huis 5";
$planeten[45]['naam']="Huis 6";
$planeten[46]['naam']="Huis 7";
$planeten[47]['naam']="Huis 8";
$planeten[48]['naam']="Huis 9";
$planeten[49]['naam']="Huis 10";
$planeten[50]['naam']="Huis 11";
$planeten[51]['naam']="Huis 12";

$planeten[60]['naam']="Gaat retrograde";
$planeten[61]['naam']="Gaat direct";

// Beginnen met berekeningen ivm progressies..
$rekendag=60*60*24;

$toen = mkStamp($utcDateStr,$utcTime);	
$nu=microtime(true);

$startstamp=toen($toen, mkstamp($startDate,"00:00:00"));
$endstamp=toen($toen, mkstamp($endDate,"00:00:00"));
$startday=eDate($startstamp);
$starttime=eTime($startstamp);
$endday=eDate($endstamp);
$endtime=eTime($endstamp);

$progstamp=toen($toen,$nu);
$leeftijd=leeftijd($toen,intval($nu),"y"); // was verdwenen in een functie.. nodig voor prog hoeken..

$prDate=eDate($progstamp);	
$prTime=eTime($progstamp);	
exec("swetest -edir$sweph -b$prDate -p0123456789t -ut$prTime -fls -roundsec -speed -g, -head -eswe",$prPlanets);
$teller=0; 
foreach ($prPlanets as $prPlanet){
	$line=explode(",",$prPlanet);
		$prPlaneten[$teller]['naam']= $planeetnaam[$teller];
		$prPlaneten[$teller]['pos']= $line[0];
		$prPlaneten[$teller]['snel']= $line[1];
	$teller++; 
}	

$preDate=eDate(strtotime("+".$leeftijd." days", $toen));
exec("swetest -edir$sweph -p -b$preDate -house$longitude,$latitude,k -ut$utcTime -fl -roundsec -speed -g -head -eswe",$preHouses);
$teller=1;
foreach ($preHouses as $preHouse){ 
	$preHuis[$teller]['naam']= "huis $teller";
	$preHuis[$teller]['pos']= $preHouse;
	$teller++;
}
$postDate=eDate(strtotime("+".($leeftijd+1)." days", $toen));
exec("swetest -edir$sweph -p -b$postDate -house$longitude,$latitude,k -ut$utcTime -fl -roundsec -speed -g -head -eswe",$postHouses);
$teller=1;
foreach ($postHouses as $postHouse){ 
		$postHuis[$teller]['naam']= "huis $teller";
		$postHuis[$teller]['pos']= $postHouse;
	$teller++;
}	

$tijdverschil=$progstamp-strtotime("+".$leeftijd." days", $toen);
$restjaar=$tijdverschil/$rekendag;
$ascVerschil = $postHuis[1]['pos'] - $preHuis[1]['pos'];
$mcVerschil = $postHuis[10]['pos'] - $preHuis[10]['pos'];
$prAscPos= 	$preHuis[1]['pos'] +($ascVerschil*$restjaar);
$prMcPos= 	$preHuis[10]['pos'] +($mcVerschil*$restjaar);	

$prPlaneten[11]['pos']=$prAscPos;
$prPlaneten[12]['pos']=$prMcPos;

$nu = round($nu); // afronden op seconden..

// van alle radix posities nu de aspectpunten berekenen..

$aspTeller = 0;
foreach ($rplanets as $rplanet) {
    $hoeken = calculate_aspects($planeten[$rplanet]['pos'], $aspects);
    foreach ($hoeken as $hoek) {
        $aspList[$aspTeller]['pla'] = $rplanet;
        $aspList[$aspTeller]['asp'] = $hoek['aspect'];
        $aspList[$aspTeller]['pos'] = $hoek['positie'];
        $aspTeller++;
    }
}
if ($tingress==1){  // teken ingressen aanzetten met flag
	for($teken=0; $teken<=11; $teken++){ // loop door de tekens
		$aspList[$aspTeller]['pla']=$teken+20;
		$aspList[$aspTeller]['asp']=0;
		$aspList[$aspTeller]['pos']=$teken*30;
		$aspTeller++;
	} 
}
if ($hingress==1){  // huizen ingressen aanzetten met flag
	for($huis=1; $huis<=12; $huis++){ // loop door de huizen
		$aspList[$aspTeller]['pla']=$huis+(40-1); // -1 omdat namen op 40 beginnen, huizen op 1
		$aspList[$aspTeller]['asp']=0;
		$aspList[$aspTeller]['pos']=$huizen[$huis]['pos'];
		$aspTeller++;
	}
}

usort(
    $aspList, 
    function($a, $b) {
        $result = 0;
        if ($a['pos'] > $b['pos']) {
            $result = 1;
        } else if ($a['pos'] < $b['pos']) {
            $result = -1;
        }
        return $result; 
    }
);

//////////////////////////// TO DO //////////////////////////////////////////////////
// tijdelijke uitsluitingen Ascendant en MC.
// - Progressieve hoeken... (alleen reken werk, geen eph.berekening mogelijk..)
// - Vinden van data om Ram/Vissen/Retro te testen..
//////////////////////////////////////////////////////////////////////////////////////

foreach ($pplanets as $pp) {
	if ($pp==1){ // Moon needs a different aproach because it can exceed 360 degrees

		// Initialize variables
		$degrees = $aspList;
		$start_date = (new DateTime())->setTimestamp(round($startstamp));
		$end_date = (new DateTime())->setTimestamp(round($endstamp));
		// hack to get the moon data for the correct date add 1 day to the start date and add 1 day from the end date
		$start_date->add(new DateInterval('P1D'));
		$end_date->add(new DateInterval('P1D'));
		// should not be neede but it's a hack to get the correct moon data
		$current_date = clone $start_date;
		$last_date = clone $start_date; // Keep track of the last date printed
		$last_printed_degree = null;
		// Main routine for progressed Moon.
		while ($current_date <= $end_date) {
			$moon_data = get_moon_data($current_date);
			$current_longitude = $moon_data['longitude'];
			$moon_daily_motion = $moon_data['speed'];
		
			// Adjust for shift from Pisces to Aries
			if ($last_printed_degree > 330 && $current_longitude < 30) {
				$current_longitude += 360;
			}
		
			foreach($degrees as $degree) {
				// Adjust degree for comparison
				$adjusted_degree = $degree['pos'];
				//echo "<br>adjusted_degree: ".$adjusted_degree;
				if ($last_printed_degree !== null && $last_printed_degree > 330 && $degree['pos'] < 30) {
					$adjusted_degree += 360;
				}
		
				if (abs($current_longitude - $adjusted_degree) < $moon_daily_motion && $last_printed_degree != $degree['pos']) {
		
					if ($start_longitude <= $degree['pos'] && $degree['pos'] <= $current_longitude) {
						$current_speed = get_moon_data($current_date)['speed'];
						$exact_date = interpolate_time($current_longitude, $degree['pos'], $current_date, $current_speed);
						$tussenStamp = $exact_date->getTimestamp();
						// start adding to $prResult
						$prResult[$resultcount]['planP'] = $pp;
						$prResult[$resultcount]['planR'] = $degree['pla'];
						$prResult[$resultcount]['dir'] = "D";
						$prResult[$resultcount]['asp'] = $degree['asp'];
						$prResult[$resultcount]['pos'] = $degree['pos'];
						$prResult[$resultcount]['time'] = $tussenStamp;
						$resultcount++;

						//$last_printed_degree = $degree['pos']; // conjuncties met hoeken worden niet getoond
						$last_printed_degree = null;
					}
				}
			}
			// Save longitude and date for next iteration
			$last_longitude = $current_longitude;
			$last_date = clone $current_date;
			// Increment the date
			$current_date->add(new DateInterval('P1D'));
		}

	}
	else {
		$progstamp = round($progstamp); // afronden op seconden..
		$preStamp = (int)$startstamp;
		$postStamp = (int)$endstamp;

		$preSdate = eDate($preStamp);
		$preStime = eTime($preStamp);
		$preSplanets = array();
		exec("swetest -edir$sweph -b$preSdate -p$pp -ut$preStime -fls -roundsec -speed -g, -head -eswe", $preSplanets);
		$line = explode(",", $preSplanets[0]);
		$preSpos = $line[0];
		$preSsnel = $line[1];
		
		$postSdate = eDate($postStamp);
		$postStime = eTime($postStamp);
		$postSplanets = array();
		exec("swetest -edir$sweph -b$postSdate -p$pp -ut$postStime -fls -roundsec -speed -g, -head -eswe", $postSplanets);
		$line = explode(",", $postSplanets[0]);
		$postSpos = $line[0];
		$postSsnel = $line[1];
		
		// hier bepalen wat de planeet doet qua richting..
		if ($preSsnel >= 0) { // Start direct
			if ($postSsnel >= 0) { // eindigt direct
				$richting = 1;
			} else { // begint direct maar gaat retrograde lopen
				$richting = 2;
			}
			if (($postSpos < 180) && ($preSpos > 180)) { // planeet gaat van Vissen naar Ram
				$richting = 3;
			}
		} else { // start retrograde
			if ($postSsnel <= 0) { // eindigt retrograde
				$richting = -1;
			} else { //begint retrograde maar gaat direct lopen
				$richting = -2;
			}
			if (($postSpos > 180) && ($preSpos < 180)) { // planeet gaat van Ram naar Vissen
				$richting = -3;
			}
		}
    
		// hier weten we welke richting de planeet op gaat..
		switch ($richting) {
			case -3:
				Pramvis($preSpos, $preSsnel, $postSpos, $postSsnel, $preStamp, $postStamp, $pp);
				break;
			case -2:
				$omkeer = omkeer($preStamp, $postStamp, $pp);
				// Nu transits berekenen in twee fasen...
				foreach ($omkeer as $oValue) {
					$tussenStamp = $oValue['time'];
					$tussenPos = $oValue['pos'];
				}
				$prResult[$resultcount]['planP'] = $pp;
				$prResult[$resultcount]['planR'] = 61;
				$prResult[$resultcount]['dir'] = "S";
				$prResult[$resultcount]['asp'] = 0;
				$prResult[$resultcount]['pos'] = $tussenPos;
				$prResult[$resultcount]['time'] = $tussenStamp;
				$resultcount++;
				Progress($preSpos, $preSsnel, $tussenPos, $preSsnel, $preStamp, $tussenStamp, $pp);
				Progress($tussenPos, $postSsnel, $postSpos, $postSsnel, $tussenStamp + 1, $postStamp, $pp);
				break;
			case -1:
				Progress($preSpos, $preSsnel, $postSpos, $postSsnel, $preStamp, $postStamp, $pp);
				break;
			case 1:
				Progress($preSpos, $preSsnel, $postSpos, $postSsnel, $preStamp, $postStamp, $pp);
				break;
			case 2:
				$omkeer = omkeer($preStamp, $postStamp, $pp);
				// Nu transits berekenen in twee fasen...
				foreach ($omkeer as $oValue) {
					$tussenStamp = $oValue['time'];
					$tussenPos = $oValue['pos'];
				}
				$prResult[$resultcount]['planP'] = $pp;
				$prResult[$resultcount]['planR'] = 60;
				$prResult[$resultcount]['dir'] = "S";
				$prResult[$resultcount]['asp'] = 0;
				$prResult[$resultcount]['pos'] = $tussenPos;
				$prResult[$resultcount]['time'] = $tussenStamp;
				$resultcount++;
				Progress($preSpos, $preSsnel, $tussenPos, $preSsnel, $preStamp, $tussenStamp, $pp);
				Progress($tussenPos, $postSsnel, $postSpos, $postSsnel, $tussenStamp + 1, $postStamp, $pp);
				break;
			case 3:
				Pvisram($preSpos, $preSsnel, $postSpos, $postSsnel, $preStamp, $postStamp, $pp);
				break;
		}
	} // Eind else
} // Eind foreach $pplanets

usort(
    $prResult, 
    function($a, $b) {
        $result = 0;
        if ($a['time'] > $b['time']) {
            $result = 1;
        } else if ($a['time'] < $b['time']) {
            $result = -1;
        }
        return $result; 
    }
);

foreach ($prResult as $key => $value) { // convert timestamps to ISO 8601 date strings.
    $timestamp = $value['time']; // Assuming 'time' is the timestamp in seconds.
	$timestamp=nu($toen, $timestamp); // convert progressive date to current date
	$timestamp=round($timestamp); // round to whole seconds
    $date = new DateTime("@$timestamp");
    // Update the 'time' field with the ISO 8601 date string.
    $prResult[$key]['time'] = $date->format(DateTime::ATOM);
	// add the longtitute of planR to the array
	$rLong=$planeten[$prResult[$key]['planR']]['pos'];
	if ($value['planR'] == 60 OR $value['planR'] == 61) {
		$rLong=$value['pos'];
	}
	$prResult[$key]['rLong']=$rLong;

}

// Output naar JSON
echo json_encode($prResult);

// ********************************************************
// Hieronder Functies, geen invloed op het hoofdprogramma..
// ********************************************************

// Functie om efemeride datum en tijd tot timestamp te maken
function mkStamp($date,$time){	// specifiek voor Sweph
	$t=explode(":",$time);
	$d = explode(".",$date);
	$result=mktime(doubleval($t[0]),$t[1],$t[2],$d[1],$d[0],$d[2]);
	return ($result);
}
function eDate($stamp){			// specifiek voor Sweph
	$stamp=round($stamp); // afronden op seconden
	return date("j.n.Y",$stamp);
}
function eTime($stamp){			// specifiek voor Sweph
	$stamp=round($stamp); // afronden op seconden
	return date("H:i:s",$stamp);
}

function calculate_aspects($positie, $aspecten){ // voeg aspecten to aan planeet positie
	$teller=0;
	foreach ($aspecten as $aspect){
		$hoek[$teller]['aspect']=$aspect;
		$hoek[$teller]['positie']=cirkel($positie+$aspect);
		$teller++;
		if ($aspect>0 AND $aspect<180) { // voorkom dubbele 0 en 180 aspecten
			$hoek[$teller]['aspect']=$aspect;
			$hoek[$teller]['positie']=cirkel($positie-$aspect);
			$teller++;
		}	// Einde if
	}		// Einde foreach
	return $hoek;
}

function leeftijd($toen,$nu,$format){ // format is y - jaar of a - dag
	$jaar1=date_create("@$toen");
	$jaar2=date_create("@$nu");
	$jdiff=date_diff($jaar1,$jaar2);
	return $jdiff->format("%$format");
}

function toen($toen, $nu){ // maak van geboortetijd en tweede timestamp een progressieve datum
	$solaryear=365.24219893;
	$secProgRate = 1/$solaryear;
	return($toen+($nu - $toen)*$secProgRate);
}
function nu($toen, $progstamp) {
	$solaryear=365.24219893;
	$secProgRate = 1/$solaryear;
	return($toen+($progstamp - $toen)/$secProgRate);
}

function cirPlus($graad){
	if ($graad<180) $graad=$graad+360;
	return ($graad);
}

// berekening voor wanneer planeet van vissen naar Ram gaat..
function Pvisram($preSpos, $preSsnel, $postSpos, $postSsnel, $preStamp, $postStamp, $pp){
	global $planeten;
	global $aspList;
	global $rekendag;
	global $sweph;
	global $prResult;
	global $resultcount;
	if ($pp==1) {$endPos = $postSpos+360;}
	else{
		$endPos=cirPlus($postSpos);
	}
	$startPos= cirPlus($preSpos);

	$okTeller=0;
	$okList=array();
	foreach ($aspList as $alist){
	 if (cirPlus($alist['pos'])>= cirPlus($startPos) AND cirPlus($alist['pos'])<= cirPlus($endPos)) {
		$okList[$okTeller]['pla']=$alist['pla'];
		$okList[$okTeller]['asp']=$alist['asp'];
		$okList[$okTeller]['pos']=cirPlus($alist['pos']);
		$okTeller++;
	 } // einde if
	} // einde foreach
	usort(
		$okList, 
		function($a, $b) {
			$result = 0;
			if ($a['pos'] > $b['pos']) {
				$result = 1;
			} else if ($a['pos'] < $b['pos']) {
				$result = -1;
			}
			return $result; 
		}
	);
	
	$startpunt=cirPlus($startPos);
	$startsnel=$preSsnel;

	foreach ($okList as $aspQuery){

		if ($preSsnel>=0 AND $postSsnel>=0) {
			$verschil=cirPlus($aspQuery['pos'])-cirPlus($startpunt);
			$dir="D";
		}
		if ($preSsnel<0 AND $postSsnel<0) {
			$verschil=cirPlus($startpunt)-cirPlus($aspQuery['pos']);
			$dir="R";
		}

		// verschil omzetten naar tijd/seconden van 
		$deler=$verschil/$preSsnel;
		$verschilSec=round($deler*$rekendag);
		$testStamp=$preStamp+$verschilSec;
	
	$gevonden=0;
	while ($gevonden<=5){ // loop om tijd te verfijnen..

			$whileDate=eDate($testStamp);	
			$whileTime=eTime($testStamp);	
			$whilePlanets=array();
			exec("swetest -edir$sweph -b$whileDate -p$pp -ut$whileTime -fls -roundsec -speed -g, -head -eswe",$whilePlanets);
			$line=explode(",",$whilePlanets[0]);
				$whilepos= cirPlus($line[0]);
				$whilesnel= $line[1];	
			$whileverschil=cirPlus($whilepos)-cirPlus($aspQuery['pos']);
			$whiledeler=$whileverschil/$whilesnel;
			$whileverschilSec=$whiledeler*$rekendag;
			$testStamp=$testStamp-$whileverschilSec;

			if (abs($whileverschilSec) < .5) { // tijdverschil is minder dan een halve seconde, afronden nu..
				$prResult[$resultcount]['planP']=$pp;
					$prResult[$resultcount]['planR']=$aspQuery['pla'];
					$prResult[$resultcount]['dir']=$dir;
					$prResult[$resultcount]['asp']=$aspQuery['asp'];
					$prResult[$resultcount]['pos']=cirkel($aspQuery['pos']);
					$prResult[$resultcount]['time']=$testStamp;
					$resultcount++;
				$gevonden =5;
			}
			$gevonden++;
	} // Einde While
	
	} // eind foreach
} // Einde functie



function Pramvis($preSpos, $preSsnel, $postSpos, $postSsnel, $preStamp, $postStamp, $pp){
	global $planeten;
	global $aspList;
	global $rekendag;
	global $sweph;
	global $prResult;
	global $resultcount;
	$startPos= cirPlus($preSpos); 
	$endPos=cirPlus($postSpos);

	$okTeller=0;
	$okList=array();
	foreach ($aspList as $alist){
	 if (cirPlus($alist['pos'])<= cirPlus($startPos) AND cirPlus($alist['pos'])>= cirPlus($endPos)) {
		$okList[$okTeller]['pla']=$alist['pla'];
		$okList[$okTeller]['asp']=$alist['asp'];
		$okList[$okTeller]['pos']=cirPlus($alist['pos']);
		$okTeller++;
	 } // einde if
	} // einde foreach
	usort(
		$okList, 
		function($a, $b) {
			$result = 0;
			if ($a['pos'] > $b['pos']) {
				$result = 1;
			} else if ($a['pos'] < $b['pos']) {
				$result = -1;
			}
			return $result; 
		}
	);
	
	$startpunt=cirPlus($startPos);
	$startsnel=$preSsnel;

	foreach ($okList as $aspQuery){

		if ($preSsnel>=0 AND $postSsnel>=0) {
			$verschil=cirPlus($aspQuery['pos'])-cirPlus($startpunt);
			$dir="D";
		}
		if ($preSsnel<0 AND $postSsnel<0) {
			$verschil=cirPlus($startpunt)-cirPlus($aspQuery['pos']);
			$dir="R";
		}

		// verschil omzetten naar tijd/seconden van 
		$deler=$verschil/$preSsnel;
		$verschilSec=round($deler*$rekendag);
		$testStamp=$preStamp+$verschilSec;
	
	$gevonden=0;
	while ($gevonden<=5){ // loop om tijd te verfijnen. (max 5x)

			$whileDate=eDate($testStamp);	
			$whileTime=eTime($testStamp);	
			$whilePlanets=array();
			exec("swetest -edir$sweph -b$whileDate -p$pp -ut$whileTime -fls -roundsec -speed -g, -head -eswe",$whilePlanets);
			$line=explode(",",$whilePlanets[0]);
				$whilepos= cirPlus($line[0]);
				$whilesnel= $line[1];	
			$whileverschil=cirPlus($whilepos)-cirPlus($aspQuery['pos']);
			$whiledeler=$whileverschil/$whilesnel;
			$whileverschilSec=$whiledeler*$rekendag;
			$testStamp=$testStamp-$whileverschilSec;

			if (abs($whileverschilSec) < .5) { // tijdverschil is minder dan een halve seconde, afronden nu..
				$prResult[$resultcount]['planP']=$pp;
					$prResult[$resultcount]['planR']=$aspQuery['pla'];
					$prResult[$resultcount]['dir']=$dir;
					$prResult[$resultcount]['asp']=$aspQuery['asp'];
					$prResult[$resultcount]['pos']=cirkel($aspQuery['pos']);
					$prResult[$resultcount]['time']=$testStamp;
					$resultcount++;
				$gevonden =5;
			}
			$gevonden++;
	} // Einde While
	
	} // eind foreach
} // Einde functie

///////////////////////////////////////////
// Vind het moment van omkeren planeet.. //
///////////////////////////////////////////
function omkeer($startpunt,$eindpunt,$qPlaneet){
    global $sweph;
	$fnTeller=0;
    $retroResult=array();
	
	//$halvedag was bedoeld voor stappen van 24 uur, maar we hebben nu stappen van meerdere dagen
	// bereken aantal dagen tussen $startpunt en $eindpunt
	$verschil=$eindpunt-$startpunt; // verschil in seconden tussen start en eindpunt
	$halvedag=round($verschil/2); // "halve dag" is de 1/2 van het verschil tussen start en eindpunt

	global $PlList;
	$qDatum=$startpunt;
	$startjaar=date("Y",$startpunt);
	$interval= 4;
	
	for ($rLoop=0; $rLoop<=$interval; $rLoop++){ // loop met periodieke steekproef (maand of 1/2 maand)
		
		$rPlaneet=array();
		$rLoopDatum=eDate($qDatum);
		$utTijd=eTime($qDatum);
		exec("swetest -edir$sweph -b$rLoopDatum -p$qPlaneet -ut$utTijd -fls -roundsec -speed -g, -head -eswe",$rPlaneet);
		$result=explode(",",$rPlaneet[0]);
		$planeten[$rLoop]['pos']= $result[0];
		$planeten[$rLoop]['snel']= $result[1];
		if ($rLoop>0) {

			if (($planeten[$rLoop]['snel'] > 0 AND $planeten[$rLoop-1]['snel'] < 0) OR ($planeten[$rLoop]['snel'] < 0 AND $planeten[$rLoop-1]['snel']> 0)){

				$eindpunt=mkStamp($rLoopDatum,$utTijd);
				$startpunt=$eindpunt-$halvedag;

				for ($dTeller=1; $dTeller<=24; $dTeller++) { // for ipv while, gelimiteerd tot 24, vind exact moment

					$midpunt = $startpunt+($eindpunt-$startpunt)/2;
					$verschil= $eindpunt-$startpunt;
					$dPlaneet=array();
					$dresult=array();
					$mLoopDatum=eDate($midpunt);
					$mLoopTijd=eTime($midpunt);
					exec("swetest -edir$sweph -b$mLoopDatum -p$qPlaneet -ut$mLoopTijd -fls -roundsec -speed -g, -head -eswe",$dPlaneet);
					$dresult=explode(",",$dPlaneet[0]);
					$mSnel= $dresult[1];
					if ($verschil<1) { 
						
						$retroResult[$fnTeller]["pla"] = $qPlaneet;
						$retroResult[$fnTeller]["time"] = $midpunt;
						$retroResult[$fnTeller]["pos"] = $dresult[0];
						if ($planeten[$rLoop-1]['snel'] > 0) { 
							$richting ="retrograde";
							$retroResult[$fnTeller]["dir"] = "R";
						}
						else {
							$richting = "direct";
							$retroResult[$fnTeller]["dir"] = "D";
						}
						$fnTeller++;
						break;
					}
					if (($planeten[$rLoop-1]['snel'] > 0 AND $mSnel < 0) OR ($planeten[$rLoop-1]['snel'] < 0 AND $mSnel > 0)) $eindpunt = $midpunt;
					else $startpunt = $midpunt;
					} // for dTeller
				} // If plsneten
				
			// interval moet hier vastgesteld worden en qDatum aangeparst worden..
			$qDatum=$qDatum + $halvedag;
		} // //if rloop
	}// for rLoop
	return($retroResult);
}
//////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Berekenen progressies zowel direct als retrograde
// vullen van array 
//////////////////////////////////////////////////////////////////////////////
function Progress($startPos, $preSsnel, $endPos, $postSsnel, $preStamp, $postStamp, $pp){
	global $planeten;
	global $aspList;
	global $rekendag;
	global $sweph;
	global $prResult;
	global $resultcount;

	$okTeller=0;
	$okList=array();
	if ($preSsnel>=0 AND $postSsnel>=0) { // Direct lopende planeten..
		foreach ($aspList as $alist){
			if ($alist['pos']>= $startPos AND $alist['pos']<= $endPos) {
				$okList[$okTeller]['pla']=$alist['pla'];
				$okList[$okTeller]['asp']=$alist['asp'];
				$okList[$okTeller]['pos']=$alist['pos'];
				$okTeller++;
			} // einde if
		} // einde foreach
	} // Einde if preSsnel
	if ($preSsnel<0 AND $postSsnel<0) { // Retrograde lopende planeten..
		foreach ($aspList as $alist){
			if ($alist['pos']>= $endPos AND $alist['pos']<= $startPos) {
				$okList[$okTeller]['pla']=$alist['pla'];
				$okList[$okTeller]['asp']=$alist['asp'];
				$okList[$okTeller]['pos']=$alist['pos'];
				$okTeller++;
			} // einde if
		} // einde foreach
		usort(	// omdraaien $okList i.v.m. omgekeerde richting..
			$okList, 
			function($a, $b) {
				$result = 0;
				if ($a['pos'] < $b['pos']) {
					$result = 1;
				} else if ($a['pos'] > $b['pos']) {
					$result = -1;
				}
				return $result; 
			}
		);
	}	// einde if preSsnel

	$startpunt=$startPos;
	$startsnel=$preSsnel;

	foreach ($okList as $aspQuery){

		if ($preSsnel>=0 AND $postSsnel>=0) {
			$verschil=$aspQuery['pos']-$startpunt;
			$dir="D";
			$deler=$verschil/$preSsnel;
			$verschilSec=round($deler*$rekendag); // waarom round ??
			$testStamp=$preStamp+$verschilSec;
		}
		if ($preSsnel<0 AND $postSsnel<0) {
			$verschil=$startpunt-$aspQuery['pos'];
			$dir="R";
			$deler=$verschil/$preSsnel;
			$verschilSec=round($deler*$rekendag); // waarom round ??
			$testStamp=$preStamp-$verschilSec;
		}

		$gevonden=0;
		while ($gevonden<=5){ // loop om tijd te verfijnen..
			$whileDate=eDate($testStamp);	
			$whileTime=eTime($testStamp);	
			$whilePlanets=array();
			exec("swetest -edir$sweph -b$whileDate -p$pp -ut$whileTime -fls -roundsec -speed -g, -head -eswe",$whilePlanets);
			$line=explode(",",$whilePlanets[0]);
			$whilepos= $line[0];
			$whilesnel= $line[1];	
			$whileverschil=$whilepos-$aspQuery['pos'];
			$whiledeler=$whileverschil/$whilesnel;
			$whileverschilSec=$whiledeler*$rekendag;
			$testStamp=$testStamp-$whileverschilSec;
			if (abs($whileverschilSec) < .5) { // tijdverschil is minder dan een halve seconde, afronden nu..
					$prResult[$resultcount]['planP']=$pp;
					$prResult[$resultcount]['planR']=$aspQuery['pla'];
					$prResult[$resultcount]['dir']=$dir;
					$prResult[$resultcount]['asp']=$aspQuery['asp'];
					$prResult[$resultcount]['pos']=$aspQuery['pos'];
					$prResult[$resultcount]['time']=$testStamp;
					$resultcount++;
				$gevonden =5;
			}
			$gevonden++;
		} // Einde While
	} // eind foreach
} // Einde functie

Function cirkel($pos){ // eenvoudige functie die ingevoerde waarde binnen de waarde van de cirkel 0-360 terugbrengt.
	if ($pos<0)$pos=$pos+360;
	if ($pos>=360)$pos=$pos-360;
	return($pos);
}
function circle($long) { // same as cirkel, need to be adjusted
    return fmod(($long + 360), 360);
}

/**
 * Get the current moon data for a given date.
 * @param DateTime $date The date and time to get the moon data for.
 * @return array The moon data, including longitude and speed.
 * @throws Exception If unable to get moon data.
 */
function get_moon_data(DateTime $date) {
    // Constants
    $SWEPHSRC = '../src';
    $SWEPH = '../eph';
    $COMMAND_TEMPLATE = "swetest -edir%s -b%s -p1 -ut%s -fls -roundsec -speed -g, -head -eswe";

    // Add swephsrc to PATH
    $currentPath = getenv("PATH");
    putenv("PATH=$currentPath:$SWEPHSRC");

    // Prepare command
    $formattedDate = $date->format('d.m.Y');
    $formattedTime = $date->format('H:i:s');
    $command = sprintf($COMMAND_TEMPLATE, $SWEPH, $formattedDate, $formattedTime);

    // Execute command
    $output = shell_exec($command);

    // Handle command execution failure
    if ($output === null) {
        throw new Exception('Unable to get moon data.');
    }

    // Parse command output
    $outputParts = explode(',', $output);
    if (count($outputParts) < 2) {
        throw new Exception('Unable to parse moon data.');
    }

    // Return moon data
    return [
        'longitude' => $outputParts[0],
        'speed' => $outputParts[1],
    ];
}

/**
 * Interpolate to find the exact time the Moon reaches a certain degree.
 * @param float $current_longitude The current longitude of the Moon.
 * @param float $degree The target degree to find the exact time for.
 * @param DateTime $current_date The current date and time.
 * @param float $current_speed The current speed of the Moon.
 * @return DateTime The exact date and time the Moon reaches the target degree.
 * @throws Exception If unable to get moon data.
 */
function interpolate_time($current_longitude, $degree, $current_date, $current_speed) {
    // Constants
    $SECONDS_IN_DAY = 86400;
    $THRESHOLD = 1 / 7200;  // Desired accuracy in degrees (1/7200 degrees = 0.5 arcseconds)
    $MAX_ITERATIONS = 10;  // Maximum number of attempts

    // Calculate the difference in longitude
    $longitude_difference = $current_longitude - $degree;
    $time_difference_hours = $longitude_difference / $current_speed;
    $time_difference_seconds = round($time_difference_hours * $SECONDS_IN_DAY);

    // Create a new DateTime object based on the current date, and subtract the time difference
    $exact_date = clone $current_date;
    $exact_date->sub(new DateInterval('PT' . $time_difference_seconds . 'S'));

    for ($i = 0; $i < $MAX_ITERATIONS; $i++) {
        $new_data = get_moon_data($exact_date);

        if (!isset($new_data['longitude'], $new_data['speed'])) {
            throw new Exception('Unable to get moon data.');
        }

        $new_longitude = $new_data['longitude'];
        $new_speed = $new_data['speed'];
        $new_difference = $new_longitude - $degree;

        if (abs($new_difference) < $THRESHOLD) {
            break;  // The estimated time is accurate enough
        }

        // Adjust the estimate based on the new longitude and the new speed
        $new_difference_hours = ($new_difference / $new_speed);
        $new_difference_seconds = round($new_difference_hours * $SECONDS_IN_DAY);

        if ($new_difference_seconds > 0) {
            $exact_date->sub(new DateInterval('PT' . $new_difference_seconds . 'S'));
        } else {
            $exact_date->add(new DateInterval('PT' . abs($new_difference_seconds) . 'S'));
        }
    }

    return $exact_date;
}
?>
