<?php 
include "../conexion.php";

echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

// Habilitar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validación básica del ID
    if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'ID inválido.'
            }).then(() => { window.location='2insumos.php'; });
        </script>";
        exit;
    }

    $id = intval($_POST['id']);
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    $serie = isset($_POST['serie']) ? trim($_POST['serie']) : '';

    // Validaciones
    $tipos_permitidos = ['consumible', 'no consumible'];
    if (!in_array(strtolower($tipo), $tipos_permitidos)) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Tipo inválido.'
            }).then(() => { window.location='editar_materiales.php?id=$id'; });
        </script>";
        exit;
    }

    if ($cantidad <= 0) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La cantidad debe ser mayor a cero.'
            }).then(() => { window.location='editar_materiales.php?id=$id'; });
        </script>";
        exit;
    }

    // Obtener datos actuales ANTES de la actualización
    $sql_actual = "SELECT nombre, tipo, cantidad, serie FROM materiales WHERE id = ?";
    $stmt_actual = mysqli_prepare($connect, $sql_actual);
    mysqli_stmt_bind_param($stmt_actual, "i", $id);
    mysqli_stmt_execute($stmt_actual);
    $result_actual = mysqli_stmt_get_result($stmt_actual);
    $datos_actuales = mysqli_fetch_assoc($result_actual);
    mysqli_stmt_close($stmt_actual);

    if (!$datos_actuales) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Material no encontrado.'
            }).then(() => { window.location='2insumos.php'; });
        </script>";
        exit;
    }

    // Iniciar transacción
    mysqli_begin_transaction($connect);

    try {
        // =============================================
        // SECCIÓN DE HISTORIAL DE EDICIONES (NUEVA)
        // =============================================
        
        // 1. Preparar datos nuevos (del formulario)
        $datos_nuevos = [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'serie' => $serie
        ];

        // 2. Comparar cambios para registro detallado
        $cambios = [];
        foreach ($datos_actuales as $campo => $valor_actual) {
            if ($datos_actuales[$campo] != $datos_nuevos[$campo]) {
                $cambios[] = "$campo: '{$datos_actuales[$campo]}' → '{$datos_nuevos[$campo]}'";
            }
        }

        // 3. Registrar en historial solo si hubo cambios reales
        if (!empty($cambios)) {
            $sql_historial = "INSERT INTO historial_materiales 
                            (material_id, nombre, tipo, cantidad, serie, fecha, movimiento, cambios) 
                            VALUES (?, ?, ?, ?, ?, NOW(), 'edicion', ?)";
            $stmt_historial = mysqli_prepare($connect, $sql_historial);
            $detalle_cambios = implode("\n", $cambios);
            mysqli_stmt_bind_param($stmt_historial, "ississ", 
                                 $id, $datos_nuevos['nombre'], $datos_nuevos['tipo'],
                                 $datos_nuevos['cantidad'], $datos_nuevos['serie'], $detalle_cambios);
            
            if (!mysqli_stmt_execute($stmt_historial)) {
                throw new Exception("Error al registrar en historial: " . mysqli_error($connect));
            }
            mysqli_stmt_close($stmt_historial);
        }
        
        // =============================================
        // FIN SECCIÓN DE HISTORIAL DE EDICIONES
        // =============================================

        // 1. Actualizar la tabla materiales (tu código original)
        $sql_update = "UPDATE materiales SET nombre = ?, tipo = ?, cantidad = ?, serie = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($connect, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ssisi", $nombre, $tipo, $cantidad, $serie, $id);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Error al actualizar el material: " . mysqli_error($connect));
        }
        mysqli_stmt_close($stmt_update);

        // 2. Lógica adicional para cambios de cantidad (tu código original)
        $hubo_cambios = false;
        $registrar_historial = false;
        $movimiento = 'ingreso';
        $cantidad_historico = $cantidad;

        if ($cantidad != $datos_actuales['cantidad']) {
            $diferencia = $cantidad - $datos_actuales['cantidad'];
            $movimiento = ($diferencia > 0) ? 'ingreso' : 'salida';
            $cantidad_historico = abs($diferencia);
            $registrar_historial = true;
        }
        
        if ($nombre != $datos_actuales['nombre'] || $tipo != $datos_actuales['tipo'] || $serie != $datos_actuales['serie']) {
            if (!$registrar_historial) {
                $movimiento = 'ingreso';
                $cantidad_historico = 0;
                $registrar_historial = true;
            }
            $hubo_cambios = true;
        }

        // Confirmar transacción
        mysqli_commit($connect);
        echo "<!DOCTYPE html>
        <html><head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Material actualizado correctamente.'
            }).then(() => { window.location='2insumos.php'; });
        });
        </script>
        </body></html>";
        exit;
        
    } catch (Exception $e) {
        // Revertir en caso de error
        mysqli_rollback($connect);
        echo "<!DOCTYPE html>
        <html><head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '".addslashes($e->getMessage())."'
            }).then(() => { window.location='editar_materiales.php?id=$id'; });
        });
        </script>
        </body></html>";
        exit;
    }
} else {
    // Si no es POST, redireccionar
    header("Location: 2insumos.php");
    exit;
}
?>