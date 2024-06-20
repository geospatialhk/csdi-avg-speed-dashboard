<?php
// Load the XML file
$xml = simplexml_load_file('https://resource.data.one.gov.hk/td/traffic-detectors/irnAvgSpeed-all.xml');

// Check if the file was loaded successfully
if ($xml === false) {
    echo "Failed to load XML file.";
    foreach(libxml_get_errors() as $error) {
        echo "\n", $error->message;
    }
    exit;
}

// Connect to PostgreSQL database
$conn = new PDO('pgsql:host=192.168.0.108;dbname=gisdb','pguser','pwd');


// Build GeoJSON feature collection array
$geojson = array(
   'type'      => 'FeatureCollection',
   'features'  => array()
);


// Iterate through each book and print details
foreach ($xml->segments->segment  as $segment) {
	
	if ($segment->valid == "Y") {
	
		$sql = 'SELECT route_id, '.$segment->speed.' as speed, ST_AsGeoJSON(ST_Transform((geom),4326),6) AS geojson FROM centerline where route_id='.$segment->segment_id;
		
		// Try query or error
		$rs = $conn->query($sql);
		if (!$rs) {
			echo 'An SQL error occured.\n';
			exit;
		}
		
		
		// Loop through rows to build feature arrays
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$properties = $row;
			// Remove geojson and geometry fields from properties
			unset($properties['geojson']);
			unset($properties['geom']);
			$feature = array(
				 'type' => 'Feature',
				 'geometry' => json_decode($row['geojson'], true),
				 'properties' => $properties
			);
			// Add feature arrays to feature collection array
			array_push($geojson['features'], $feature);
		}	
	}
}



//header('Content-type: application/json');
//echo json_encode($geojson, JSON_NUMERIC_CHECK);

file_put_contents('/var/www/html/irnAvgSpeed.json', json_encode($geojson, JSON_NUMERIC_CHECK));

$conn = NULL;

?>
