<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "root";
$db_name = "simpleshakeshear";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    print "No connection";
    die("Connection failed: " . $conn->connect_error);
} else {
}
?>