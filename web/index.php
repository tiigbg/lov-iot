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


?>


<html>

<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>

<body>

	<h1>LOV-IoT</h1>
	<p>Välkommen!</p>
	<a href="map.php"><button>Map</button></a>

	<ul>
		<?php
			for($i = 0; $i < sizeof($all_nodes);$i++)
			{
				echo "<li><a href=\"graph.php?node_id=".$all_nodes[$i]["id"]."\">".$all_nodes[$i]["name"]."</a></li>";
			}

		?>
	</ul>

</body>
</html>