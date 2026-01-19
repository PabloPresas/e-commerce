


<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$cn = new mysqli(
    "localhost",
    "u774827812_admin",
    "Tikolito21",
    "u774827812_shop"
);

if ($cn->connect_error) {
    echo json_encode(["success" => false, "error" => "DB error"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$items = $data["items"] ?? [];

if (!count($items)) {
    echo json_encode(["success" => false, "error" => "Carrito vacío"]);
    exit;
}

$cn->begin_transaction();

try {

    foreach ($items as $item) {

        $producto_id = (int)$item["id"];
        $cantidad    = (int)$item["cantidad"];
        $precio      = (float)$item["precio"];
        $total       = $precio * $cantidad;

        /* 1. Ver stock actual */
        $stmt = $cn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row || $row["stock"] < $cantidad) {
            throw new Exception("Stock insuficiente");
        }

        /* 2. Descontar stock */
        $stmt = $cn->prepare(
            "UPDATE productos SET stock = stock - ? WHERE id = ?"
        );
        $stmt->bind_param("ii", $cantidad, $producto_id);
        $stmt->execute();
        $stmt->close();

        /* 3. Registrar venta */
        $stmt = $cn->prepare(
            "INSERT INTO ventas (producto_id, cantidad, total, fecha)
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param("iid", $producto_id, $cantidad, $total);
        $stmt->execute();
        $stmt->close();
    }

    $cn->commit();

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $cn->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

$cn->close();
