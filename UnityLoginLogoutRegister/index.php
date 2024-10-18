<?php

include("config.php");

function registerUser($username, $password, $email, $sex)
{
    global $connection;

    // Validar el correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 4; // Email inválido
        return;
    }

    // Validar la seguridad de la contraseña
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||    // Al menos una letra mayúscula
        !preg_match('/[a-z]/', $password) ||    // Al menos una letra minúscula
        !preg_match('/[0-9]/', $password) ||    // Al menos un número
        !preg_match('/[\W]/', $password)        // Al menos un carácter especial
    ) {
        echo 5; // Contraseña no segura
        return;
    }

    // Verificar si el nombre de usuario ya existe
    $stmt = $connection->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        echo 3; // Usuario ya existe
        return;
    }

    // Encriptar la contraseña y registrar al usuario
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $connection->prepare("INSERT INTO users (username, email, password, sex) VALUES (:username, :email, :password, :sex)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':sex', $sex);

    if ($stmt->execute()) {
        echo 1; // Registro exitoso
    } else {
        echo 2; // Error al registrar
    }
}

function loginUser($username, $password)
{
    global $connection;

    // Buscar el usuario en la base de datos
    $stmt = $connection->prepare("SELECT user_id, password FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        echo json_encode(['userId' => $user['user_id'], 'status' => 1]); // Inicio exitoso con ID de usuario
    } else {
        echo ($user) ? 3 : 4; // 3: Contraseña incorrecta, 4: Usuario no encontrado
    }
}

function logoutUser()
{
    session_start();
    session_unset();
    session_destroy();
    echo "Sesión cerrada";
}

function getUserInfo($userId)
{
    global $connection;

    // Obtener la información del usuario
    $stmt = $connection->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Obtener el puntaje del nivel 1
        $stmt2 = $connection->prepare("SELECT score FROM user_levels WHERE user_id = :user_id AND level_id = 1");
        $stmt2->bindParam(':user_id', $userId);
        $stmt2->execute();
        $level1Score = $stmt2->fetchColumn();

        // Obtener el puntaje del nivel 2
        $stmt3 = $connection->prepare("SELECT score FROM user_levels WHERE user_id = :user_id AND level_id = 2");
        $stmt3->bindParam(':user_id', $userId);
        $stmt3->execute();
        $level2Score = $stmt3->fetchColumn();

        // Si hay puntajes, los agregamos a los datos del usuario
        $user['level1_score'] = $level1Score ? $level1Score : 0;
        $user['level2_score'] = $level2Score ? $level2Score : 0; // Si no hay puntaje, devolver 0

        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
}



function updateUser($userId, $username, $password, $email, $sex)
{
    global $connection;

    // Validar el correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 4;
        return;
    }

    // Encriptar la nueva contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $connection->prepare("UPDATE users SET username = :username, email = :email, password = :password, sex = :sex WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':sex', $sex);

    if ($stmt->execute()) {
        echo 1; // Actualización exitosa
    } else {
        echo 2; // Error al actualizar
    }
}

// Lógica de enrutamiento de solicitudes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["username"], $_POST["password"], $_POST["email"], $_POST["sex"])) {
        registerUser($_POST["username"], $_POST["password"], $_POST["email"], $_POST["sex"]);
    } elseif (isset($_POST["loginUsername"], $_POST["loginPassword"])) {
        loginUser($_POST["loginUsername"], $_POST["loginPassword"]);
    } elseif (isset($_POST["logout"])) {
        logoutUser();
    } elseif (isset($_POST["updateUserId"], $_POST["updateUsername"], $_POST["updatePassword"], $_POST["updateEmail"], $_POST["updateSex"])) {
        updateUser($_POST["updateUserId"], $_POST["updateUsername"], $_POST["updatePassword"], $_POST["updateEmail"], $_POST["updateSex"]);
    } else {
        echo "Solicitud POST inválida.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['user_id'])) {
        getUserInfo($_GET['user_id']);
    } else {
        echo json_encode(['error' => 'User ID not provided']);
    }
} else {
    echo "Método no permitido";
}
