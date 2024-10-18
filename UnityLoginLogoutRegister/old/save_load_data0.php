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
            // Preparar la consulta SQL
            $stmt = $connection->prepare("SELECT * FROM users WHERE user_id = :userId");

            // Vincular el parámetro y ejecutar la consulta
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();

            // Verifica que el nivel exista
            $stmt = $connection->prepare("SELECT * FROM levels WHERE level_id = :levelId");
            $stmt->bindParam(':levelId', $levelId);

            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                die("Error: Level ID $levelId does not exist.");
            }

            // Inserta o actualiza los datos en user_levels
            $query = $connection->prepare("INSERT INTO user_levels (user_id, level_id, completion_status, score)
                            VALUES (:userId, :levelId, :completionStatus, :score)
                            ON DUPLICATE KEY UPDATE completion_status=:completionStatus, score=:score");

            $query->bindParam(':userId', $userId, PDO::PARAM_INT);
            $query->bindParam(':levelId', $levelId, PDO::PARAM_INT);
            $query->bindParam(':completionStatus', $completionStatus, PDO::PARAM_STR);
            $query->bindParam(':score', $score, PDO::PARAM_INT);

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

