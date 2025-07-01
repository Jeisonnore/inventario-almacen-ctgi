

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Transparente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/stylos.css">
</head>
<body>
    <div class="blur-background"></div>
    <div class="container login-container">
        <div class="login-box">
            <div class="logo-section">
                <img src="https://virtual.fundetec.edu.co/wp-content/uploads/2024/09/las-mejores-carreras-tecnicas-en-el-sena.png" alt="Logo" class="animated-logo">
                <h3>Bienvenido</h3>
                <p>Ingresa a tu cuenta</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" action="login.php" method="POST">
                <div class="form-group">
                    <input type="email" name="usuario" class="form-control" placeholder="Correo electrónico" required>
                </div>
                <div class="form-group">
                    <input type="password" name="contraseña" class="form-control" placeholder="Contraseña" required>
                </div>
                <button type="submit" class="btn-login">Iniciar Sesión</button>
            </form>

            <!-- Enlace de recuperación de contraseña -->
            <div class="text-center mt-3">
                <a href="contraseña/recuperar.php" class="text-decoration-none text-muted">¿Olvidaste tu contraseña?</a>
            </div>

        </div>
    </div>
</body>
</html>
