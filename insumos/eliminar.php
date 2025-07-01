<?php
include "../conexion.php";

if (
    isset($_GET['id'], $_GET['tipo']) &&
    ctype_digit($_GET['id']) &&
    in_array($_GET['tipo'], ['equipo', 'material'])
) {
    $id = (int)$_GET['id'];
    $tipo = $_GET['tipo'];
    $url_redirigir = '2insumos.php';

    // Iniciar transacción para asegurar integridad
    mysqli_begin_transaction($connect);

    try {
        if ($tipo === 'equipo') {
            // Configuración para equipos
            $sql_datos = "SELECT marca, serie, estado FROM equipos WHERE id = ?";
            $tabla = 'equipos';
            $columna_id = 'id';
            $mensaje_exito = 'Equipo eliminado correctamente.';
            $mensaje_error = 'No se pudo eliminar el equipo.';
        } else { // material
            // Configuración para materiales
            $sql_datos = "SELECT nombre, tipo, cantidad, serie, estado FROM materiales WHERE id = ?";
            $tabla = 'materiales';
            $columna_id = 'id';
            $mensaje_exito = 'Material eliminado correctamente.';
            $mensaje_error = 'No se pudo eliminar el material.';
        }

        // Obtener datos del item
        $stmt = mysqli_prepare($connect, $sql_datos);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $item_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$item_data) {
            throw new Exception("No se encontró el $tipo.");
        }

        // Verificar estado
        $estado_actual = isset($item_data['estado']) ? $item_data['estado'] : 'disponible';
        if ($estado_actual !== 'disponible') {
            throw new Exception('Solo se puede eliminar si el estado es "disponible".');
        }

        // Manejo específico por tipo
        if ($tipo === 'equipo') {
            // Registrar en historial de equipos
            $sql_historial = "INSERT INTO historial_equipos 
                             (equipo_id, marca, serie, estado, fecha, movimiento, cambios) 
                             VALUES (?, ?, ?, ?, NOW(), 'eliminado', 'Eliminación manual')";
            $stmt = mysqli_prepare($connect, $sql_historial);
            mysqli_stmt_bind_param(
                $stmt,
                "isss",
                $id,
                $item_data['marca'],
                $item_data['serie'],
                $item_data['estado']
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("No se pudo registrar en historial: " . mysqli_error($connect));
            }
            mysqli_stmt_close($stmt);

            // Eliminar registros relacionados
            $relaciones = [
                "DELETE de FROM devolucion_equipos de JOIN prestamos_equipos pe ON de.prestamo_equipo_id = pe.id WHERE pe.equipo_id = ?",
                "DELETE FROM prestamos_equipos WHERE equipo_id = ?",
                "DELETE FROM historial_equipos WHERE equipo_id = ?"
            ];
        } else { // material
            // Verificar tipo de material
            if ($item_data['tipo'] === 'no consumible' && $item_data['cantidad'] != 1) {
                throw new Exception('No se puede eliminar material no consumible con cantidad diferente a 1.');
            }

            // Registrar en historial de materiales
            $sql_historial = "INSERT INTO historial_materiales 
                            (material_id, nombre, tipo, cantidad, serie, fecha, movimiento) 
                            VALUES (?, ?, ?, ?, ?, NOW(), 'eliminado')";
            $stmt = mysqli_prepare($connect, $sql_historial);
            mysqli_stmt_bind_param(
                $stmt,
                "issis",
                $id,
                $item_data['nombre'],
                $item_data['tipo'],
                $item_data['cantidad'],
                $item_data['serie']
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("No se pudo registrar en historial: " . mysqli_error($connect));
            }
            mysqli_stmt_close($stmt);

            // Eliminar registros relacionados
            $relaciones = [
                "DELETE dm FROM devolucion_materiales dm JOIN prestamo_materiales pm ON dm.prestamo_material_id = pm.id WHERE pm.material_id = ?",
                "DELETE FROM prestamo_materiales WHERE material_id = ?"
            ];

            // Para materiales no consumibles, verificar préstamos activos
            if ($item_data['tipo'] === 'no consumible') {
                $sql_verificar = "SELECT COUNT(*) as total FROM prestamo_materiales 
                                WHERE material_id = ? AND fecha_devolucion IS NULL";
                $stmt = mysqli_prepare($connect, $sql_verificar);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $data = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                
                if ($data['total'] > 0) {
                    throw new Exception("No se puede eliminar: existen préstamos activos de este material.");
                }
            }
        }

        // Eliminar registros relacionados
        foreach ($relaciones as $sql) {
            $stmt = mysqli_prepare($connect, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error al eliminar registros relacionados: " . mysqli_error($connect));
            }
            mysqli_stmt_close($stmt);
        }

        // Finalmente, eliminar el item principal
        $sql = "DELETE FROM $tabla WHERE $columna_id = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error al eliminar: " . mysqli_error($connect));
        }
        mysqli_stmt_close($stmt);

        // Confirmar transacción
        mysqli_commit($connect);
        mostrarExito($mensaje_exito, $url_redirigir);
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($connect);
        mostrarError($e->getMessage(), $url_redirigir);
    }
} else {
    mostrarError('ID o tipo inválido.', '2insumos.php');
}

// Funciones auxiliares para mostrar mensajes
function mostrarExito($mensaje, $url) {
    echo "<!DOCTYPE html>
    <html><head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head><body>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '$mensaje'
        }).then(() => { window.location='$url'; });
    });
    </script>
    </body></html>";
}

function mostrarError($mensaje, $url) {
    echo "<!DOCTYPE html>
    <html><head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head><body>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '$mensaje'
        }).then(() => { window.location='$url'; });
    });
    </script>
    </body></html>";
}
?>