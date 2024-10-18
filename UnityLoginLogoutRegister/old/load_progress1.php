<?php
$servername = "localhost"; // Cambia esto si tu base de datos no está en localhost
$username = "root"; // Cambia esto por el nombre de usuario de tu base de datos
$password = ""; // Cambia esto por tu contraseña de base de datos
$dbname = "dbgame";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"));

// Preparar la consulta SQL
$sql = $conn->prepare("SELECT level, stars FROM players WHERE username = ?");
$sql->bind_param("s", $data->username);

// Ejecutar la consulta
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(["level" => $row["level"], "stars" => $row["stars"]]);
} else {
    echo json_encode(["level" => 0, "stars" => 0]);
}

$sql->close();
$conn->close();
?>