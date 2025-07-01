<?php
// Inicia la sesión y conecta con la base de datos
session_start();
include 'conexion.php'; 

// Verifica que el usuario sea almacenista
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'almacenista') {
    header("Location: login.php");
    exit;
}

// Obtiene correo y rol del usuario
$correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$rol = ucfirst($_SESSION['rol'] ?? 'Almacenista');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sistema Almacén SGA - Almacenista</title>
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Estilos personalizados para el panel de almacenista -->
    <link rel="stylesheet" href="css/almacenista.css">
</head>
<body class="light">
  <div class="container">
    <!-- Sidebar de navegación -->
    <div class="sidebar">
        <img src="https://virtual.fundetec.edu.co/wp-content/uploads/2024/09/las-mejores-carreras-tecnicas-en-el-sena.png" class="animated-logo" alt="Logo SENA" />
        <div class="head">
            <div class="user-details">
                <p class="title"><?php echo htmlspecialchars($rol); ?></p>
                <p class="name"><?php echo htmlspecialchars($correo); ?></p>
            </div>
        </div>
        <div class="menu">
            <ul>
                <!-- Opciones del menú lateral -->
                <li><a href="funcionarios/1registrofuncionario.php"><i class="icon fas fa-laptop"></i><span class="text">Registro Funcionarios</span></a></li>
                <li><a href="prestamo1/prestamos.php"><i class="icon fas fa-hand-holding"></i><span class="text">Préstamos</span></a></li>
                <li><a href="lista_prestamos/listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listar Préstamos</span></a></li>
                <li><a href="devoluciones/4devolucion.php"><i class="icon fas fa-box-open"></i><span class="text">Devoluciones</span></a></li>
                <li><a href="lista_prestamos/reporte_devolucion.php"><i class="icon fas fa-file-alt"></i><span class="text">Reportes Devoluciones</span></a></li>
                <li><a href="insumos/2insumos.php"><i class="icon fas fa-boxes"></i><span class="text">Insumos</span></a></li>
                <li><a href="reportes/reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes Conexión</span></a></li>
                <li><a href="#"><i class="icon fas fa-info-circle"></i><span class="text">Ayuda</span></a></li>
                <li><a href="logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
            </ul>
        </div>
    </div>
    <!-- Contenido principal -->
    <div class="main">
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        <div class="header">
            <h1 class="welcome">Bienvenido al Sistema Almacén CTGI</h1>
        </div>
        <p style="margin-bottom: 10px;">Seleccione una opción en el menú para gestionar el sistema.</p>
        <p style="margin-bottom: 30px; font-size: 15px; max-width: 600px;">Desde aquí puedes controlar todos los aspectos del almacén, incluyendo insumos, préstamos, devoluciones y reportes en tiempo real.</p>
        
        <?php include 'dashboard.php'; // Incluimos el panel reutilizable ?>

    </div>
  </div>
<script>
    // Función para alternar entre tema claro y oscuro
    function toggleTheme() {
        document.body.classList.toggle('dark');
        document.body.classList.toggle('light');
        const isDark = document.body.classList.contains('dark');
        const icon = document.getElementById('theme-icon');
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }
</script>
</body>
</html>