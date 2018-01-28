<?php
require_once("connect.php");
require_once("util.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$x = array();
$y = array();

$limit = "";
if(isset($_GET["latest"]) && is_int(filter_input(INPUT_GET, "latest", FILTER_VALIDATE_INT)))
{
	$limit = " LIMIT ".clean_input(filter_input(INPUT_GET, "latest", FILTER_VALIDATE_INT));
}

if(isset($_GET["sensor_id"]) && is_int(filter_input(INPUT_GET, "sensor_id", FILTER_VALIDATE_INT)))
{
	$sensor_id = clean_input(filter_input(INPUT_GET, "sensor_id", FILTER_VALIDATE_INT));

	$sql = "SELECT timestamp, value FROM data WHERE sensor_id=".$sensor_id." ORDER BY timestamp DESC".$limit;
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
	    // output data of each row
	    
	    
	    while($row = $result->fetch_assoc()) {
	    	// plotly wants yyyy-mm-dd HH:MM:SS.ssssss'
        //$x[] = strtotime($row["timestamp"]);
        $x[] = $row["timestamp"];
        $y[] = $row["value"];
	    }
	} else {
	    //echo "0 results";
	}

	$node_name = "nameless";
	$quantity = "";
	$unit = "";

	$sql_sensor = "SELECT * FROM sensors WHERE id=".$sensor_id." LIMIT 1";
	$result_sensor = $conn->query($sql_sensor);
	if ($result_sensor->num_rows > 0) {
		$row_sensor = $result_sensor->fetch_assoc();

		$sql_sensor_type = "SELECT * FROM sensor_types WHERE id=".$row_sensor["type_id"]." LIMIT 1";
		$result_sensor_type = $conn->query($sql_sensor_type);
		if ($result_sensor_type->num_rows > 0) {
			$row = $result_sensor_type->fetch_assoc();
			$quantity = $row["quantity"];
			$unit = $row["unit"];
		}

		$sql_node = "SELECT * FROM nodes WHERE id=".$row_sensor["node_id"]." LIMIT 1";
		$result_node = $conn->query($sql_node);
		if ($result_node->num_rows > 0) {
			$row = $result_node->fetch_assoc();
			$node_name = $row["name"];
		}
	}
}



$conn->close();

$randId = rand();
?>


<div id="graphDiv<?php echo $randId; ?>" style="width:100%;height:100%;"></div>

<script>
	Plotly.plot( "graphDiv<?php echo $randId; ?>",
		[{
			x: [
				<?php
					for ($i = 0; $i < sizeof($x); $i++) {
						echo "'".$x[$i]."'";
						if($i<sizeof($x)-1)
							echo ",";
					}
				?>
			],
			y: [
				<?php
					for ($i = 0; $i < sizeof($y); $i++) {
						echo $y[$i];
						if($i<sizeof($y)-1)
							echo ",";
					}
				?>
			]
		}],
		{
			title: '<?php echo $quantity." at ".$node_name; ?>',
			margin: { t: 30 },
			xaxis: {
    		title: 'Time',
    	},
    	yaxis: {
    		title: '<?php echo $unit; ?>'
    	}
		}
	);
</script>