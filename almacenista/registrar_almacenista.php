<?php
// Iniciar sesión para manejar variables de sesión
session_start();
// Incluir archivo de conexión a la base de datos
include("../conexion.php");

// Verificar si el usuario tiene sesión activa y es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php'); // Redirigir si no es administrador
    exit;
}

// Obtener parámetros de filtro del GET
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : ''; // Fecha desde para filtrar
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : ''; // Fecha hasta para filtrar
$estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos'; // Estado para filtrar (activo/inactivo/todos)

// Construir consulta SQL base
$sql = "SELECT * FROM almacenista WHERE 1=1"; // 1=1 para facilitar añadir condiciones

// Añadir filtros según los parámetros recibidos
if (!empty($fecha_desde)) {
    $sql .= " AND DATE(hora_ingreso) >= '" . mysqli_real_escape_string($connect, $fecha_desde) . "'";
}
if (!empty($fecha_hasta)) {
    $sql .= " AND DATE(hora_ingreso) <= '" . mysqli_real_escape_string($connect, $fecha_hasta) . "'";
}
if ($estado != 'todos') {
    $sql .= " AND estado = '" . mysqli_real_escape_string($connect, $estado) . "'";
}

// Ordenar por ID descendente y ejecutar consulta
$sql .= " ORDER BY id DESC";
$result = mysqli_query($connect, $sql);

// Obtener información del usuario para mostrar en el sidebar
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com'; // Correo del usuario o valor por defecto
$user_rol = ucfirst($_SESSION['rol']); // Rol del usuario con primera letra en mayúscula
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Almacenistas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Hojas de estilo y librerías -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/registrar_almacenista.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="light">

<!-- Contenedor principal -->
<div class="container-fluid">
    <!-- Sidebar con menú de navegación -->
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
        <!-- Botón para cambiar tema claro/oscuro -->
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        
        <!-- Título de la página -->
        <header><h1><i class="fas fa-user-tie"></i> Registro de Almacenistas</h1></header>
        <hr>

        <!-- Sección de contenido en dos columnas -->
        <div class="row">
            <!-- Columna izquierda: Formularios -->
            <div class="col-lg-5">
                <!-- Formulario para registrar nuevo almacenista -->
                <section class="card-form mb-4 p-4 rounded">
                    <h2>Registrar Nuevo Almacenista</h2>
                    <p class="text-muted small">La contraseña se generará automáticamente usando el número de cédula.</p>
                    <p id="mensajeError" class="text-danger fw-bold"></p>
                    <form id="miformulario" action="inseralmacenista.php" method="post" onsubmit="return validarFormulario();" class="row g-3">
                        <div class="col-md-6"><label for="nombre" class="form-label">Nombre:</label><input type="text" id="nombre" class="form-control" name="nombre" required></div>
                        <div class="col-md-6"><label for="apellido" class="form-label">Apellido:</label><input type="text" id="apellido" class="form-control" name="apellido" required></div>
                        <div class="col-md-6"><label for="cedula" class="form-label">Cédula:</label><input type="text" id="cedula" class="form-control" name="cedula" required></div>
                        <div class="col-md-6"><label for="telefono" class="form-label">Teléfono:</label><input type="text" id="telefono" class="form-control" name="telefono" required></div>
                        <div class="col-12"><label for="correo" class="form-label">Correo (será su usuario):</label><input type="email" id="correo" class="form-control" name="correo" required></div>
                        <div class="col-12"><button type="submit" class="btn btn-success">Registrar</button></div>
                    </form>
                </section>
                
                <!-- Formulario para filtrar almacenistas -->
                <section class="card-form mb-4 p-4 rounded">
                    <h2>Filtrar Almacenistas</h2>
                    <form method="get" class="row g-3">
                        <div class="col-md-6"><label for="fecha_desde" class="form-label">Fecha desde:</label><input type="date" id="fecha_desde" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>"></div>
                        <div class="col-md-6"><label for="fecha_hasta" class="form-label">Fecha hasta:</label><input type="date" id="fecha_hasta" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"></div>
                        <div class="col-12"><label for="estado_filtro" class="form-label">Estado:</label><select id="estado_filtro" class="form-select" name="estado"><option value="todos" <?= $estado == 'todos' ? 'selected' : '' ?>>Todos</option><option value="activo" <?= $estado == 'activo' ? 'selected' : '' ?>>Activo</option><option value="inactivo" <?= $estado == 'inactivo' ? 'selected' : '' ?>>Inactivo</option></select></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary">Filtrar</button><a href="registrar_almacenista.php" class="btn btn-secondary ms-2">Limpiar</a></div>
                    </form>
                </section>
            </div>
            
            <!-- Columna derecha: Tabla de almacenistas -->
            <div class="col-lg-7">
                <section class="card-form p-4 rounded">
                    <h2>Listado de Almacenistas</h2>
                    <div class="table-responsive">
                        <table id="miTabla1" class="display table table-striped table-hover" style="width:100%;">
                            <thead><tr><th>ID</th><th>Nombre</th><th>Apellidos</th><th>Cédula</th><th>Correo</th><th>Teléfono</th><th>Estado</th><th>Registro</th><th>Acciones</th></tr></thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td><?= htmlspecialchars($row['apellido']) ?></td>
                                        <td><?= htmlspecialchars($row['cedula']) ?></td>
                                        <td><?= htmlspecialchars($row['correo']) ?></td>
                                        <td><?= htmlspecialchars($row['telefono']) ?></td>
                                        <td><span class="badge bg-<?= $row['estado'] == 'activo' ? 'success' : 'danger' ?>"><?= htmlspecialchars($row['estado']) ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($row['hora_ingreso'])) ?></td>
                                        <td class="text-center"><a href="editar_2alma.php?id=<?= urlencode($row['id']) ?>" class="btn btn-success btn-sm">Editar</a><a href="eliminar_alma.php?id=<?= urlencode($row['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro?');">Eliminar</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>

