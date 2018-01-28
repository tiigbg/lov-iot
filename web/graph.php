<?php
require_once("util.php");
require_once("connect.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// get all nodes
$all_nodes = array();
$sql = "SELECT id, name FROM nodes";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$all_nodes[] = $row;
	}
}

// get all sensor types
$all_sensor_types = array();
$sql = "SELECT id, quantity, unit FROM sensor_types";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$all_sensor_types[$row["id"]] = $row;
	}
}


$sensor_ids = array();
// one sensor is chosen
if(isset($_GET["sensor_id"]) && is_int(filter_input(INPUT_GET, "sensor_id", FILTER_VALIDATE_INT)))
{
	$sensor_ids[] = clean_input(filter_input(INPUT_GET, "sensor_id", FILTER_VALIDATE_INT));
}

$all_sensors_node = array();
// one node is chosen, or chosen sensor_id is negative
$node_is_chosen = false;
if((isset($sensor_ids[0]) && $sensor_ids[0] < 0) || (isset($_GET["node_id"]) && is_int(filter_input(INPUT_GET, "node_id", FILTER_VALIDATE_INT))))
{
	if (isset($sensor_ids[0]) && $sensor_ids[0] < 0)
	{
		// sensor_id was set to negative, but no node_id was given
		// assuming the first node
		$node_id = 1;
	}
	else
	{
		$node_id = clean_input(filter_input(INPUT_GET, "node_id", FILTER_VALIDATE_INT));
	}
	$node_is_chosen = true;
}
else
{
	if(!isset($sensor_ids[0]))
	{
		// no input given. Taking the first one.
		$sensor_ids[0] = 1;
	}
	// find which node the one selected sensor belongs to
	$sql = "SELECT id, node_id FROM sensors WHERE id=".$sensor_ids[0]." LIMIT 1";
	$result = $conn->query($sql);
	if ($result->num_rows > 0)
	{
		$node_id = $result->fetch_assoc()["node_id"];
	}
}
// find all sensors of the current node
$sql = "SELECT id, type_id FROM sensors WHERE node_id=".$node_id." ";	
$result = $conn->query($sql);

if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		if($node_is_chosen)
		{
			$sensor_ids[] = $row["id"];
		}
		$all_sensors_node[] = $row;
	}
}


if(isset($_GET["latest"]) && is_int(filter_input(INPUT_GET, "latest", FILTER_VALIDATE_INT)))
{
	$latest = clean_input(filter_input(INPUT_GET, "latest", FILTER_VALIDATE_INT));
}
else
{
	$latest = 50000;
}


?>


<html>

<head>
	<script src="js/plotly-latest.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>

<body>

<div>
	<form action="graph.php" method="get">
	Node: <select name="node_id" onchange="this.form.submit()">
<?php
	for($i = 0; $i < sizeof($all_nodes); $i++)
	{
		$selected = "";
		if($all_nodes[$i]["id"] == $node_id)
		{
			$selected = "selected";
		}
		echo "<option value=\"".$all_nodes[$i]["id"]."\" ".$selected.">".$all_nodes[$i]["name"]."</option>";
	}
	?>
	</select>
</form>

<form action="graph.php" method="get">
	<input type="hidden" name="node_id" value="<?php echo $node_id; ?>" />
	Sensor: <select name="sensor_id" onchange="this.form.submit()">
	<?php
	if($node_is_chosen)
	{
		echo "<option value=\"-1\" selected>All</option>";
	}
	else
	{
		echo "<option value=\"-1\">All</option>";
	}
	for($i = 0; $i < sizeof($all_sensors_node); $i++)
	{
		$selected = "";
		if(!$node_is_chosen && $all_sensors_node[$i]["id"] == $sensor_ids[0])
		{
			$selected = "selected";
		}
		echo "<option value=\"".$all_sensors_node[$i]["id"]."\" ".$selected.">".$all_sensor_types[$all_sensors_node[$i]["type_id"]]["quantity"]."</option>";
	}
	?>
	</select>
</form>
</div>

<?php
	if (sizeof($sensor_ids) == 1) {
		echo "<div style=\"width:100%;height:100%;display:inline-block;\"><div id=\"graphHolder$sensor_ids[0]\"></div></div>";
	}
	else
	{
		for($i = 0; $i < sizeof($sensor_ids); $i++)
		{
			echo "<div style=\"width:48%;height:48%;display:inline-block;\"><div id=\"graphHolder$sensor_ids[$i]\"></div></div>";
		}
	}
?>
	<script type="text/javascript">
		$(document).ready(function(){
			<?php
				for($i = 0; $i < sizeof($sensor_ids); $i++)
				{
					echo "refreshGraph($sensor_ids[$i]);";
				}
			?>
		});

		function refreshGraph(nr){
			<?php 
			if(isset($latest))
			{
				echo "let latestString = \"&latest=$latest\";";
			}
			else
			{
				echo "let latestString = \"\";";
			}
			?>
			$('#graphHolder'+nr).load('getGraph.php?sensor_id='+nr+latestString, function(){
				setTimeout(function () {refreshGraph(nr);}, 1000);
			});
		}
	</script>
</body>
</html>