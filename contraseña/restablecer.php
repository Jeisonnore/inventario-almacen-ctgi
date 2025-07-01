<?php
// Incluye la conexión a la base de datos
include '../conexion.php'; // $connect ya está definido aquí

// Verifica que el token esté presente en la URL
if (!isset($_GET['token'])) {
    echo "Token no válido.";
    exit;
}

$token = $_GET['token'];

// Consulta para verificar que el token es válido y no ha expirado
$stmt = $connect->prepare("SELECT usuario FROM usuario WHERE token_recuperacion = ? AND token_expiracion > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

// Si el token es válido, muestra el formulario para restablecer la contraseña
if ($stmt->num_rows > 0) {
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <!-- Bootstrap CSS para estilos rápidos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../css/stylos.css">
</head>
<body>
    <!-- Fondo difuminado decorativo -->
    <div class="blur-background"></div>
    <div class="container login-container">
        <div class="login-box">
            <div class="logo-section">
                <!-- Logo institucional -->
                <img src="https://virtual.fundetec.edu.co/wp-content/uploads/2024/09/las-mejores-carreras-tecnicas-en-el-sena.png" alt="Logo" class="animated-logo">
                <h3>Restablecer Contraseña</h3>
                <p>Ingresa una nueva contraseña</p>
            </div>
            <!-- Formulario para ingresar la nueva contraseña -->
            <form class="login-form" method="POST" action="cambiar_contra.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group">
                    <input type="password" name="nueva_contra" class="form-control" placeholder="Nueva contraseña" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirmar_contra" class="form-control" placeholder="Confirmar contraseña" required>
                </div>
                <button type="submit" class="btn-login">Cambiar Contraseña</button>
            </form>
        </div>
    </div>
</body>
</html>
    <?php
// Si el token no es válido o ha expirado, muestra un mensaje de error
} else {
    echo "El enlace ha expirado o no es válido.";
}
?>
