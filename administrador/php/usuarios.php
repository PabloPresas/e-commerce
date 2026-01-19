

<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$conexion = new mysqli("localhost", "u774827812_admin", "Tikolito21", "u774827812_shop");
if ($conexion->connect_error) {
    die(json_encode(["error" => "Fallo conexión DB: " . $conexion->connect_error]));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $result = $conexion->query("SELECT id, nombre, email, rol FROM usuarios WHERE id = $id");
        echo json_encode($result->fetch_assoc());
    } else {
        $result = $conexion->query("SELECT id, nombre, email, rol FROM usuarios");
        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        echo json_encode($usuarios);
    }
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $nombre = $conexion->real_escape_string($data['nombre']);
    $email = $conexion->real_escape_string($data['email']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $rol = $conexion->real_escape_string($data['rol']);
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre, $email, $password, $rol);
    $stmt->execute();
    echo json_encode(["success" => true]);
    exit;
}

if ($method === 'PUT' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $data = json_decode(file_get_contents("php://input"), true);
    $fields = [];
    $params = [];

    if (isset($data['nombre'])) {
        $fields[] = "nombre = ?";
        $params[] = $data['nombre'];
    }
    if (isset($data['email'])) {
        $fields[] = "email = ?";
        $params[] = $data['email'];
    }
    if (!empty($data['password'])) {
        $fields[] = "password = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    if (isset($data['rol'])) {
        $fields[] = "rol = ?";
        $params[] = $data['rol'];
    }
    if (count($fields) > 0) {
        $sql = "UPDATE usuarios SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;
        $stmt = $conexion->prepare($sql);
        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    }
    echo json_encode(["success" => true]);
    exit;
}

if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conexion->query("DELETE FROM usuarios WHERE id = $id");
    echo json_encode(["success" => true]);
    exit;
}

$conexion->close();
?>
