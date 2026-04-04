<?php 

// Set default timezone
date_default_timezone_set('UTC');

// Set HTTP Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Set Swiss Ephemeris Paths
$swephsrc = '../src';
$sweph = '../eph';

// Get and validate input
$inputData = json_decode(file_get_contents("php://input"), true);

if (!isset($inputData['lat'], $inputData['long'], $inputData['date'], $inputData['utc'])) {
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

$utdatenow = filter_var($inputData['date'], FILTER_SANITIZE_SPECIAL_CHARS);
$utnow = filter_var($inputData['utc'], FILTER_SANITIZE_SPECIAL_CHARS);
$bpos = filter_var($inputData['lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$lpos = filter_var($inputData['long'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

// Run swetest to calculate the radix houses
$houses = runSwetest($utdatenow, $utnow, $bpos, $lpos, $swephsrc, $sweph);

// Get current timestamp and transit planets
$stamp = time();
$trDate = eDate($stamp);
$trTime = eTime($stamp);
$transitPlanets = runSwetestTransit($trDate, $trTime, $swephsrc, $sweph);

// Find which house each planet is in
$inhuis = in_house_transit($houses, $transitPlanets);

// Prepare data to be sent
$data = prepareData($transitPlanets, $inhuis);

// Output data as JSON
echo json_encode($data);

// Functions
function eDate($stamp) {
    $stamp = round($stamp);
    return date("j.n.Y", $stamp);
}

function eTime($stamp) {
    $stamp = round($stamp);
    return date("H:i:s", $stamp);
}

function in_house_transit($huis, $pllon) {
    for ($i=0; $i<=12; $i++){
		for ($ii=1; $ii<=12; $ii++){
			   
			if ($ii < 12) $iii = $ii + 1;
			else $iii = 1; 
			
			if ($huis[$ii]['pos'] < $huis[$iii]['pos']) {
				if ($pllon[$i]['pos'] >= $huis[$ii]['pos']) {
					if ($pllon[$i]['pos'] < $huis[$iii]['pos']) $plhuis[$i] = $ii;
				}
			}
			
			if ($huis[$ii]['pos'] > $huis[$iii]['pos']) {
				if ($pllon[$i]['pos'] >= $huis[$ii]['pos']) {
					if ($pllon[$i]['pos'] < $huis[$iii]['pos'] + 360) $plhuis[$i] = $ii;
				}
				if ($pllon[$i]['pos'] < $huis[$ii]['pos']) {
					if ($pllon[$i]['pos'] < $huis[$iii]['pos']) $plhuis[$i] = $ii;
				}
			} // einde huisbepaling      
	   } // next ii
	} // next i
	
	return $plhuis;
}

function runSwetest($utdatenow, $utnow, $bpos, $lpos, $swephsrc, $sweph) {
    $houses = [];
    $PATH = '';
    putenv("PATH=$PATH:$swephsrc");
    exec("swetest -edir$sweph -p -b$utdatenow -house$lpos,$bpos,k -ut$utnow -fl -roundsec -speed -g -head -eswe", $houses);

    $teller = 1;
    foreach ($houses as $house) { 
        $huizen[$teller]['pos'] = $house;
        $teller++;
    }

    return $huizen;
}

function runSwetestTransit($trDate, $trTime, $swephsrc, $sweph) {
    $transitPlanets = [];
    exec("swetest -edir$sweph -b$trDate -p0123456789t -ut$trTime -fls -roundsec -speed -g, -head -eswe", $transitPlanets);

    $teller = 0;
    foreach ($transitPlanets as $tranPlanet) {
        $line = explode(",", $tranPlanet);
        $trPlaneten[$teller]['pos'] = $line[0];
        $trPlaneten[$teller]['snel'] = $line[1];
        $teller++; 
    }

    return $trPlaneten;
}

function prepareData($trPlaneten, $inhuis) {
    $data = [];
    for ($i = 0; $i <= 10; $i++) {
        $data[$i] = [
            'planet' => $i,
            'pos' => $trPlaneten[$i]['pos'],
            'snel' => $trPlaneten[$i]['snel'],
            'house' => $inhuis[$i]
        ];
    }

    return $data;
}

?>