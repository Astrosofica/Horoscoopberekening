<?php
    // Add headers
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // Database connection settings, needs to be adjusted for online/offline.
    $servername = "localhost";
    $username = "ephemeris"; // root
    $password = "Adata=V12"; // no password
    $dbname = "ephemeris";

    // Set Swiss Ephemeris Paths
    $swephsrc = '../src';
    $sweph = '../eph';
    $PATH = "";

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

    // Data from the JSON object we need:
    $longitude =$data->longitude;
    $latitude =$data->latitude;
    $utcTime =$data->utcTime;
    $utcDateStr =$data->utcDateStr;
    $radix = array();
    
    putenv("PATH=$PATH:$swephsrc");
    exec("swetest -edir$sweph -b$utcDateStr -p0123456789t -ut$utcTime -fls -roundsec -speed -g, -head -eswe",$planets);
    
    $teller=0; 
    foreach ($planets as $planet){
        $line=explode(",",$planet);
            $radix[$teller]= $line[0];
        $teller++; 
    }
    
    exec("swetest -edir$sweph -p -b$utcDateStr -house$longitude,$latitude,k -ut$utcTime -fl -roundsec -speed -g -head -eswe",$houses);
    $teller=1;
    foreach ($houses as $house){ 
            $huizen[$teller]['pos']= $house;
        $teller++;
    }
    
    // Maak Ascendant en MC ook tot onderdeel van de radix array
    $radix[11]=$huizen[1]['pos'];
    $radix[12]=$huizen[10]['pos'];

    // wanneer huis ingress is geselecteerd. Maak de huizen ook tot onderdeel van de radix array
    $radix[101]=$huizen[1]['pos'];
    $radix[102]=$huizen[2]['pos'];
    $radix[103]=$huizen[3]['pos'];
    $radix[104]=$huizen[4]['pos'];
    $radix[105]=$huizen[5]['pos'];
    $radix[106]=$huizen[6]['pos'];
    $radix[107]=$huizen[7]['pos'];
    $radix[108]=$huizen[8]['pos'];
    $radix[109]=$huizen[9]['pos'];
    $radix[110]=$huizen[10]['pos'];
    $radix[111]=$huizen[11]['pos'];
    $radix[112]=$huizen[12]['pos'];


    // I need some more of the data from the received JSON object
    $startDate = $data->startDate;
    $endDate = $data->endDate;
    $tplanets = $data->tplanets;
    $rplanets = $data->rplanets;
    $aspects = $data->aspects;
    $hingress = $data->hingress; // house ingress flag

    // we need to make an array of all the sensitive points, include the positions of point, the planet number and the aspect degree.
    // in the future expand sensitive ponts with the house cusps 
    // perhaps even find out if we can calculate retrograde and direct motion of the planets

    foreach ($rplanets as $plNumber){
        foreach ($aspects as $aspect){
            $point = [];
            $point['long'] = in360($radix[$plNumber] + $aspect);
            $point['planet'] = $plNumber;
            $point['aspect'] = $aspect;
            $sensitivePoint[] = $point;
    
            if ($aspect <> 180 and $aspect <> 0){
                $point = [];
                $point['long'] = in360($radix[$plNumber] - $aspect);
                $point['planet'] = $plNumber;
                $point['aspect'] = $aspect;
                $sensitivePoint[] = $point;
            }
        }
    }
    
    // add the house cusps to the sensitive points if the hingress flag is set
    if ($hingress == 1){
        for($hkey = 101; $hkey <= 112; $hkey++) {
            $point = [];
            $point['long'] = $radix[$hkey];
            $point['planet'] = $hkey;
            $point['aspect'] = 0;
            $sensitivePoint[] = $point;
        }
    }

    // sort the array by the long value
    usort($sensitivePoint, 'sort_by_long');

    // create an array of the transiting planets and the tables that contain the data
    $tableName = [
        5 => 'jupiter',
        6 => 'saturn',
        7 => 'uranus',
        8 => 'neptune',
        9 => 'pluto'
      ];

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $transit = array();
    $i=0; // counter for the transits
    foreach ($tplanets as $tplanet) {
    foreach ($sensitivePoint as $key => $value) {

            $position = $sensitivePoint[$key]['long'];
            $table = $tableName[$tplanet];
            $query = "
            SELECT 
                s1.id AS ida,
                s1.date AS datea,
                s1.pos AS posa,
                s1.speed AS speeda,
                s2.pos AS posb
            FROM ".$table." s1
            JOIN ".$table." s2 ON s2.id = s1.id + 1
            WHERE
                ((s1.pos < ".$position." AND s2.pos > ".$position." AND s1.speed >= 0)
                OR (s1.pos > ".$position." AND s2.pos < ".$position." AND s1.speed < 0))
                AND (s1.date >= '".$startDate."' AND s1.date <= '".$endDate."')
            ORDER BY s1.id ASC";

            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                do {
                    $transit[$i]['date'] = $row['datea'];
                    $transit[$i]['speed'] = $row['speeda'];
                    $transit[$i]['rplanet'] = $sensitivePoint[$key]['planet'];
                    $transit[$i]['rlong'] = $radix[$sensitivePoint[$key]['planet']];
                    $transit[$i]['aspect'] = $sensitivePoint[$key]['aspect'];
                    $transit[$i]['tplanet'] = $tplanet;
                    $transit[$i]['tlong'] = $sensitivePoint[$key]['long'];
                    $i++;
                } while ($row = $result->fetch_assoc());
            } // end if
        } // end foreach
    } // end tplanets foreach
        usort($transit, 'cmp');

    // Prepare the response
    $response = new stdClass();

    // Add the data to the responseS
    $response->transit = $transit;

    // Convert the response object into a JSON string
    $json_response = json_encode($response);

    // Return the JSON response
    echo $json_response;


function in360($angle){
    if ($angle > 360){
        $angle = $angle - 360;
    }
    elseif ($angle < 0){
        $angle = $angle + 360;
    }
    return $angle;
}
// function to sort the array by the long value
function sort_by_long($a, $b) {
    return $a['long'] <=> $b['long'];
}
// sort by date
function cmp($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
}
?>

