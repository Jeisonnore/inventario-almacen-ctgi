<?php
// Inicia la sesión y conecta con la base de datos
session_start();
include '../conexion.php';

// Verifica que el usuario esté autenticado
if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

// Define el tipo de respuesta como JSON
header('Content-Type: application/json');

// Verifica que los parámetros requeridos estén presentes
if (!isset($_GET['instructor_id']) || !isset($_GET['tipo'])) {
    echo json_encode(['error' => 'Parámetros incompletos']);
    exit;
}

$instructor_id = (int)$_GET['instructor_id'];
$tipo = $_GET['tipo'];
$prestamos = [];

try {
    // Consulta para préstamos de equipos no devueltos
    if ($tipo == 'equipos') {
        $query = "SELECT 
                    e.id AS equipo_id,
                    e.marca, 
                    e.serie,
                    e.estado,
                    pe.id AS id,
                    pe.fecha_prestamo
                  FROM prestamos_equipos pe
                  JOIN equipos e ON pe.equipo_id = e.id
                  WHERE pe.instructor_id = ?
                  AND e.estado = 'prestado'
                  AND NOT EXISTS (
                      SELECT 1 FROM devolucion_equipos de
                      WHERE de.prestamo_equipo_id = pe.id
                  )";
    // Consulta para préstamos de materiales no consumibles no devueltos
    } elseif ($tipo == 'materiales') {
        $query = "SELECT 
                    m.id AS material_id,
                    m.nombre, 
                    m.tipo,
                    pm.id AS id,
                    pm.cantidad AS cantidad_prestada,
                    pm.fecha_prestamo
                  FROM prestamo_materiales pm
                  JOIN materiales m ON pm.material_id = m.id
                  WHERE pm.instructor_id = ?
                  AND m.tipo = 'no consumible'  -- Solo materiales no consumibles
                  AND NOT EXISTS (
                      SELECT 1 FROM devolucion_materiales dm
                      WHERE dm.prestamo_material_id = pm.id
                  )";
    } else {
        echo json_encode(['error' => 'Tipo no válido']);
        exit;
    }

    // Ejecuta la consulta preparada
    $stmt = $connect->prepare($query);
    $stmt->bind_param('i', $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Recorre los resultados y los agrega al array
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }

    // Devuelve los préstamos encontrados en formato JSON
    echo json_encode($prestamos);
} catch (Exception $e) {
    // Devuelve el error en caso de excepción
    echo json_encode(['error' => $e->getMessage()]);
}
?>