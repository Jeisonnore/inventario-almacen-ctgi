<?php
session_start();
include("../conexion.php");

// --- VALIDACIÓN DE SESIÓN Y ROL ---
if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    header('Location: ../login.php');
    exit;
}
$allowed_roles = ['administrador', 'almacenista'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: ../acceso_denegado.php');
    exit;
}

// --- LÓGICA PARA EL SIDEBAR ---
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$user_rol = ucfirst($_SESSION['rol']);

// --- LÓGICA DE LA PÁGINA ---
$instructor = null;
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $error_message = "Error: ID de instructor inválido o no proporcionado.";
} else {
    $id = $_GET['id'];
    $sql = "SELECT * FROM instructores WHERE id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $instructor = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$instructor) {
        $error_message = "No se encontró el instructor con el ID proporcionado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Instructor</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/editar_funcionario.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="light">

<div class="container-fluid">
    <div class="sidebar">
        <img src="https://virtual.fundetec.edu.co/wp-content/uploads/2024/09/las-mejores-carreras-tecnicas-en-el-sena.png" class="animated-logo" alt="Logo SENA" />
        <div class="head">
            <div class="user-details">
                <p class="title"><?php echo htmlspecialchars($user_rol); ?></p>
                <p class="name"><?php echo htmlspecialchars($user_correo); ?></p>
            </div>
        </div>
        <div class="menu">
             <ul>
                <li><a href="<?php echo ($_SESSION['rol'] === 'administrador') ? '../admin.php' : '../almacenista.php'; ?>"><i class="icon fas fa-home"></i><span class="text">Inicio</span></a></li>
                <li class="active"><a href="../funcionarios/1registrofuncionario.php"><i class="icon fas fa-user-plus"></i><span class="text">Funcionarios</span></a></li>
                <li><a href="../prestamo1/prestamos.php"><i class="icon fas fa-hand-holding-hand"></i><span class="text">Préstamos</span></a></li>
                <li><a href="../lista_prestamos/listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listados</span></a></li>
                <li><a href="../devoluciones/4devolucion.php"><i class="icon fas fa-undo"></i><span class="text">Devoluciones</span></a></li>
                <li><a href="../insumos/2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
                <li><a href="../reportes/reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes</span></a></li>
                <li><a href="../logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        <header><h1><i class="fas fa-edit"></i> Editar Instructor</h1></header>
        <hr>

        <section class="card-form p-4 rounded" style="max-width: 800px; margin: auto;">
            <?php if (isset($error_message)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: <?= json_encode($error_message) ?>,
                            confirmButtonText: 'Entendido'
                        });
                    });
                </script>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php elseif ($instructor): ?>
                <form method="POST" action="editar3.php" onsubmit="return validarFormulario();" class="row g-3">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($instructor['id']); ?>">

                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($instructor['nombre']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="apellido" class="form-label">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($instructor['apellido']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="cedula" class="form-label">Cédula:</label>
                        <input type="text" id="cedula" name="cedula" class="form-control" value="<?php echo htmlspecialchars($instructor['cedula']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo:</label>
                        <input type="email" id="correo" name="correo" class="form-control" value="<?php echo htmlspecialchars($instructor['correo']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($instructor['telefono']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="estado" class="form-label">Estado:</label>
                        <select id="estado" name="estado" class="form-select" required>
                            <option value="activo" <?php echo ($instructor['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo ($instructor['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                    
                    <p id="mensajeError" style="color: red; font-weight: bold;" class="col-12"></p>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" name="btn_actualizar">Guardar Cambios</button>
                        <a href="1registrofuncionario.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    function toggleTheme() {
        document.body.classList.toggle('dark');
        document.body.classList.toggle('light');
        if(document.body.classList.contains('dark')) { localStorage.setItem('theme', 'dark'); } 
        else { localStorage.setItem('theme', 'light'); }
        const icon = document.getElementById('theme-icon');
        icon.classList.toggle('fa-moon');
        icon.classList.toggle('fa-sun');
    }

    $(document).ready(function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            document.body.classList.remove('light');
            $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
        }

        // Inicializar Select2
        $('#estado').select2({
            theme: "bootstrap-5",
            minimumResultsForSearch: Infinity // Oculta la barra de búsqueda
        });
    });

    function validarFormulario() {
            const nombre = document.getElementById("nombre").value.trim();
            const apellido = document.getElementById("apellido").value.trim();
            const cedula = document.getElementById("cedula").value.trim();
            const telefono = document.getElementById("telefono").value.trim();
            const correo = document.getElementById("correo").value.trim();
            const mensajeError = document.getElementById("mensajeError");

            if (!nombre || !apellido || !cedula || !telefono || !correo) {
                mensajeError.textContent = "Todos los campos son obligatorios.";
                return false;
            }

            if (!/^[0-9]{6,10}$/.test(cedula)) {
                mensajeError.textContent = "La cédula debe tener entre 6 y 10 dígitos numéricos.";
                return false;
            }

            if (!/^[0-9]{7,10}$/.test(telefono)) {
                mensajeError.textContent = "El teléfono debe tener entre 7 y 10 dígitos numéricos.";
                return false;
            }

            const correoRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!correoRegex.test(correo)) {
                mensajeError.textContent = "El correo electrónico no es válido.";
                return false;
            }

            mensajeError.textContent = "";
            return true;
        }
</script>
</body>
</html>