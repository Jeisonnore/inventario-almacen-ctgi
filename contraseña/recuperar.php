<!-- recuperar.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
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
                <h3>Recuperar Contraseña</h3>
                <p>Ingresa tu correo electrónico para restablecer tu contraseña</p>
            </div>
            <!-- Formulario para solicitar recuperación de contraseña -->
            <form class="login-form" action="enviar_recuperacion.php" method="POST">
                <div class="form-group">
                    <input type="email" name="correo" class="form-control" placeholder="Correo electrónico" required>
                </div>
                <button type="submit" class="btn-login">Enviar Enlace</button>
            </form>
        </div>
    </div>
</body>
</html>
