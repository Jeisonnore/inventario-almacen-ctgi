<?php
// Inicia la sesión y conecta con la base de datos
session_start();
include '../conexion.php'; // Asegúrate que la ruta a tu archivo de conexión sea correcta.

// Validación de sesión y rol
if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    header('Location: ../login.php');
    exit;
}
$allowed_roles = ['administrador', 'almacenista'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: ../acceso_denegado.php');
    exit;
}

// Obtener ID y nombre del almacenista
$almacenista_id = null;
$nombre_almacenista_responsable = '';
if ($_SESSION['rol'] === 'administrador') {
    // Si es administrador, obtiene el primer almacenista activo
    $stmt = $connect->prepare("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM almacenista WHERE estado = 'activo' LIMIT 1");
    $stmt->execute();
    $almacenista = $stmt->get_result()->fetch_assoc();
    if (!$almacenista) die("No hay almacenistas activos registrados");
    $almacenista_id = $almacenista['id'];
    $nombre_almacenista_responsable = $almacenista['nombre_completo'];
} else {
    // Si es almacenista, obtiene su propio perfil
    $stmt = $connect->prepare("SELECT id FROM almacenista WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $almacenista = $stmt->get_result()->fetch_assoc();
    if (!$almacenista) die("No tienes un perfil de almacenista asociado");
    $almacenista_id = $almacenista['id'];
}

// Datos de usuario para mostrar en el sidebar
$correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$rol = ucfirst($_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <!-- Metadatos y enlaces a estilos y scripts externos -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Préstamos</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
  <link rel="stylesheet" href="../css/prestamos.css">

</head>
<body class="light">
<div class="container-fluid">
  <!-- Sidebar lateral con menú y datos de usuario -->
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
        <!-- Menú de navegación -->
        <li><a href="<?php echo ($_SESSION['rol'] === 'administrador') ? '../admin.php' : '../almacenista.php'; ?>"><i class="icon fas fa-home"></i><span class="text">Inicio</span></a></li>
        <li><a href="../funcionarios/1registrofuncionario.php"><i class="icon fas fa-user-plus"></i><span class="text">Funcionarios</span></a></li>
        <li class="active"><a href="prestamos.php"><i class="icon fas fa-hand-holding-hand"></i><span class="text">Préstamos</span></a></li>
        <li><a href="../lista_prestamos/listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listados</span></a></li>
        <li><a href="../devoluciones/4devolucion.php"><i class="icon fas fa-undo"></i><span class="text">Devoluciones</span></a></li>
        <li><a href="../insumos/2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
        <li><a href="../reportes/reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes</span></a></li>
        <li><a href="../logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
      </ul>
    </div>
  </div>
  
  <div class="main">
    <!-- Botón para cambiar tema -->
    <div class="toggle-mode-icons">
      <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
    </div>
    
    <header><h1><i class="fas fa-hand-holding-hand"></i> Registro de Préstamo</h1></header>
    <hr>
    
    <section class="card-form p-4 rounded">
        <!-- Selección de tipo de préstamo -->
        <div class="mb-3">
            <label for="tipo_prestamo" class="form-label"><strong>1. Seleccione tipo de préstamo:</strong></label>
            <select id="tipo_prestamo" class="form-select" style="max-width: 400px;">
                <option value="">-- Seleccionar --</option>
                <option value="equipos">Equipos</option>
                <option value="materiales">Materiales</option>
            </select>
        </div>

        <!-- Formulario de registro de préstamo -->
        <form id="form_prestamo" action="registro_prestamo.php" method="POST" style="display:none; margin-top: 20px;">
            <input type="hidden" name="almacenista_id" value="<?= $almacenista_id ?>">
            
            <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <!-- Solo visible para administradores -->
                <div class="mb-3">
                    <label class="form-label"><strong>2. Almacenista responsable:</strong></label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($nombre_almacenista_responsable) ?>" readonly>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="instructor" class="form-label"><strong><?php echo ($_SESSION['rol'] === 'administrador') ? '3.' : '2.'; ?> Instructor:</strong></label>
                <select id="instructor" name="instructor_id" class="form-select" required>
                    <option value="">-- Seleccionar Instructor --</option>
                    <?php
                    // Consulta de instructores activos
                    $stmt_instr = $connect->prepare("SELECT id, nombre, apellido, cedula FROM instructores WHERE estado = 'activo'");
                    $stmt_instr->execute();
                    $result_instr = $stmt_instr->get_result();
                    
                    while ($row_instr = $result_instr->fetch_assoc()) {
                        $id = htmlspecialchars($row_instr['id']);
                        $cedula = htmlspecialchars($row_instr['cedula']);
                        $nombre = htmlspecialchars($row_instr['nombre']);
                        $apellido = htmlspecialchars($row_instr['apellido']);
                        echo "<option value='$id'>$cedula - $nombre $apellido</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="row">
                <!-- Fechas de préstamo y devolución -->
                <div class="col-md-6 mb-3">
                    <label for="fecha_prestamo" class="form-label"><strong>Fecha préstamo:</strong></label>
                    <input type="datetime-local" class="form-control" id="fecha_prestamo" name="fecha_prestamo" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_devolucion" class="form-label"><strong>Fecha devolución:</strong></label>
                    <input type="datetime-local" class="form-control" id="fecha_devolucion" name="fecha_devolucion">
                    <small class="text-muted">Requerido para equipos y materiales no consumibles.</small>
                </div>
            </div>

            <hr>
            <h3 class="mt-4"><strong>Ítems a prestar</strong></h3>

            <!-- Tabla de equipos disponibles -->
            <div id="prestamo_equipo" style="display:none; margin-top:20px;" class="table-responsive">
                <table id="tabla_equipos" class="display table table-striped table-hover" style="width:100%;">
                    <thead><tr><th>Marca</th><th>Serie</th><th>Estado</th><th>Seleccionar</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- Tabla de materiales disponibles -->
            <div id="prestamo_material" style="display:none; margin-top:20px;" class="table-responsive">
                <table id="tabla_materiales" class="display table table-striped table-hover" style="width:100%;">
                    <thead><tr><th>Nombre</th><th>Tipo</th><th>Stock</th><th>Cantidad</th><th>Seleccionar</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>

            <button type="button" class="btn btn-primary mt-4" onclick="validarYEnviar()">Registrar Préstamo</button>
        </form>
    </section>
  </div>
</div>

<!-- Scripts de librerías y lógica JS para la página -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function toggleTheme() {
    // Cambia el tema claro/oscuro y guarda preferencia en localStorage
    document.body.classList.toggle('dark');
    document.body.classList.toggle('light');
    if(document.body.classList.contains('dark')) { localStorage.setItem('theme', 'dark'); } 
    else { localStorage.setItem('theme', 'light'); }
    const icon = document.getElementById('theme-icon');
    icon.classList.toggle('fa-moon');
    icon.classList.toggle('fa-sun');
}

// Valida el formulario antes de enviarlo
function validarYEnviar() {
    const tipo = $('#tipo_prestamo').val();
    const form = $('#form_prestamo')[0];
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    if (tipo === 'equipos') {
        if ($('input[name="equipos[]"]:checked').length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Debe seleccionar al menos un equipo para el préstamo.',
            });
            return;
        }
    } else if (tipo === 'materiales') {
        if ($('input[name="materiales[]"]:checked').length === 0) {
             Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Debe seleccionar al menos un material para el préstamo.',
            });
            return;
        }
        let error = false;
        $('input[name="materiales[]"]:checked').each(function() {
            const tipoMaterial = $(this).closest('tr').find('td:nth-child(2)').text().trim().toLowerCase();
            const fechaDevolucion = $('#fecha_devolucion').val();

            if (tipoMaterial === 'no consumible' && !fechaDevolucion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Los materiales no consumibles requieren una fecha de devolución obligatoria.',
                });
                $('#fecha_devolucion').focus();
                error = true;
                return false;
            }
        });

        if (error) return;
    }
    
    form.submit();
}

