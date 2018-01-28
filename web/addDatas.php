<?php
require_once("connect.php");
require_once("util.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(isset($_GET["sensor_ids"]) && !empty($_GET["sensor_ids"]))
{
	//clean_input(filter_input(INPUT_GET, "sensor_id", FILTER_VALIDATE_INT))
	$sensor_ids = array();
	foreach ($_GET["sensor_ids"] as $sensor_id) {
		if(is_int(filter_var($sensor_id, FILTER_VALIDATE_INT)))
		{
			$sensor_ids[] = clean_input(filter_var($sensor_id, FILTER_VALIDATE_INT));
		}
	}

}
else
{
	die();
}

if(isset($_GET["data_values"]) && !empty($_GET["data_values"]))
{
	//clean_input(filter_input(INPUT_GET, "sensor_id", FILTER_VALIDATE_INT))
	$data_values = array();
	foreach ($_GET["data_values"] as $data_value) {
		if(is_numeric(clean_input($data_value)))
		{
			$data_values[] = clean_input($data_value);
		}
	}

}
else
{
	die();
}

if(count($sensor_ids) != count($data_values))
{
	die();
}

for ($i=0; $i < count($sensor_ids); $i++) { 
	$sensor_id = $sensor_ids[$i];
	$data_value = $data_values[$i];

	$sql = "INSERT INTO data (sensor_id, value)
	VALUES ($sensor_id, $data_value)";

	if ($conn->query($sql) === TRUE) {
	    echo "Added data successfully.<br>";
	} else {
	    echo "Error: " . $sql . "<br>" . $conn->error;
	}
}

$conn->close();
?>