<?php 
include "../conexion.php";

echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

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
    $marca = trim($_POST['marca']);
    $serie = trim($_POST['serie']);
    $estado = strtolower(trim($_POST['estado'])); // importante: strtolower

    // Validar que el estado esté dentro de los permitidos
    $estados_permitidos = ['disponible', 'deteriorado', 'prestado'];
    if (!in_array($estado, $estados_permitidos)) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Estado inválido.'
            }).then(() => { window.location='editar_equipos.php?id=$id'; });
        </script>";
        exit;
    }

    // Verificar que la serie no esté repetida
    $sql_verificar_serie = "SELECT id FROM equipos WHERE serie = ? AND id != ?";
    $stmt_verificar_serie = mysqli_prepare($connect, $sql_verificar_serie);

    if ($stmt_verificar_serie) {
        mysqli_stmt_bind_param($stmt_verificar_serie, "si", $serie, $id);
        mysqli_stmt_execute($stmt_verificar_serie);
        mysqli_stmt_store_result($stmt_verificar_serie);

        if (mysqli_stmt_num_rows($stmt_verificar_serie) > 0) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'La serie ya está registrada para otro equipo.'
                }).then(() => { window.location='editar_equipos.php?id=$id'; });
            </script>";
            exit;
        }
        mysqli_stmt_close($stmt_verificar_serie);
    }

    // =============================================//
    // SECCIÓN DE HISTORIAL DE EDICIONES/
    // =============================================//
    
    // 1. Obtener datos anteriores del equipo
    $sql_antes = "SELECT marca, serie, estado FROM equipos WHERE id = ?";
    $stmt_antes = mysqli_prepare($connect, $sql_antes);
    mysqli_stmt_bind_param($stmt_antes, "i", $id);
    mysqli_stmt_execute($stmt_antes);
    $result_antes = mysqli_stmt_get_result($stmt_antes);
    $antes = mysqli_fetch_assoc($result_antes);
    mysqli_stmt_close($stmt_antes);

    // 2. Preparar datos nuevos (del formulario)
    $despues = [
        'marca' => $marca,
        'serie' => $serie,
        'estado' => $estado
    ];

    // 3. Comparar cambios
    $cambios = [];
    foreach ($antes as $campo => $valor_antes) {
        if ($antes[$campo] != $despues[$campo]) {
            $cambios[] = "$campo: '{$antes[$campo]}' → '{$despues[$campo]}'";
        }
    }

    // 4. Registrar en historial solo si hubo cambios reales
    if (!empty($cambios)) {
        $sql_historial = "INSERT INTO historial_equipos 
                         (equipo_id, marca, serie, estado, fecha, movimiento, cambios) 
                         VALUES (?, ?, ?, ?, NOW(), 'edicion', ?)";
        $stmt_historial = mysqli_prepare($connect, $sql_historial);
        $detalle_cambios = implode("\n", $cambios);
        mysqli_stmt_bind_param($stmt_historial, "issss", 
                             $id, $despues['marca'], $despues['serie'], 
                             $despues['estado'], $detalle_cambios);
        mysqli_stmt_execute($stmt_historial);
        mysqli_stmt_close($stmt_historial);
    }
    
    // =============================================
    // FIN SECCIÓN DE HISTORIAL
    // =============================================

    // Actualizar los datos del equipo
    $sql = "UPDATE equipos SET marca = ?, serie = ?, estado = ? WHERE id = ?";
    $stmt = mysqli_prepare($connect, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssi", $marca, $serie, $estado, $id);

        if (mysqli_stmt_execute($stmt)) {
            // Mostrar la notificación en una página HTML completa para asegurar que el DOM esté listo
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
                    text: 'Equipo actualizado correctamente.'
                }).then(() => { window.location='2insumos.php'; });
            });
            </script>
            </body></html>";
        } else {
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
                    text: 'No se pudo actualizar el equipo.'
                }).then(() => { window.location='editar_equipos.php?id=$id'; });
            });
            </script>
            </body></html>";
        }
        mysqli_stmt_close($stmt);
    } else {
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
                text: 'Error al preparar la consulta de actualización.'
            }).then(() => { window.location='editar_equipos.php?id=$id'; });
        });
        </script>
        </body></html>";
    }
}
?>