$(document).ready(function() {
    // Aplica el tema guardado en localStorage
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        document.body.classList.remove('light');
        $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
    }

    // Inicializa select2 en los selects
    $('#tipo_prestamo, #instructor').select2({
        theme: "bootstrap-5",
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
    });

    // Opciones comunes para DataTables
    const commonDtOptions = {
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "No hay registros disponibles",
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron resultados",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    };

    // Inicializa DataTables para equipos y materiales
    const tablaEquipos = $('#tabla_equipos').DataTable({
        ...commonDtOptions,
        "columns": [
            { "data": "marca" }, { "data": "serie" }, { "data": "estado" },
            { "data": "id", "render": function(d) { return `<input type="checkbox" class="form-check-input" name="equipos[]" value="${d}">`; }, "orderable": false }
        ]
    });
    
    const tablaMateriales = $('#tabla_materiales').DataTable({
        ...commonDtOptions,
        "columns": [
            { "data": "nombre" }, { "data": "tipo" }, { "data": "cantidad" },
            { "data": "id", "render": function(d, t, r) {
                let max = (r.tipo && r.tipo.toLowerCase() === 'no consumible') ? 1 : r.cantidad;
                return `<input type="number" name="cantidad[${d}]" min="1" max="${max}" class="form-control form-control-sm" value="1" style="width: 80px;">`;
            }, "orderable": false },
            { "data": "id", "render": function(d) { return `<input type="checkbox" class="form-check-input" name="materiales[]" value="${d}">`; }, "orderable": false }
        ]
    });

    // Cambia el formulario según el tipo de préstamo seleccionado
    $('#tipo_prestamo').on('change', function() {
        const tipo = $(this).val();
        
        $('#form_prestamo').toggle(tipo !== '');
        $('#prestamo_equipo').toggle(tipo === 'equipos');
        $('#prestamo_material').toggle(tipo === 'materiales');
        $('#fecha_devolucion').prop('required', tipo === 'equipos');
        
        tablaEquipos.clear().draw();
        tablaMateriales.clear().draw();
        $('#instructor').val('').trigger('change');

        if (tipo) {
            // Carga los ítems disponibles vía AJAX
            $.ajax({
                url: 'obtener_disponibles.php',
                type: 'GET', data: { tipo: tipo }, dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        Swal.fire({
                           icon: 'error',
                           title: 'Error del servidor',
                           text: response.error,
                        });
                        return;
                    }
                    const tabla = (tipo === 'equipos') ? tablaEquipos : tablaMateriales;
                    tabla.clear().rows.add(response.data).draw();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: "No se pudieron cargar los datos. Verifique la consola y el archivo 'obtener_disponibles.php'"
                    });
                }
            });
        }
    });

    // Establece la fecha y hora actual por defecto en el campo de préstamo
    const now = new Date();
    const localISOTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    $('#fecha_prestamo').val(localISOTime);
});
</script>

<?php
// Muestra alertas de éxito o error si existen en la URL
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
    echo "<script>
        // Usamos DOMContentLoaded para asegurar que la página esté cargada antes de mostrar la alerta
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '¡Operación Exitosa!',
                text: '{$success_message}',
                confirmButtonText: 'Entendido'
            });
        });
    </script>";
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Ha ocurrido un error',
                text: '{$error_message}',
                confirmButtonText: 'Intentar de nuevo'
            });
        });
    </script>";
}
?>
</body>
</html>