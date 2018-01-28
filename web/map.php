<?php
require_once("util.php");
require_once("connect.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$all_nodes = array();
$sql = "SELECT id, name,lat, lon FROM nodes";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$all_nodes[] = $row;
	}
}

echo "<script>";
echo "var nodes = [];";
for($i = 0; $i < sizeof($all_nodes);$i++)
{
	echo "nodes.push({
		'id': ".$all_nodes[$i]["id"].",
		'name': '".$all_nodes[$i]["name"]."',
		'lat':".$all_nodes[$i]["lat"].",
		'lon':".$all_nodes[$i]["lon"]."
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
	<div id="mapid" style="height: 100%;"></div>

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

		nodes.forEach(function(node) {
			var circle = L.circle([node.lat, node.lon], {
				color: 'red',
				fillColor: '#f03',
				fillOpacity: 0.5,
				radius: 500
			}).bindPopup("<a href=\"graph.php?node_id="+node.id+"\">"+node.name+"</a>").addTo(mymap);
		});

	</script>


</body>
</html>