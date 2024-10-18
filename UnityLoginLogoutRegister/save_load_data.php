<?php
include 'config.php';

function getUserLevels($connection, $userId)
{
    $stmt = $connection->prepare("SELECT level_id, completion_status, score FROM user_levels WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        "lastUnlockedLevel" => getLastUnlockedLevel($connection, $userId),
        "levelItems" => $levels
    ];
}

function getLastUnlockedLevel($connection, $userId)
{
    $stmt = $connection->prepare("SELECT MAX(level_id) as last_unlocked FROM user_levels WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return ($result['last_unlocked'] !== null) ? $result['last_unlocked'] + 1 : 1;
}

function saveData($connection, $userId, $levelId, $completionStatus, $score)
{
    // echo "completion: jose " . $completionStatus;
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

    // Inicia la transacción
    $connection->beginTransaction();

    // Verifica si el usuario ya tiene un registro para este nivel
    $stmt = $connection->prepare("SELECT * FROM user_levels WHERE user_id = :userId AND level_id = :levelId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':levelId', $levelId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Actualiza el registro existente
        // echo "update";
        $query = $connection->prepare("UPDATE user_levels SET completion_status = :completionStatus, score = :score WHERE user_id = :userId AND level_id = :levelId");
    } else {
        // Inserta un nuevo registro
        $query = $connection->prepare("INSERT INTO user_levels (user_id, level_id, completion_status, score) VALUES (:userId, :levelId, :completionStatus, :score)");
        // echo "insert";
    }
    // Asigna valores a los parámetros
    $query->bindParam(':userId', $userId, PDO::PARAM_INT);
    $query->bindParam(':levelId', $levelId, PDO::PARAM_INT);
    $query->bindParam(':completionStatus', $completionStatus, PDO::PARAM_INT);
    $query->bindParam(':score', $score, PDO::PARAM_INT);

    $query->execute();

    // Si el nivel actual se completa, verifica e inserta el próximo nivel
    if ($completionStatus == 1) { // Si el nivel se completa
        $next_level = $levelId + 1;

        // Verifica que el próximo nivel exista antes de intentar insertarlo
        $stmt = $connection->prepare("SELECT * FROM levels WHERE level_id = :nextLevel");
        $stmt->bindParam(':nextLevel', $next_level, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Verifica si ya existe un registro para el próximo nivel
            $stmt = $connection->prepare("SELECT * FROM user_levels WHERE user_id = :userId AND level_id = :nextLevel");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':nextLevel', $next_level, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                // Inserta un nuevo registro para el próximo nivel
                $insert_next_level = $connection->prepare("INSERT INTO user_levels (user_id, level_id, completion_status, score) VALUES (:userId, :nextLevel, 1, 0)");
                $insert_next_level->bindParam(':userId', $userId, PDO::PARAM_INT);
                $insert_next_level->bindParam(':nextLevel', $next_level, PDO::PARAM_INT);
                $insert_next_level->execute();
            }
        }
    }

    // Confirma la transacción
    $connection->commit();
    loadData($connection, $userId);
}


function loadData($connection, $userId)
{
    $query = $connection->prepare("SELECT l.level_id, l.level_name,
                                        COALESCE(ul.completion_status, '0') AS completion_status,
                                        COALESCE(ul.score, 0) AS score
                                    FROM levels l LEFT JOIN 
                                        user_levels ul ON l.level_id = ul.level_id AND ul.user_id = :userId");
    $query->bindParam(':userId', $userId, PDO::PARAM_INT);
    $query->execute();

    if ($query->rowCount() == 0) {
        echo json_encode(new stdClass()); // Devuelve un objeto JSON vacío
    } else {
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $userId = $_POST['userId'];

    switch ($action) {
        case 'save':
            $levelId = $_POST['levelId'];
            $completionStatus = $_POST['completionStatus'];
            $score = $_POST['score'];
            saveData($connection, $userId, $levelId, $completionStatus, $score);
            break;

        case 'load':
            loadData($connection, $userId);
            break;

        default:
            echo "Invalid action";
            break;
    }
}
?>
