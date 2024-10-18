<?php
require 'config.php';

if (isset($_POST['username']) && isset($_POST['level']) && isset($_POST['score'])) {
    $username = $_POST['username'];
    $level = intval($_POST['level']);
    $score = intval($_POST['score']);

    try {
        // Obtener el ID del usuario
        $sqlUser = "SELECT user_id FROM users WHERE username = :username";
        $stmtUser = $connection->prepare($sqlUser);
        $stmtUser->execute(['username' => $username]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $user_id = intval($user['user_id']);

            // Verificar si el registro del nivel existe
            $sqlCheckLevel = "SELECT * FROM user_levels WHERE user_id = :user_id AND level_id = :level_id";
            $stmtCheck = $connection->prepare($sqlCheckLevel);
            $stmtCheck->execute(['user_id' => $user_id, 'level_id' => $level_id]);
            $record = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                // Si el registro existe, actualiza el puntaje si es mayor
                if ($score > intval($record['score'])) {
                    $sqlUpdate = "UPDATE user_levels SET score = :score, completed_at = NOW() WHERE user_id = :user_id AND level_id = :level_id";
                    $stmtUpdate = $connection->prepare($sqlUpdate);
                    $stmtUpdate->execute(['score' => $score, 'user_id' => $user_id, 'level_id' => $level_id]);
                    echo "Puntaje actualizado exitosamente.";
                } else {
                    echo "El puntaje nuevo no es mayor que el puntaje existente.";
                }
            } else {
                // Si el registro no existe, inserta un nuevo registro
                $sqlInsert = "INSERT INTO user_levels (user_id, level_id, score, completed_at) VALUES (:user_id, :level_id, :score, NOW())";
                $stmtInsert = $connection->prepare($sqlInsert);
                $stmtInsert->execute(['user_id' => $user_id, 'level_id' => $level_id, 'score' => $score]);
                echo "Puntaje insertado exitosamente.";
            }
        } else {
            echo "Usuario no encontrado.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Datos incompletos.";
}
?>
