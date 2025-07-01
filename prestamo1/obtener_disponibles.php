<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
include '../conexion.php';

$tipo = $_GET['tipo'] ?? '';
$response = ['data' => []];

if ($tipo === 'equipos') {
    $query = "SELECT id, marca, serie, estado FROM equipos WHERE estado = 'disponible'";
} 
elseif ($tipo === 'materiales') {
    $query = "SELECT id, nombre, tipo, cantidad, serie FROM materiales WHERE estado = 'disponible' AND cantidad > 0";
} 
else {
    echo json_encode($response);
    exit;
}

$result = $connect->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
}

echo json_encode($response);
?>