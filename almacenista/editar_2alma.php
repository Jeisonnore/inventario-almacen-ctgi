<?php
// Inicia la sesión
session_start();
// Incluye el archivo de conexión a la base de datos
include("../conexion.php");

// --- VALIDACIÓN DE SESIÓN Y ROL ---
// Verifica si el usuario ha iniciado sesión y si su rol es 'administrador'
if (!isset($_SESSION['id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    // Si no es administrador, redirige al login
    header('Location: ../login.php');
    exit;
}

// --- LÓGICA DE LA PÁGINA ---
// Inicializa la variable $almacenista
$almacenista = null;
// Verifica si el parámetro 'id' está presente y es un dígito
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    // Si no es válido, muestra mensaje de error y redirige
    $_SESSION['mensaje'] = "Error: ID de almacenista inválido.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: registrar_almacenista.php");
    exit;
}

// Obtiene el id del almacenista desde la URL
$id = $_GET['id'];
// Prepara la consulta para obtener los datos del almacenista
$stmt = $connect->prepare("SELECT * FROM almacenista WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
// Obtiene los datos del almacenista como un array asociativo
$almacenista = $resultado->fetch_assoc();
$stmt->close();

// Si no se encuentra el almacenista, muestra mensaje de error y redirige
if (!$almacenista) {
    $_SESSION['mensaje'] = "Error: Almacenista no encontrado.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: registrar_almacenista.php");
    exit;
}

// --- LÓGICA PARA EL SIDEBAR ---
// Obtiene el correo y rol del usuario de la sesión
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$user_rol = ucfirst($_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Almacenista</title>
    
    <!-- Incluye Bootstrap, FontAwesome, Select2 y estilos personalizados -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/editar_almacenista.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- <style> ... </style> eliminado si existía -->
</head>
<body class="light">

<div class="container-fluid">
    <!-- Sidebar de navegación -->
    <div class="sidebar">
        <!-- Logo -->
        <img src="https://virtual.fundetec.edu.co/wp-content/uploads/2024/09/las-mejores-carreras-tecnicas-en-el-sena.png" class="animated-logo" alt="Logo SENA" />
        <div class="head">
            <div class="user-details">
                <!-- Muestra el rol y correo del usuario -->
                <p class="title"><?php echo htmlspecialchars($user_rol); ?></p>
                <p class="name"><?php echo htmlspecialchars($user_correo); ?></p>
            </div>
        </div>
        <!-- Menú de navegación -->
        <div class="menu">
            <ul>
                <li><a href="../admin.php"><i class="icon fas fa-home"></i><span class="text">Inicio</span></a></li>
                <li><a href="../funcionarios/1registrofuncionario.php"><i class="icon fas fa-user-plus"></i><span class="text">Instructores</span></a></li>
                <li class="active"><a href="registrar_almacenista.php"><i class="icon fas fa-user-tie"></i><span class="text">Almacenistas</span></a></li>
                <li><a href="../prestamo1/prestamos.php"><i class="icon fas fa-hand-holding-hand"></i><span class="text">Préstamos</span></a></li>
                <li><a href="../lista_prestamos/listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listados</span></a></li>
                <li><a href="../devoluciones/4devolucion.php"><i class="icon fas fa-undo"></i><span class="text">Devoluciones</span></a></li>
                <li><a href="../insumos/2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
                <li><a href="../reportes/reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes</span></a></li>
                <li><a href="../logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
            </ul>
        </div>
    </div>

    <!-- Contenido principal -->
    <main class="main">
        <!-- Botón para cambiar el tema -->
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        
        <!-- Encabezado -->
        <header><h1><i class="fas fa-edit"></i> Editar Almacenista</h1></header>
        <hr>

        <!-- Formulario para editar almacenista -->
        <section class="card-form p-4 rounded" style="max-width: 800px; margin: auto;">
            <!-- Mensaje de error -->
            <p id="mensajeError" class="text-danger fw-bold"></p>
            <form method="POST" action="editar3alma.php" onsubmit="return validarFormulario();" class="row g-3">
                <!-- Campos ocultos con el id y usuario_id -->
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($almacenista['id']); ?>">
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($almacenista['usuario_id']); ?>">

                <!-- Campos del formulario -->
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($almacenista['nombre']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="apellido" class="form-label">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($almacenista['apellido']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="cedula" class="form-label">Cédula:</label>
                    <input type="text" id="cedula" name="cedula" class="form-control" value="<?php echo htmlspecialchars($almacenista['cedula']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="correo" class="form-label">Correo:</label>
                    <input type="email" id="correo" name="correo" class="form-control" value="<?php echo htmlspecialchars($almacenista['correo']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="telefono" class="form-label">Teléfono:</label>
                    <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($almacenista['telefono']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="estado" class="form-label">Estado:</label>
                    <select id="estado" name="estado" class="form-select" required>
                        <option value="activo" <?php echo ($almacenista['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo ($almacenista['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <!-- Botones de acción -->
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary" name="btn_actualizar">Guardar Cambios</button>
                    <a href="registrar_almacenista.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </section>
    </main>
</div>

<!-- Scripts de librerías externas -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Función para alternar entre modo claro y oscuro
function toggleTheme() {
    document.body.classList.toggle('dark');
    document.body.classList.toggle('light');
    if(document.body.classList.contains('dark')) { 
        localStorage.setItem('theme', 'dark'); 
    } else { 
        localStorage.setItem('theme', 'light'); 
    }
    const icon = document.getElementById('theme-icon');
    icon.classList.toggle('fa-moon');
    icon.classList.toggle('fa-sun');
}

// Cuando el documento está listo
$(document).ready(function() {
    // Aplica el tema guardado en localStorage
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        document.body.classList.remove('light');
        $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
    }

    // Inicializa el select2 para el campo estado
    $('#estado').select2({
        theme: "bootstrap-5",
        minimumResultsForSearch: Infinity
    });
});

// Función para validar el formulario antes de enviarlo
function validarFormulario() {
    const nombre = document.getElementById("nombre").value.trim();
    const apellido = document.getElementById("apellido").value.trim();
    const cedula = document.getElementById("cedula").value.trim();
    const telefono = document.getElementById("telefono").value.trim();
    const correo = document.getElementById("correo").value.trim();
    const mensajeError = document.getElementById("mensajeError");

    // Verifica que todos los campos estén llenos
    if (!nombre || !apellido || !cedula || !telefono || !correo) {
        mensajeError.textContent = "Todos los campos son obligatorios.";
        return false;
    }

    // Valida el formato de la cédula
    if (!/^[0-9]{6,10}$/.test(cedula)) {
        mensajeError.textContent = "La cédula debe tener entre 6 y 10 dígitos.";
        return false;
    }

    // Valida el formato del teléfono
    if (!/^[0-9]{7,10}$/.test(telefono)) {
        mensajeError.textContent = "El teléfono debe tener entre 7 y 10 dígitos.";
        return false;
    }

    // Valida el formato del correo electrónico
    const correoRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!correoRegex.test(correo)) {
        mensajeError.textContent = "El correo no es válido.";
        return false;
    }

    // Si todo está bien, limpia el mensaje de error
    mensajeError.textContent = "";
    return true;
}
</script>
</body>
</html>