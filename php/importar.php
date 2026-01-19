<?php
header("Content-Type: application/json; charset=UTF-8");

$cn = new mysqli(
    "localhost",
    "u774827812_admin",
    "Tikolito21",
    "u774827812_shop"
);

if ($cn->connect_error) {
    echo json_encode(["error" => "DB error"]);
    exit;
}

$productos = [
    ["Abrigo 01", "abrigos", "01.jpg", 1000],
    ["Abrigo 02", "abrigos", "02.jpg", 1000],
    ["Abrigo 03", "abrigos", "03.jpg", 1000],
    ["Abrigo 04", "abrigos", "04.jpg", 1000],
    ["Abrigo 05", "abrigos", "05.jpg", 1000],

    ["Camiseta 01", "camisetas", "01.jpg", 1000],
    ["Camiseta 02", "camisetas", "02.jpg", 1000],
    ["Camiseta 03", "camisetas", "03.jpg", 1000],
    ["Camiseta 04", "camisetas", "04.jpg", 1000],
    ["Camiseta 05", "camisetas", "05.jpg", 1000],
    ["Camiseta 06", "camisetas", "06.jpg", 1000],
    ["Camiseta 07", "camisetas", "07.jpg", 1000],
    ["Camiseta 08", "camisetas", "08.jpg", 1000],

    ["Pantalón 01", "pantalones", "01.jpg", 1000],
    ["Pantalón 02", "pantalones", "02.jpg", 1000],
    ["Pantalón 03", "pantalones", "03.jpg", 1000],
    ["Pantalón 04", "pantalones", "04.jpg", 1000],
    ["Pantalón 05", "pantalones", "05.jpg", 1000],
];

$insertados = 0;

foreach ($productos as $p) {
    [$titulo, $categoria, $archivo, $precio] = $p;

    // prefijo código
    $pref = strtoupper(substr($categoria, 0, 3));

    $q = $cn->query("
        SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) AS ult
        FROM productos
        WHERE categoria = '$categoria'
    ");
    $ult = $q->fetch_assoc()["ult"] ?? 0;
    $nuevo = $ult + 1;

    $codigo = "$pref-" . str_pad($nuevo, 3, "0", STR_PAD_LEFT);

    $ruta = "/img/$categoria/$archivo";

    $stmt = $cn->prepare("
        INSERT INTO productos (codigo, titulo, categoria, imagen, precio, stock)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $stmt->bind_param("ssssd", $codigo, $titulo, $categoria, $ruta, $precio);

    if ($stmt->execute()) {
        $insertados++;
    }
}

echo json_encode([
    "success" => true,
    "insertados" => $insertados
]);
