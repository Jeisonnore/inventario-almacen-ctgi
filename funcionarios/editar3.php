<?php 
include "../conexion.php";

// Validar si los datos vienen del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
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
                text: 'ID inválido.'
            }).then(() => { window.location='1registrofuncionario.php'; });
        });
        </script>
        </body></html>";
        exit;
    }

    // Capturar y limpiar datos
    $id = trim($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $cedula = trim($_POST['cedula']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $estado = trim($_POST['estado']);

    // Verificar si el correo ya está registrado en otro instructor
    $sql_verificar = "SELECT id FROM instructores WHERE correo = ? AND id != ?";
    $stmt_verificar = mysqli_prepare($connect, $sql_verificar);
    mysqli_stmt_bind_param($stmt_verificar, "si", $correo, $id);
    mysqli_stmt_execute($stmt_verificar);
    $result = mysqli_stmt_get_result($stmt_verificar);

    if (mysqli_num_rows($result) > 0) {
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
                text: 'El correo ya está registrado para otro instructor.'
            }).then(() => { window.location='editar2.php?id=$id'; });
        });
        </script>
        </body></html>";
        exit;
    }
    mysqli_stmt_close($stmt_verificar);

    // 🔥 Verificar si la cédula ya está registrada en otro instructor
    $sql_verificar_cedula = "SELECT id FROM instructores WHERE cedula = ? AND id != ?";
    $stmt_verificar_cedula = mysqli_prepare($connect, $sql_verificar_cedula);
    mysqli_stmt_bind_param($stmt_verificar_cedula, "si", $cedula, $id);
    mysqli_stmt_execute($stmt_verificar_cedula);
    $result_cedula = mysqli_stmt_get_result($stmt_verificar_cedula);

    if (mysqli_num_rows($result_cedula) > 0) {
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
                text: 'La cédula ya está registrada para otro instructor.'
            }).then(() => { window.location='editar2.php?id=$id'; });
        });
        </script>
        </body></html>";
        exit;
    }
    mysqli_stmt_close($stmt_verificar_cedula);

    // Si no hay duplicados, actualizar
    $sql = "UPDATE instructores SET nombre = ?, apellido = ?, cedula = ?, correo = ?, telefono = ?, estado = ? WHERE id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $nombre, $apellido, $cedula, $correo, $telefono, $estado, $id);

    if (mysqli_stmt_execute($stmt)) {
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
                text: '$nombre $apellido ha sido actualizado'
            }).then(() => { window.location='1registrofuncionario.php'; });
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
                text: 'No se pudo actualizar'
            }).then(() => { window.location='editar2.php?id=$id'; });
        });
        </script>
        </body></html>";
    }

    mysqli_stmt_close($stmt);
}
?>
