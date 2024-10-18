<?php
// Configuración de la base de datos
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "dbgame";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Función para obtener el nivel de un usuario
function getLevel($userId) {
    global $conn;
    $sql = "SELECT ul.level_id, l.level_name, ul.score FROM user_levels ul 
            JOIN levels l ON ul.level_id = l.level_id
            WHERE ul.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    return json_encode($data);
}

// Función para actualizar el nivel de un usuario
function updateLevel($userId, $levelId, $score) {
    global $conn;
    $sql = "UPDATE user_levels SET score = ? WHERE user_id = ? AND level_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $score, $userId, $levelId);
    if ($stmt->execute()) {
        return json_encode(["status" => "success"]);
    } else {
        return json_encode(["status" => "error", "message" => $conn->error]);
    }
}

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $userId = $_POST['user_id'];

    if ($action === 'getLevel') {
        echo getLevel($userId);
    } elseif ($action === 'updateLevel') {
        $levelId = $_POST['level_id'];
        $score = $_POST['score'];
        echo updateLevel($userId, $levelId, $score);
    } else {
        echo json_encode(["status" => "error", "message" => "Acción no válida"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Método de solicitud no válido"]);
}

$conn->close();
?>
