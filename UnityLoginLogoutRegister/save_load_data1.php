<?php
include 'config.php';

$action = $_POST['action'];
$userId = $_POST['userId'];
$levelId = $_POST['levelId'] + 1;
$completionStatus = $_POST['completionStatus'];
$score = $_POST['score'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    switch ($action) {
        case 'save':
            // Verifica que el usuario exista
            $stmt = $connection->prepare("SELECT * FROM users WHERE user_id = :userId");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                die("Error: User ID $userId does not exist.");
            }

            // Verifica que el nivel exista
            $stmt = $connection->prepare("SELECT * FROM levels WHERE level_id = :levelId");
            $stmt->bindParam(':levelId', $levelId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                die("Error: Level ID $levelId does not exist.");
            }

            // Verifica si el usuario ya tiene un registro para este nivel
            $stmt = $connection->prepare("SELECT * FROM user_levels WHERE user_id = :userId AND level_id = :levelId");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':levelId', $levelId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Actualiza el registro existente
                $query = $connection->prepare("UPDATE user_levels SET completion_status = :completionStatus, score = :score WHERE user_id = :userId AND level_id = :levelId");
            } else {
                // Inserta un nuevo registro
                $query = $connection->prepare("INSERT INTO user_levels (user_id, level_id, completion_status, score) VALUES (:userId, :levelId, :status_enable, :score)");
            }
            $enable ="1";
            $query->bindParam(':userId', $userId, PDO::PARAM_INT);
            $query->bindParam(':levelId', $levelId, PDO::PARAM_INT);
            $query->bindParam(':score', $score, PDO::PARAM_INT);
            $query->bindParam(':status_enable', $enable, PDO::PARAM_INT);

            $query->execute();
            echo "Save successful";
            break;

        case 'load':
            $userId = $_POST['userId'];

            $qery = $connection->prepare("SELECT ul.level_id, ul.completion_status, ul.score, l.level_name 
                    FROM user_levels ul 
                    JOIN levels l ON ul.level_id = l.level_id 
                    WHERE ul.user_id = '$userId'");
            $query->execute();


            if ($query->rowCount() == 0) {
                echo "No se encontraron datos para el usuario";
            }
            break;
    }
}
