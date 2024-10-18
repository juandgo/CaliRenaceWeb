<?php
require 'config.php';

if (isset($_GET['username'])) {
    $username = $_GET['username'];

    try {
        // Obtener el ID del usuario
        $sqlUser = "SELECT user_id FROM users WHERE username = :username";
        $stmtUser = $connection->prepare($sqlUser);
        $stmtUser->execute(['username' => $username]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        // echo json_encode($user);
        if ($user) {
            $user_id = intval($user['user_id']);

            // Obtener los niveles y puntajes del usuario
            $sqlLevels = "SELECT level_id, score FROM user_levels WHERE user_id = :user_id";
            $stmtLevels = $connection->prepare($sqlLevels);
            $stmtLevels->execute(['user_id' => $user_id]);
            $levels = $stmtLevels->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($levels);
        } else {
            echo json_encode(["error" => "Usuario no encontrado."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Datos incompletos."]);
}
?>