<!-- Scripts de librerías -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Sistema de alertas con SweetAlert2 -->
<?php
// Mostrar mensajes de sesión si existen
if (isset($_SESSION['mensaje'])):
    $tipo = $_SESSION['tipo_mensaje'] ?? 'info'; // Tipo de mensaje (success, error, info)
    $mensaje = $_SESSION['mensaje']; // Contenido del mensaje
    // Limpiar variables de sesión para evitar que se muestre nuevamente
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
?>
<script>
    // Mostrar alerta cuando el DOM esté cargado
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?= $tipo === "success" ? "success" : ($tipo === "error" ? "error" : "info") ?>',
            title: <?= $tipo === "success" ? "'¡Éxito!'" : ($tipo === "error" ? "'Error'" : "'Aviso'") ?>,
            text: <?= json_encode($mensaje) ?>,
            confirmButtonText: 'Entendido'
        });
    });
</script>
<?php endif; ?>

<!-- Scripts personalizados -->
<script>
    // Función para cambiar entre tema claro y oscuro
    function toggleTheme() {
        document.body.classList.toggle('dark');
        document.body.classList.toggle('light');
        if(document.body.classList.contains('dark')) { 
            localStorage.setItem('theme', 'dark'); // Guardar preferencia
        } else { 
            localStorage.setItem('theme', 'light'); 
        }
        const icon = document.getElementById('theme-icon');
        icon.classList.toggle('fa-moon');
        icon.classList.toggle('fa-sun');
    }

    // Cuando el documento esté listo
    $(document).ready(function() {
        // Aplicar tema guardado en localStorage
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            document.body.classList.remove('light');
            $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
        }

        // Inicializar select2 para selects
        $('.form-select').select2({
            theme: "bootstrap-5"
        });
        
        // Configurar DataTable con botones de exportación
        $('#miTabla1').DataTable({
            dom: 'lBfrtip', // Diseño de elementos de la tabla
            buttons: [
                { 
                    extend: 'excelHtml5', 
                    text: '<i class="fas fa-file-excel"></i> Exportar a Excel', 
                    title: 'Listado de Almacenistas', 
                    className: 'btn-success', 
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] } 
                },
                { 
                    extend: 'pdfHtml5', 
                    text: '<i class="fas fa-file-pdf"></i> PDF', 
                    title: 'Listado de Almacenistas', 
                    className: 'btn-danger', 
                    orientation: 'landscape', 
                    pageSize: 'A4', 
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] } 
                }
            ],
            lengthMenu: [10, 25, 50], // Opciones de paginación
            pageLength: 10, // Número de registros por página
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }, // Idioma español
            columnDefs: [ { orderable: false, targets: [8] } ] // Columna de acciones no ordenable
        });
    });

    // Función para validar el formulario antes de enviar
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