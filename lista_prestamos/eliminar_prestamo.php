<?php
session_start();
include '../conexion.php';
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php

// Validar sesión y rol
if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Sesión inválida',
            text: 'Debes iniciar sesión para continuar',
            confirmButtonText: 'Entendido'
        }).then(() => {
            window.location.href = '../login.php';
        });
    </script>";
    exit;
}

if ($_SESSION['rol'] !== 'administrador') {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: 'Solo administradores pueden eliminar préstamos',
            confirmButtonText: 'Entendido'
        }).then(() => {
            window.location.href = 'listar_prestamos.php';
        });
    </script>";
    exit;
}

// Obtener ID del préstamo
$prestamo_id = $_GET['id'] ?? 0;
$tipo = $_GET['tipo'] ?? '';

if (!$prestamo_id || !in_array($tipo, ['equipo', 'material'])) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Datos inválidos',
            text: 'Los datos proporcionados no son válidos',
            confirmButtonText: 'Entendido'
        }).then(() => {
            window.location.href = 'listar_prestamos.php';
        });
    </script>";
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $connect->begin_transaction();

    if ($tipo === 'equipo') {
        // Procesamiento para EQUIPOS (MANTENIDO EXACTAMENTE IGUAL)
        $prestamo_stmt = $connect->prepare("SELECT * FROM prestamos_equipos WHERE id = ?");
        $prestamo_stmt->bind_param("i", $prestamo_id);
        $prestamo_stmt->execute();
        $prestamo = $prestamo_stmt->get_result()->fetch_assoc();
        
        if (!$prestamo) {
            throw new Exception("Préstamo de equipo no encontrado");
        }
        
        // Verificar si ya fue devuelto (SOLO CORRECCIÓN MENOR EN ESTA LÍNEA)
        $devolucion_stmt = $connect->prepare("SELECT 1 FROM devolucion_equipos WHERE prestamo_equipo_id = ? LIMIT 1");
        $devolucion_stmt->bind_param("i", $prestamo_id);
        $devolucion_stmt->execute();
        
        if ($devolucion_stmt->get_result()->num_rows > 0) {
            $connect->rollback();
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'No se puede eliminar',
                    html: '<b>El equipo ya fue devuelto</b><br>No puedes eliminar préstamos que ya han sido devueltos.',
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    window.location.href = 'listar_prestamos.php';
                });
            </script>";
            exit;
        }
        
        // Actualizar estado del equipo a disponible (MANTENIDO IGUAL)
        $updateEquipo = $connect->prepare("UPDATE equipos SET estado = 'disponible' WHERE id = ?");
        $updateEquipo->bind_param("i", $prestamo['equipo_id']);
        $updateEquipo->execute();
        
        // Registrar en historial (MANTENIDO IGUAL)
        $historial = $connect->prepare("INSERT INTO historial_equipos 
                                      (equipo_id, estado, fecha, movimiento, cambios) 
                                      VALUES (?, 'disponible', NOW(), 'eliminado', ?)");
        $descripcion = "Préstamo ID {$prestamo_id} eliminado, equipo marcado como disponible";
        $historial->bind_param("is", $prestamo['equipo_id'], $descripcion);
        $historial->execute();
        
        // Eliminar el préstamo (MANTENIDO IGUAL)
        $deletePrestamo = $connect->prepare("DELETE FROM prestamos_equipos WHERE id = ?");
        $deletePrestamo->bind_param("i", $prestamo_id);
        $deletePrestamo->execute();

    } else {
        // Procesamiento para MATERIALES (MANTENIDO EXACTAMENTE IGUAL)
        $prestamo_stmt = $connect->prepare("SELECT * FROM prestamo_materiales WHERE id = ?");
        $prestamo_stmt->bind_param("i", $prestamo_id);
        $prestamo_stmt->execute();
        $prestamo = $prestamo_stmt->get_result()->fetch_assoc();
        
        if (!$prestamo) {
            throw new Exception("Préstamo de material no encontrado");
        }
        
        // Verificar si ya fue devuelto (MANTENIDO IGUAL)
        $devolucion_stmt = $connect->prepare("SELECT 1 FROM devolucion_materiales WHERE prestamo_material_id = ?");
        $devolucion_stmt->bind_param("i", $prestamo_id);
        $devolucion_stmt->execute();
        
        if ($devolucion_stmt->get_result()->num_rows > 0) {
            $connect->rollback();
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'No se puede eliminar',
                    html: '<b>El material ya fue devuelto</b><br>No puedes eliminar préstamos que ya han sido devueltos.',
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    window.location.href = 'listar_prestamos.php';
                });
            </script>";
            exit;
        }
        
        // Obtener información del material (MANTENIDO IGUAL)
        $material_stmt = $connect->prepare("SELECT tipo FROM materiales WHERE id = ?");
        $material_stmt->bind_param("i", $prestamo['material_id']);
        $material_stmt->execute();
        $material = $material_stmt->get_result()->fetch_assoc();
        
        if ($material['tipo'] === 'consumible') {
            // Para materiales consumibles (MANTENIDO IGUAL)
            $updateMaterial = $connect->prepare("UPDATE materiales SET cantidad = cantidad + ? WHERE id = ?");
            $updateMaterial->bind_param("ii", $prestamo['cantidad'], $prestamo['material_id']);
            $updateMaterial->execute();
        } else {
            // Para no consumibles (MANTENIDO IGUAL)
            $updateMaterial = $connect->prepare("UPDATE materiales SET estado = 'disponible' WHERE id = ?");
            $updateMaterial->bind_param("i", $prestamo['material_id']);
            $updateMaterial->execute();
        }
        
        // Registrar en historial (MANTENIDO IGUAL)
        $historial = $connect->prepare("INSERT INTO historial_materiales 
                                      (material_id, cantidad, fecha, movimiento, cambios) 
                                      VALUES (?, ?, NOW(), 'eliminado', ?)");
        $descripcion = "Préstamo ID {$prestamo_id} eliminado, material devuelto al stock";
        $historial->bind_param("iis", $prestamo['material_id'], $prestamo['cantidad'], $descripcion);
        $historial->execute();
        
        // Eliminar el préstamo (MANTENIDO IGUAL)
        $deletePrestamo = $connect->prepare("DELETE FROM prestamo_materiales WHERE id = ?");
        $deletePrestamo->bind_param("i", $prestamo_id);
        $deletePrestamo->execute();
    }
    
    $connect->commit();
    
    // Redireccionar con éxito (MANTENIDO IGUAL)
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: 'Préstamo eliminado correctamente',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            window.location.href = 'listar_prestamos.php?tipo=" . ($tipo === 'equipo' ? 'prestamos_equipos' : 'prestamos_materiales') . "';
        });
    </script>";
    exit;
    
} catch (Exception $e) {
    $connect->rollback();
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '".addslashes($e->getMessage())."',
            confirmButtonText: 'Entendido'
        }).then(() => {
            window.location.href = 'listar_prestamos.php';
        });
    </script>";
    exit;
}
?>