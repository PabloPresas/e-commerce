


<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$cn = new mysqli(
    "localhost",
    "u774827812_admin",
    "Tikolito21",
    "u774827812_shop"
);

if ($cn->connect_error) {
    echo json_encode(["error" => "Fallo conexión DB"]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

/* =========================
   GET: HISTORIAL DETALLADO
   /ventas.php?detalle=1
========================= */
if ($method === "GET" && isset($_GET["detalle"])) {

    $sql = "
        SELECT 
            v.id,
            v.fecha,
            v.cantidad,
            v.total,
            p.codigo,
            p.titulo
        FROM ventas v
        INNER JOIN productos p ON p.id = v.producto_id
        ORDER BY v.fecha DESC
    ";

    $res = $cn->query($sql);

    $ventas = [];
    while ($row = $res->fetch_assoc()) {
        $ventas[] = [
            "id"       => (int)$row["id"],
            "fecha"    => $row["fecha"],
            "codigo"   => $row["codigo"],
            "titulo"   => $row["titulo"],
            "cantidad" => (int)$row["cantidad"],
            "total"    => (float)$row["total"]
        ];
    }

    echo json_encode($ventas, JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   GET: RESUMEN DE VENTAS
========================= */
if ($method === "GET") {

    $mes  = isset($_GET["mes"]) ? intval($_GET["mes"]) : null;
    $anio = isset($_GET["anio"]) ? intval($_GET["anio"]) : null;

    $where  = "";
    $types  = "";
    $params = [];

    if ($mes && $anio) {
        $where  = "WHERE MONTH(v.fecha) = ? AND YEAR(v.fecha) = ?";
        $types  = "ii";
        $params = [$mes, $anio];
    }

    $sqlResumen = "
        SELECT 
            v.producto_id,
            p.codigo,
            p.titulo,
            SUM(v.cantidad) AS total_vendida,
            SUM(v.total) AS total_facturado
        FROM ventas v
        INNER JOIN productos p ON p.id = v.producto_id
        $where
        GROUP BY v.producto_id, p.codigo, p.titulo
        ORDER BY total_facturado DESC
    ";

    $stmt = $cn->prepare($sqlResumen);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $resumen = [];
    while ($row = $res->fetch_assoc()) {
        $resumen[] = [
            "producto_id"     => (int)$row["producto_id"],
            "codigo"          => $row["codigo"],
            "titulo"          => $row["titulo"],
            "total_vendida"   => (int)$row["total_vendida"],
            "total_facturado" => (float)$row["total_facturado"]
        ];
    }
    $stmt->close();

    $sqlTotales = "
        SELECT 
            COALESCE(SUM(cantidad),0) AS items,
            COALESCE(SUM(total),0) AS facturado
        FROM ventas v
        $where
    ";

    $stmt2 = $cn->prepare($sqlTotales);
    if ($types) $stmt2->bind_param($types, ...$params);
    $stmt2->execute();
    $tot = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    echo json_encode([
        "totales" => [
            "items"     => (int)$tot["items"],
            "facturado" => (float)$tot["facturado"]
        ],
        "top"     => array_slice($resumen, 0, 5),
        "resumen" => $resumen
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =========================
   POST: REGISTRAR VENTA + BAJAR STOCK
========================= */
if ($method === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    $producto_id = intval($data["producto_id"] ?? 0);
    $cantidad    = intval($data["cantidad"] ?? 0);
    $total       = floatval($data["total"] ?? 0);

    if ($producto_id <= 0 || $cantidad <= 0 || $total <= 0) {
        echo json_encode(["error" => "Datos inválidos"]);
        exit;
    }

    try {
        $cn->begin_transaction();

        // 1. Verificar stock
        $stmt = $cn->prepare("
            SELECT stock 
            FROM productos 
            WHERE id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res || $res["stock"] < $cantidad) {
            throw new Exception("Stock insuficiente");
        }

        // 2. Descontar stock
        $stmt = $cn->prepare("
            UPDATE productos 
            SET stock = stock - ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $cantidad, $producto_id);
        $stmt->execute();
        $stmt->close();

        // 3. Insertar venta
        $stmt = $cn->prepare("
            INSERT INTO ventas (producto_id, cantidad, total, fecha)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iid", $producto_id, $cantidad, $total);
        $stmt->execute();
        $stmt->close();

        $cn->commit();

        echo json_encode(["success" => true]);
        exit;

    } catch (Exception $e) {
        $cn->rollback();
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
}

echo json_encode(["error" => "Método no permitido"]);
$cn->close();
