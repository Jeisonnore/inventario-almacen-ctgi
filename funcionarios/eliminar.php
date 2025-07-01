<?php 
include "../conexion.php";

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = $_GET['id'];

    // Verificar si el instructor tiene préstamos de EQUIPOS
    $sql_verificar = "SELECT COUNT(*) as total FROM prestamos_equipos WHERE instructor_id = ?";
    $stmt_verificar = mysqli_prepare($connect, $sql_verificar);
    mysqli_stmt_bind_param($stmt_verificar, "i", $id);
    mysqli_stmt_execute($stmt_verificar);
    $result = mysqli_stmt_get_result($stmt_verificar);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_verificar);

    if ($data['total'] > 0) {
        echo "<!DOCTYPE html>
        <html><head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'No permitido',
                text: 'No se puede eliminar el instructor porque tiene préstamos de equipos registrados.'
            }).then(() => { window.location='1registrofuncionario.php'; });
        });
        </script>
        </body></html>";
        exit;
    }

    // Verificar si el instructor tiene préstamos de MATERIALES
    $sql_verificar_materiales = "SELECT COUNT(*) as total FROM prestamo_materiales WHERE instructor_id = ?";
    $stmt_verificar_materiales = mysqli_prepare($connect, $sql_verificar_materiales);
    mysqli_stmt_bind_param($stmt_verificar_materiales, "i", $id);
    mysqli_stmt_execute($stmt_verificar_materiales);
    $result_materiales = mysqli_stmt_get_result($stmt_verificar_materiales);
    $data_materiales = mysqli_fetch_assoc($result_materiales);
    mysqli_stmt_close($stmt_verificar_materiales);

    if ($data_materiales['total'] > 0) {
        echo "<!DOCTYPE html>
        <html><head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'No permitido',
                text: 'No se puede eliminar el instructor porque tiene préstamos de materiales registrados.'
            }).then(() => { window.location='1registrofuncionario.php'; });
        });
        </script>
        </body></html>";
        exit;
    }

    // Si no tiene ningún préstamo, se elimina
    $sql = "DELETE FROM instructores WHERE id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);

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
                title: 'Eliminado',
                text: 'Instructor eliminado correctamente.'
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
                text: 'No se pudo eliminar el instructor.'
            }).then(() => { window.location='1registrofuncionario.php'; });
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
            title: 'ID inválido',
            text: 'ID inválido.'
        }).then(() => { window.location='1registrofuncionario.php'; });
    });
    </script>
    </body></html>";
}
?>
