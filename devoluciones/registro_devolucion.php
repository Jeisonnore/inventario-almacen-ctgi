<?php 
// Inicia la sesión y conecta con la base de datos
session_start();
include '../conexion.php';

// Verifica que la petición sea POST y que el usuario esté autenticado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
    header("Location: 4devolucion.php?error=Acceso no autorizado");
    exit;
}

// Valida que los datos obligatorios estén presentes
if (empty($_POST['almacenista']) || empty($_POST['instructor']) || empty($_POST['fecha_devolucion'])) {
    header("Location: 4devolucion.php?error=Faltan datos obligatorios");
    exit;
}

$almacenista_id = (int)$_POST['almacenista'];
$instructor_id = (int)$_POST['instructor'];
$fecha_devolucion = $connect->real_escape_string($_POST['fecha_devolucion']);

$connect->autocommit(false);
$error = false;

try {
    // Procesar devolución de equipos seleccionados
    if (!empty($_POST['equipos_seleccionados'])) {
        foreach ($_POST['equipos_seleccionados'] as $id_prestamo_equipo) {
            $id_prestamo_equipo = (int)$id_prestamo_equipo;
            $condicion = $connect->real_escape_string($_POST["condicion_equipo_{$id_prestamo_equipo}"]);
            $observacion = $connect->real_escape_string($_POST["observacion_equipo_{$id_prestamo_equipo}"] ?? '');

            // Consulta para obtener datos del equipo
            $res_equipo = $connect->query("SELECT e.id, e.marca, e.serie 
                FROM equipos e
                JOIN prestamos_equipos pe ON e.id = pe.equipo_id
                WHERE pe.id = $id_prestamo_equipo");
            
            if (!$res_equipo || $res_equipo->num_rows === 0) {
                throw new Exception("Equipo no encontrado");
            }
            
            $equipo = $res_equipo->fetch_assoc();
            $equipo_id = $equipo['id'];

            // Inserta la devolución del equipo
            $stmt = $connect->prepare("INSERT INTO devolucion_equipos 
                (estado_devolucion, fecha_devolucion, observaciones, prestamo_equipo_id) 
                VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $condicion, $fecha_devolucion, $observacion, $id_prestamo_equipo);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar devolución de equipo: " . $stmt->error);
            }

            // Inserta en el historial de equipos
            $historial_equipo = $connect->prepare("INSERT INTO historial_equipos 
                (equipo_id, marca, serie, estado, fecha, movimiento, cambios) 
                VALUES (?, ?, ?, ?, NOW(), 'devolucion', ?)");
            $cambios = "Devolución de equipo. Condición: $condicion";
            $historial_equipo->bind_param("issss", $equipo_id, $equipo['marca'], $equipo['serie'], $condicion, $cambios);
            $historial_equipo->execute();

            // Actualiza la fecha de devolución en el préstamo
            $update_prestamo = $connect->query("UPDATE prestamos_equipos 
                SET fecha_devolucion = '$fecha_devolucion' 
                WHERE id = $id_prestamo_equipo");

            if (!$update_prestamo) {
                throw new Exception("Error al actualizar préstamo de equipo");
            }
        }
    }
    // Procesar devolución de materiales seleccionados (solo no consumibles)
    if (!empty($_POST['materiales_seleccionados'])) {
        foreach ($_POST['materiales_seleccionados'] as $id_prestamo_material) {
            $id_prestamo_material = (int)$id_prestamo_material;
            $cantidad_devuelta = (int)$_POST["cantidad_{$id_prestamo_material}"];
            $condicion = $connect->real_escape_string($_POST["condicion_material_{$id_prestamo_material}"]);

            // Consulta para obtener datos del material y préstamo
            $res_material = $connect->query("SELECT m.id, m.nombre, m.tipo, m.serie, pm.cantidad 
                FROM materiales m
                JOIN prestamo_materiales pm ON m.id = pm.material_id
                WHERE pm.id = $id_prestamo_material");

            if (!$res_material || $res_material->num_rows === 0) {
                throw new Exception("Préstamo de material no encontrado");
            }

            $row_material = $res_material->fetch_assoc();
            $id_material = (int)$row_material['id'];
            $cantidad_prestada = (int)$row_material['cantidad'];

            // Valida que la cantidad devuelta no sea mayor a la prestada
            if ($cantidad_devuelta > $cantidad_prestada) {
                throw new Exception("Cantidad a devolver mayor que la prestada");
            }

            $observacion = $connect->real_escape_string($_POST["observacion_material_{$id_prestamo_material}"] ?? '');

            // Inserta la devolución del material
            $stmt = $connect->prepare("INSERT INTO devolucion_materiales 
                (fecha_devolucion, observaciones, cantidad, condicion_entrega, prestamo_material_id, almacenista_id) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissi", $fecha_devolucion, $observacion, $cantidad_devuelta, $condicion, $id_prestamo_material, $almacenista_id);

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar devolución de material");
            }

            // Valida que no exista ya un registro de historial para evitar duplicados
            $verifica_historial = $connect->prepare("SELECT id FROM historial_materiales 
                WHERE material_id = ? AND movimiento = 'entrada' AND fecha >= NOW() - INTERVAL 1 MINUTE");
            $verifica_historial->bind_param("i", $id_material);
            $verifica_historial->execute();
            $res_verifica = $verifica_historial->get_result();

            // Si no existe, inserta en el historial de materiales
            if ($res_verifica->num_rows === 0) {
                $cambios = "Devolución de material. Cantidad: $cantidad_devuelta, Condición: $condicion. Observación: $observacion";
                $historial_material = $connect->prepare("INSERT INTO historial_materiales 
                    (material_id, nombre, tipo, cantidad, serie, fecha, movimiento, cambios) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'entrada', ?)");
                $historial_material->bind_param("ississ", $id_material, $row_material['nombre'], 
                    $row_material['tipo'], $cantidad_devuelta, $row_material['serie'], $cambios);

                $historial_material->execute();
            }

            // Si es no consumible, actualiza el estado del material
            if ($row_material['tipo'] === 'no consumible') {
                $estado_actualizado = ($condicion === 'bueno') ? 'disponible' : 'deteriorado';
                $update_material = $connect->query("UPDATE materiales 
                    SET estado = '$estado_actualizado' 
                    WHERE id = $id_material AND tipo = 'no consumible'");

                if (!$update_material) {
                    throw new Exception("Error al actualizar estado de material");
                }
            }

            // Actualiza la cantidad pendiente en el préstamo
            $nueva_cantidad = $cantidad_prestada - $cantidad_devuelta;
            
            if ($nueva_cantidad > 0) {
                $update_prestamo = $connect->query("UPDATE prestamo_materiales 
                    SET cantidad = $nueva_cantidad 
                    WHERE id = $id_prestamo_material");
            } else {
                $update_prestamo = $connect->query("UPDATE prestamo_materiales 
                    SET cantidad = 0, fecha_devolucion = '$fecha_devolucion'
                    WHERE id = $id_prestamo_material");
            }
        }
    }

    // Confirma la transacción si todo fue exitoso
    $connect->commit();
    $swal_type = 'success';
    $swal_title = '¡Éxito!';
    $swal_text = 'Devolución registrada exitosamente.';
} catch (Exception $e) {
    // Revierte la transacción en caso de error
    $connect->rollback();
    $swal_type = 'error';
    $swal_title = 'Error';
    $swal_text = 'Error al registrar devolución: ' . $e->getMessage();
}

// Mostrar SweetAlert2 y redirigir
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado de Devolución</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Muestra el mensaje de éxito o error y redirige
    Swal.fire({
        icon: '<?= $swal_type ?>',
        title: '<?= $swal_title ?>',
        text: '<?= $swal_text ?>',
        confirmButtonText: 'Aceptar',
        allowOutsideClick: false
    }).then(() => {
        window.location.href = '4devolucion.php';
    });
    setTimeout(function() {
        window.location.href = '4devolucion.php';
    }, 4000);
</script>
</body>
</html>
