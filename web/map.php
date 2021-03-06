<?php
require_once("util.php");
require_once("connect.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$all_sensor_types = array();
$sql = "SELECT id, quantity, unit FROM sensor_types";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$all_sensor_types[] = $row;
	}
}

$typeSelected = false;
$sensor_type_id = 0;
$selected_nodes = array();

if(isset($_GET["sensor_type"]) && is_int(filter_input(INPUT_GET, "sensor_type", FILTER_VALIDATE_INT)))
{
	// if sensortype with sensor_type_id exists
	$sensor_type_id = clean_input(filter_input(INPUT_GET, "sensor_type", FILTER_VALIDATE_INT));
	$sql = "SELECT id FROM sensor_types WHERE id=$sensor_type_id LIMIT 1";
	$result = $conn->query($sql);
	if ($result->num_rows > 0)
	{
		$typeSelected = true;
		// if sensor with sensor_id exists
		
		$sql = "SELECT sensors.id as sensor_id, nodes.id as node_id, nodes.name as name, nodes.lat as lat, nodes.lon as lon FROM sensors INNER JOIN nodes ON sensors.node_id=nodes.id WHERE type_id=$sensor_type_id";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$selected_nodes[] = $row;
			}
		}

		// get latest data point for each sensor
		$data_points = array();
		for($i = 0; $i < sizeof($selected_nodes);$i++)
		{
			$sql = "SELECT value FROM data WHERE sensor_id=".$selected_nodes[$i]["sensor_id"];
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$data_points[$selected_nodes[$i]["sensor_id"]] = $row["value"];
			}
			else
			{
				// no data found for this sensor
				$data_points[$selected_nodes[$i]["sensor_id"]] = 0;
			}
		}

	}
}

if(!$typeSelected)
{
	// find all nodes
	$sql = "SELECT id as node_id, name,lat, lon FROM nodes";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$selected_nodes[] = $row;
		}
	}
}

echo "<script>";
echo "var nodes = [];";
for($i = 0; $i < sizeof($selected_nodes);$i++)
{
	$data = 0;
	if($typeSelected)
	{
		$data = $data_points[$selected_nodes[$i]["sensor_id"]];
	}
	echo "nodes.push({
		'id': ".$selected_nodes[$i]["node_id"].",
		'name': '".$selected_nodes[$i]["name"]."',
		'lat':".$selected_nodes[$i]["lat"].",
		'lon':".$selected_nodes[$i]["lon"].",
		'data':".$data."
	});";
}
echo "</script>";

?>


<html>

<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

	<!-- Leaflet for maps: http://leafletjs.com/examples.html -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css"
	integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ=="
	crossorigin=""/>
	<!-- Make sure you put this AFTER Leaflet's CSS -->
 <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js"
   integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw=="
   crossorigin=""></script>

 <script type="text/javascript" src="http://maps.stamen.com/js/tile.stamen.js?v1.3.0"></script>
</head>

<body>

	<div style="height: 10%;">
		<form action="map.php" method="get">
		Type: <select name="sensor_type" onchange="this.form.submit()">
		<?php
			if(!$typeSelected)
			{
				echo "<option value=\"-1\" selected>All</option>";
			}
			else
			{
				echo "<option value=\"-1\">All</option>";
			}
			for($i = 0; $i < sizeof($all_sensor_types); $i++)
			{
				$selected = "";
				if($all_sensor_types[$i]["id"] == $sensor_type_id)
				{
					$selected = "selected";
					$selected_type_index = $i;
				}
				echo "<option value=\"".$all_sensor_types[$i]["id"]."\" ".$selected.">".$all_sensor_types[$i]["quantity"]."</option>";
			}
			?>
			</select>
		</form>
	</div>
	
	<div id="mapid" style="height: 90%;"></div>

	<script type="text/javascript">
		var mymap = L.map('mapid').setView([57.707094, 11.966844], 13);

		/*L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
			attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
			maxZoom: 18,
			id: 'mapbox.streets',
			accessToken: 'pk.eyJ1IjoibmllbHNzdyIsImEiOiJjamN4eW5xa2kzaHFhMndwZ2x1cTcxY3k2In0.CcrHZ_P_GN4Z0aKQyVvdfA'
		}).addTo(mymap);*/

		var layer = new L.StamenTileLayer("toner-lite");
		//var layer = new L.StamenTileLayer("toner");
		//var layer = new L.StamenTileLayer("watercolor");
		mymap.addLayer(layer);

		var low_red = 0;
		var low_green = 25;
		var high_green = 75;
		var high_red = 100;

		nodes.forEach(function(node) {
			var setColor ='blue';
			var popup_text = "<a href=\"graph.php?node_id="+node.id+"\">"+node.name+"</a>"
			if(<?php echo ($typeSelected)?"true":"false"; ?>)
			{
				if(node.data <= low_red || node.data >= high_red)
				{
					setColor = 'red';
				}
				else if(node.data < low_green || node.data > high_green)
				{
					setColor = 'orange';
				}
				else
				{
					setColor = 'green';
				}
				var unit = "";
				<?php
				if(isset($selected_type_index))
					echo "unit = \"".$all_sensor_types[$selected_type_index]["unit"]."\";";
				?>
				popup_text = "<a href=\"graph.php?node_id="+node.id+"\">"+node.name+"</a><br>"+node.data+" "+unit;
			}
			var circle = L.circle([node.lat, node.lon], {
				color: setColor,
				fillColor: setColor,
				fillOpacity: 0.5,
				radius: 500
			}).bindPopup(popup_text).addTo(mymap);
		});

	</script>


</body>
</html>