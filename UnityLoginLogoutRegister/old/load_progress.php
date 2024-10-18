<?php
require 'config.php'; // Asegúrate de que 'config.php' contiene la configuración de conexión a la base de datos

// Obtener datos del parámetro de consulta en la URL
$username = isset($_GET['username']) ? $_GET['username'] : null;

if ($username) {
    loadProgress($username);
} else {
    echo json_encode(['error' => 'No username provided']);
}

function loadProgress($username)
{
    global $connection;

    try {
        // Preparar la consulta SQL
        $stmt = $connection->prepare("SELECT * FROM users WHERE userId = :userId");

        // Vincular el parámetro y ejecutar la consulta
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        // Obtener los resultados
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json'); // Set content type header
        // Verificar si se encontraron resultados y devolverlos en formato JSON
        if ($result) {
            echo json_encode($result);
        } else {
            echo json_encode([]);
        }
    } catch (PDOException $e) {
        // Manejo de errores de la base de datos
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
