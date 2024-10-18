<?php
require 'config.php';

// Retrieve level data from the database
$sql = "SELECT * FROM users";
$result = $connection->query($sql);

if ($result->rowCount() > 0) {
    // Output data of each row
    $levels = array();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $levels[] = $row;
    }

    // Output data as formatted JSON
    header('Content-Type: application/json'); // Set content type header
    echo json_encode($levels, JSON_PRETTY_PRINT);
} else {
    echo json_encode(array("error" => "No data found"));
}
