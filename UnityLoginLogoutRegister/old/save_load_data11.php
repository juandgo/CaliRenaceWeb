<?php
include 'config.php';

function saveData($connection, $userId, $levelId, $completionStatus, $score)
{
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
        $query = $connection->prepare("INSERT INTO user_levels (user_id, level_id, completion_status, score) VALUES (:userId, :levelId, :completionStatus, :score)");
    }

    $query->bindParam(':userId', $userId, PDO::PARAM_INT);
    $query->bindParam(':levelId', $levelId, PDO::PARAM_INT);
    $query->bindParam(':completionStatus', $completionStatus, PDO::PARAM_STR);
    $query->bindParam(':score', $score, PDO::PARAM_INT);

    $query->execute();
    // Devuelve los datos actualizados del usuario
    $userLevels = getUserLevels($connection, $userId);
    echo json_encode($userLevels);
}
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
    // Asume que tienes una forma de determinar el último nivel desbloqueado
    $stmt = $connection->prepare("SELECT MAX(level_id) as last_unlocked FROM user_levels WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['last_unlocked'] + 1 ?? 0;
}
function loadData($connection, $userId)
{
    // Obtener datos de los niveles del usuario
    $query = $connection->prepare("SELECT ul.level_id, ul.completion_status, ul.score, l.level_name 
                                    FROM user_levels ul 
                                    JOIN levels l ON ul.level_id = l.level_id 
                                    WHERE ul.user_id = :userId
                                    ORDER BY ul.level_id ASC");
    $query->bindParam(':userId', $userId, PDO::PARAM_INT);
    $query->execute();

    if ($query->rowCount() == 0) {
        echo json_encode(new stdClass()); // Devuelve un objeto JSON vacío si no se encuentran datos
    } else {
        $data = $query->fetchAll(PDO::FETCH_ASSOC);

        // Identificar el último nivel completado
        $lastCompletedLevel = null;
        foreach ($data as $level) {
            if ($level['completion_status']) {
                $lastCompletedLevel = $level['level_id'];
            }
        }

        // Clasificar los niveles como desbloqueados o bloqueados
        $unlockedLevels = [];
        $lockedLevels = [];
        $nextUnlocked = true; // Flag to check the first unlocked but incomplete level

        foreach ($data as $level) {
            if ($level['completion_status']) {
                $unlockedLevels[] = $level;
            } elseif ($nextUnlocked && ($lastCompletedLevel === null || $level['level_id'] == $lastCompletedLevel + 1)) {
                // El siguiente nivel desbloqueado pero no completado
                $unlockedLevels[] = $level;
                $nextUnlocked = false; // Solo desbloquea el primer nivel no completado después del último completado
            } else {
                $lockedLevels[] = $level;
            }
        }

        // Estructurar el resultado
        $result = [
            'unlockedLevels' => $unlockedLevels,
            'lockedLevels' => $lockedLevels
        ];

        echo json_encode($result);
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $userId = $_POST['userId'];

    switch ($action) {
        case 'save':
            $levelId = $_POST['levelId'] + 1;
            $completionStatus = $_POST['completionStatus'];
            $score = $_POST['score'] + 2;
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
