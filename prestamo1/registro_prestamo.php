<?php
session_start();
include '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prestamos.php?error=' . urlencode('Método no permitido'));
    exit;
}

if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    header('Location: ../login.php');
    exit;
}

$allowed_roles = ['administrador', 'almacenista'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: prestamos.php?error=' . urlencode('Acceso no autorizado'));
    exit;
}

$almacenista_id = intval($_POST['almacenista_id'] ?? 0);
$instructor_id = intval($_POST['instructor_id'] ?? 0);
$fecha_prestamo = $_POST['fecha_prestamo'] ?? null;
$fecha_devolucion = $_POST['fecha_devolucion'] ?? null;
$tipo = isset($_POST['equipos']) ? 'equipos' : (isset($_POST['materiales']) ? 'materiales' : null);

if (!$almacenista_id || !$instructor_id || !$fecha_prestamo || !$tipo) {
    header('Location: prestamos.php?error=' . urlencode('Faltan datos obligatorios'));
    exit;
}

// ... (Validaciones iniciales sin cambios) ...

$connect->begin_transaction();

try {
    if ($tipo === 'equipos') {
        // --- Lógica para Equipos (sin cambios) ---
        if (empty($_POST['equipos'])) {
            throw new Exception("No se seleccionaron equipos");
        }
        if (!$fecha_devolucion) {
            throw new Exception("Fecha de devolución es obligatoria para equipos");
        }
        if (strtotime($fecha_devolucion) <= strtotime($fecha_prestamo)) {
            throw new Exception("La fecha de devolución debe ser posterior a la de préstamo");
        }
        foreach ($_POST['equipos'] as $equipo_id) {
            $equipo_id = intval($equipo_id);

            $stmt = $connect->prepare("SELECT estado FROM equipos WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();
            $equipo = $stmt->get_result()->fetch_assoc();

            if (!$equipo) throw new Exception("Equipo no encontrado");
            if ($equipo['estado'] !== 'disponible') throw new Exception("El equipo $equipo_id no está disponible");
            
            $stmt = $connect->prepare("INSERT INTO prestamos_equipos (equipo_id, instructor_id, almacenista_id, fecha_prestamo, fecha_devolucion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $equipo_id, $instructor_id, $almacenista_id, $fecha_prestamo, $fecha_devolucion);
            if (!$stmt->execute()) throw new Exception("Error al registrar préstamo de equipo");
            
            $stmt = $connect->prepare("UPDATE equipos SET estado = 'prestado' WHERE id = ?");
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();
            
            $stmt = $connect->prepare("INSERT INTO historial_equipos (equipo_id, marca, serie, estado, fecha, movimiento, cambios) SELECT id, marca, serie, 'prestado', NOW(), 'prestamo', 'Préstamo de equipo' FROM equipos WHERE id = ?");
            $stmt->bind_param("i", $equipo_id);
            $stmt->execute();
        }

    } elseif ($tipo === 'materiales') {
        // --- Lógica para Materiales (con la modificación solicitada) ---
        if (empty($_POST['materiales'])) {
            throw new Exception("No se seleccionaron materiales");
        }

        foreach ($_POST['materiales'] as $material_id) {
            $material_id = intval($material_id);
            $cantidad = intval($_POST['cantidad'][$material_id] ?? 0);

            if ($cantidad <= 0) continue;

            $stmt = $connect->prepare("SELECT tipo, cantidad, nombre, serie FROM materiales WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $material_id);
            $stmt->execute();
            $material = $stmt->get_result()->fetch_assoc();

            if (!$material) throw new Exception("Material no encontrado");

            // Validaciones de tipo y cantidad (sin cambios)
            if ($material['tipo'] === 'no consumible') {
                if ($cantidad !== 1) throw new Exception("Para material no consumible la cantidad debe ser 1");
                if (!$fecha_devolucion) throw new Exception("Fecha de devolución es obligatoria para materiales no consumibles");
                $estado_material = $connect->query("SELECT estado FROM materiales WHERE id = $material_id")->fetch_assoc()['estado'];
                if ($estado_material !== 'disponible') throw new Exception("El material no consumible no está disponible");
            } else {
                if ($material['cantidad'] < $cantidad) throw new Exception("Stock insuficiente para el material {$material['nombre']}");
            }

            $fecha_dev = ($material['tipo'] === 'no consumible') ? $fecha_devolucion : NULL;

            $stmt = $connect->prepare("INSERT INTO prestamo_materiales (material_id, instructor_id, almacenista_id, cantidad, fecha_prestamo, fecha_devolucion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiss", $material_id, $instructor_id, $almacenista_id, $cantidad, $fecha_prestamo, $fecha_dev);
            if (!$stmt->execute()) throw new Exception("Error al registrar préstamo de material");
            
            if ($material['tipo'] === 'consumible') {
                $stmt = $connect->prepare("UPDATE materiales SET cantidad = cantidad - ? WHERE id = ?");
                $stmt->bind_param("ii", $cantidad, $material_id);
                $stmt->execute();
            } else {
                $stmt = $connect->prepare("UPDATE materiales SET estado = 'prestado' WHERE id = ?");
                $stmt->bind_param("i", $material_id);
                $stmt->execute();
            }

            // =======================================================
            // INICIO DE LA MODIFICACIÓN SOLICITADA
            // =======================================================
            $stmt = $connect->prepare("INSERT INTO historial_materiales 
                (material_id, nombre, tipo, cantidad, serie, fecha, movimiento, cambios) 
                VALUES (?, ?, ?, ?, ?, NOW(), 'salida', ?)");
            
            // Asignación condicional del valor para la columna 'cambios'
            $cambios = ($material['tipo'] === 'no consumible') ? "Préstamo de material" : null;
            
            $stmt->bind_param("ississ", $material_id, $material['nombre'], $material['tipo'], $cantidad, $material['serie'], $cambios);
            $stmt->execute();
            // =======================================================
            // FIN DE LA MODIFICACIÓN
            // =======================================================
        }
    }

    $connect->commit();
    header('Location: prestamos.php?success=' . urlencode('Préstamo registrado correctamente'));
    exit;

} catch (Exception $e) {
    $connect->rollback();
    header('Location: prestamos.php?error=' . urlencode($e->getMessage()));
exit;
}
?>