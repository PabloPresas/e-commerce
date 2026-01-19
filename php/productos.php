

<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

/* =========================
   CONEXIÓN DB (LA TUYA)
========================= */
$cn = new mysqli(
    "localhost",
    "u774827812_admin",
    "Tikolito21",
    "u774827812_shop"
);

if ($cn->connect_error) {
    echo json_encode(["error" => "Error conexión DB"]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

/* =========================
   OBTENER PRODUCTOS
========================= */
if ($method === "GET") {

    $res = $cn->query("
        SELECT id, codigo, titulo, categoria, imagen, precio, stock
        FROM productos
        ORDER BY id DESC
    ");

    $productos = [];
    while ($row = $res->fetch_assoc()) {
        $productos[] = $row;
    }

    echo json_encode($productos, JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   AGREGAR PRODUCTO
========================= */
if ($method === "POST") {

    $titulo    = $_POST["titulo"];
    $categoria = $_POST["categoria"];
    $precio    = floatval($_POST["precio"]);
    $stock     = intval($_POST["stock"]);

    /* ===== CÓDIGO AUTOMÁTICO ===== */
    $prefijos = [
        "camisetas"  => "CAM",
        "pantalones" => "PAN",
        "abrigos"    => "ABR"
    ];

    $pref = $prefijos[$categoria] ?? "GEN";

    $stmt = $cn->prepare("
        SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) AS ultimo
        FROM productos
        WHERE categoria = ?
    ");
    $stmt->bind_param("s", $categoria);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    $siguiente = ($res["ultimo"] ?? 0) + 1;
    $codigo = $pref . "-" . str_pad($siguiente, 3, "0", STR_PAD_LEFT);

    /* ===== SUBIR IMAGEN ===== */
    if (!isset($_FILES["imagen"])) {
        echo json_encode(["error" => "No se recibió imagen"]);
        exit;
    }

    $nombreImg = time() . "_" . basename($_FILES["imagen"]["name"]);
    $rutaFisica = __DIR__ . "/../img/$categoria/$nombreImg";
    $rutaDB = "/img/$categoria/$nombreImg";

    if (!move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaFisica)) {
        echo json_encode(["error" => "Error al subir imagen"]);
        exit;
    }

    /* ===== INSERT ===== */
    $stmt = $cn->prepare("
        INSERT INTO productos (codigo, titulo, categoria, imagen, precio, stock)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssdi",
        $codigo,
        $titulo,
        $categoria,
        $rutaDB,
        $precio,
        $stock
    );

    $stmt->execute();

    echo json_encode(["success" => true]);
    exit;
}

/* =========================
   AGREGAR STOCK
========================= */
if ($method === "PUT") {

    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data["id"]);
    $cantidad = intval($data["cantidad"]);

    if ($cantidad <= 0) {
        echo json_encode(["error" => "Cantidad inválida"]);
        exit;
    }

    $cn->query("
        UPDATE productos
        SET stock = stock + $cantidad
        WHERE id = $id
    ");

    echo json_encode(["success" => true]);
    exit;
}

/* ========================= */
echo json_encode(["error" => "Método no permitido"]);
