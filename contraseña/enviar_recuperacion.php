<?php
// Incluye el archivo de conexión a la base de datos
include '../conexion.php'; // Usa tu archivo original con $connect

// Incluye el archivo que contiene la función para enviar correos
require 'correo.php';

// Verifica si la petición es de tipo POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obtiene y limpia el correo enviado por el usuario
    $correo = trim($_POST['correo']);

    // Prepara la consulta para verificar si el correo existe en la base de datos
    $stmt = $connect->prepare("SELECT id FROM usuario WHERE usuario = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    // Si el correo existe
    if ($stmt->num_rows > 0) {
        // Genera un token aleatorio y una fecha de expiración (1 hora)
        $token = bin2hex(random_bytes(16));
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Actualiza el usuario con el token y la expiración
        $stmt_update = $connect->prepare("UPDATE usuario SET token_recuperacion = ?, token_expiracion = ? WHERE usuario = ?");
        $stmt_update->bind_param("sss", $token, $expira, $correo);
        $stmt_update->execute();

        // Crea el enlace de recuperación de contraseña
        $enlace = "http://localhost/almace/contraseña/restablecer.php?token=$token";

        // Envía el correo con el enlace de recuperación
        if (enviarCorreo($correo, $enlace)) {
            // SweetAlert2 de éxito y redirección
            echo '
            <html><head>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
            </head><body>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "success",
                title: "¡Correo enviado!",
                text: "Se ha enviado un enlace a tu correo.",
                confirmButtonText: "Ir al login",
                allowOutsideClick: false
            }).then(() => {
                window.location.href = "../login.php";
            });
            setTimeout(function(){ window.location.href = "../login.php"; }, 4000);
            </script>
            </body></html>';
        } else {
            // SweetAlert2 de error y redirección
            echo '
            <html><head>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
            </head><body>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo enviar el correo. Inténtalo más tarde.",
                confirmButtonText: "Ir al login",
                allowOutsideClick: false
            }).then(() => {
                window.location.href = "../login.php";
            });
            setTimeout(function(){ window.location.href = "../login.php"; }, 4000);
            </script>
            </body></html>';
        }
    } else {
        // Si el correo no está registrado, SweetAlert2 de error y redirección
        echo '
        <html><head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        </head><body>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        Swal.fire({
            icon: "error",
            title: "Correo no registrado",
            text: "El correo ingresado no está registrado.",
            confirmButtonText: "Ir al login",
            allowOutsideClick: false
        }).then(() => {
            window.location.href = "../login.php";
        });
        setTimeout(function(){ window.location.href = "../login.php"; }, 4000);
        </script>
        </body></html>';
    }
}
?>
