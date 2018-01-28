<?php
echo "starting. ";
require_once("connect.php");
require_once("util.php");


if(isset($_GET["sensor_id"]) && is_int(filter_input(INPUT_GET, "sensor_id", FILTER_VALIDATE_INT)))
{
	$sensor_id = clean_input(filter_input(INPUT_GET, "sensor_id", FILTER_VALIDATE_INT));
}
else
{
	echo is_int(clean_input($_GET["sensor_id"]))?'yes':'no';
	die();
}

if(isset($_GET["data_value"]) && is_numeric(clean_input($_GET["data_value"])))
{
	$data_value = clean_input($_GET["data_value"]);
}
else
{
	echo "data_value=".clean_input($_GET["data_value"]);
	die();
}

$sql = "INSERT INTO data (sensor_id, value)
VALUES ($sensor_id, $data_value)";

if ($conn->query($sql) === TRUE) {
    echo "Added data successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}


$conn->close();
?>