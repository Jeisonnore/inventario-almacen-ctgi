<?php
// Inicia la sesión y conecta con la base de datos
session_start();
include '../conexion.php';

// --- VALIDACIÓN DE SESIÓN Y ROL ---
// Verifica que el usuario esté autenticado y tenga un rol permitido
if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    header('Location: ../login.php');
    exit;
}
$allowed_roles = ['administrador', 'almacenista'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: ../acceso_denegado.php');
    exit;
}

// Obtener ID de almacenista según el rol
// Si es administrador, obtiene el primer almacenista activo
// Si es almacenista, obtiene su propio id de almacenista
$almacenista_id = null;
if ($_SESSION['rol'] === 'administrador') {
    $stmt = $connect->prepare("SELECT id FROM almacenista WHERE estado = 'activo' LIMIT 1");
    $stmt->execute();
    $almacenista_res = $stmt->get_result()->fetch_assoc();
    if ($almacenista_res) {
        $almacenista_id = $almacenista_res['id'];
    }
} else {
    $stmt = $connect->prepare("SELECT id FROM almacenista WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $almacenista_res = $stmt->get_result()->fetch_assoc();
    if (!$almacenista_res) {
        die("No tienes un perfil de almacenista asociado");
    }
    $almacenista_id = $almacenista_res['id'];
}

// --- LÓGICA PARA EL SIDEBAR ---
// Obtiene el correo y rol del usuario para mostrar en el sidebar
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$user_rol = ucfirst($_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Devolución</title>

    <!-- Carga de estilos y librerías externas -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/devolucion.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="light">

<div class="container-fluid">
    <!-- Sidebar de navegación -->
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
                <!-- Menú de navegación lateral -->
                <li><a href="<?php echo ($_SESSION['rol'] === 'administrador') ? '../admin.php' : '../almacenista.php'; ?>"><i class="icon fas fa-home"></i><span class="text">Inicio</span></a></li>
                <li><a href="../funcionarios/1registrofuncionario.php"><i class="icon fas fa-user-plus"></i><span class="text">Funcionarios</span></a></li>
                <li><a href="../prestamo1/prestamos.php"><i class="icon fas fa-hand-holding-hand"></i><span class="text">Préstamos</span></a></li>
                <li><a href="../lista_prestamos/listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listados</span></a></li>
                <li class="active"><a href="../devoluciones/4devolucion.php"><i class="icon fas fa-undo"></i><span class="text">Devoluciones</span></a></li>
                <li><a href="../insumos/2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
                <li><a href="../reportes/reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes</span></a></li>
                <li><a href="../logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <!-- Botón para cambiar tema claro/oscuro -->
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        
        <header><h1><i class="fas fa-undo"></i> Registro de Devoluciones</h1></header>
        <hr>

        <?php
        // Mensajes de éxito o error al registrar devolución
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success" id="mensaje-success">Devolución registrada exitosamente</div>';
        } elseif (isset($_GET['error'])) {
            echo '<div class="alert alert-danger" id="mensaje-error">Error al registrar devolución: ' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>

        <section class="card-form p-4 rounded">
            <!-- Selección del tipo de ítem a devolver -->
            <div class="form-group mb-3">
                <label for="tipo_devolucion" class="form-label"><strong>1. Seleccione el tipo de ítem a devolver:</strong></label>
                <select id="tipo_devolucion" class="form-select" style="max-width: 400px;">
                    <option value="">-- Seleccionar --</option>
                    <option value="equipos">Equipos</option>
                    <option value="materiales">Materiales</option>
                </select>
            </div>

            <!-- Formulario de registro de devolución -->
            <form id="form_devoluciones" action="registro_devolucion.php" method="POST" style="display:none;">
                <input type="hidden" name="almacenista" id="input_almacenista_id" value="<?= htmlspecialchars($almacenista_id) ?>">
                
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                    <!-- Si es administrador, puede seleccionar el almacenista que recibe -->
                    <div class="form-group mb-3">
                        <label for="select_almacenista" class="form-label"><strong>2. Almacenista que recibe:</strong></label>
                        <select id="select_almacenista" class="form-select">
                            <option value="">-- Seleccionar Almacenista --</option>
                            <?php
                            $stmt_alm = $connect->query("SELECT id, CONCAT(nombre, ' ', apellido) as nombre FROM almacenista WHERE estado = 'activo'");
                            while ($row = $stmt_alm->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php else: ?>
                    <!-- Si es almacenista, solo muestra su nombre -->
                    <div class="form-group mb-3">
                        <label class="form-label"><strong>2. Almacenista que recibe:</strong></label>
                        <?php
                        $stmt_nombre = $connect->prepare("SELECT CONCAT(nombre, ' ', apellido) as nombre FROM almacenista WHERE id = ?");
                        $stmt_nombre->bind_param("i", $almacenista_id);
                        $stmt_nombre->execute();
                        $nombre_almacenista = $stmt_nombre->get_result()->fetch_assoc()['nombre'];
                        ?>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($nombre_almacenista) ?>" readonly>
                    </div>
                <?php endif; ?>

                <!-- Selección del instructor que devuelve -->
                <div class="form-group mb-3">
                    <label for="instructor" class="form-label"><strong>3. Instructor que devuelve:</strong></label>
                    <select id="instructor" name="instructor" class="form-select">
                        <option value="">-- Seleccionar Instructor --</option>
                        <?php
                        $res = $connect->query("SELECT id, nombre, apellido, cedula FROM instructores WHERE estado = 'activo'");
                        while ($row = $res->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['cedula']} - {$row['nombre']} {$row['apellido']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Fecha y hora de devolución -->
                <div class="form-group mb-3">
                    <label for="fecha_devolucion" class="form-label"><strong>4. Fecha y hora de devolución:</strong></label>
                    <input type="datetime-local" class="form-control" id="fecha_devolucion" name="fecha_devolucion" required>
                </div>

                <hr>
                <h3 class="mt-4"><strong>5. Ítems a devolver</strong></h3>

                <!-- Tabla de equipos a devolver -->
                <div id="devolucion_equipo" style="display:none;" class="table-responsive mt-3">
                    <table id="tabla_equipos" class="display table table-striped table-hover" style="width:100%">
                        <thead><tr><th>Marca</th><th>Serie</th><th>Estado</th><th>Seleccionar</th><th>Condición</th><th>Observación</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Tabla de materiales a devolver -->
                <div id="devolucion_material" style="display:none;" class="table-responsive mt-3">
                    <table id="tabla_materiales" class="display table table-striped table-hover">
                       <thead>
                          <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Cant. Prestada</th>
                            <th>Cant. a Devolver</th>
                            <th>Condición</th>
                            <th>Seleccionar</th>
                            <th>Observación</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Botones de acción -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Registrar Devolución</button>
                    <a href="4devolucion.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </section>
    </main>
</div>

<!-- Carga de scripts y librerías JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Función para alternar entre tema claro y oscuro
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
        // Aplica el tema guardado en localStorage
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            document.body.classList.remove('light');
            $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
        }

        // Inicializa select2 en los select
        $('#tipo_devolucion, #select_almacenista, #instructor').select2({
            theme: "bootstrap-5",
            width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        });
        
        // Oculta mensajes de éxito/error después de 5 segundos
        setTimeout(() => {
            $('#mensaje-success, #mensaje-error').fadeOut();
        }, 5000);

        // Variables de elementos del DOM
        const tipoSelect = $('#tipo_devolucion');
        const formDevoluciones = $('#form_devoluciones');
        const devolucionEquipo = $('#devolucion_equipo');
        const devolucionMaterial = $('#devolucion_material');
        const instructorSelect = $('#instructor');
        const selectAlmacenista = $('#select_almacenista');
        const inputAlmacenistaId = $('#input_almacenista_id');

        // Actualiza el input hidden con el id del almacenista seleccionado
        selectAlmacenista.on('change', function() {
            inputAlmacenistaId.val($(this).val());
        });
        
        // Opciones comunes para DataTables
        const commonDtOptions = {
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/Spanish.json",
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros por página",
                info: "Mostrando página _PAGE_ de _PAGES_",
                infoEmpty: "No hay registros disponibles",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                paginate: { previous: "Anterior", next: "Siguiente" }
            },
            paging: true, searching: true, ordering: true
        };

        // Inicializa DataTable para equipos
        const tablaEquipos = $('#tabla_equipos').DataTable({
            ...commonDtOptions,
            columns: [
                { data: "marca" }, { data: "serie" }, { data: "estado" },
                { data: "id", orderable: false, render: (d) => `<input type="checkbox" class="form-check-input" name="equipos_seleccionados[]" value="${d}">` },
                { data: "id", orderable: false, render: (d) => `<select name="condicion_equipo_${d}" class="form-select form-select-sm" required><option value="bueno">Bueno</option><option value="deteriorado">Deteriorado</option></select>` },
                { data: "id", orderable: false, render: (d) => `<input type="text" name="observacion_equipo_${d}" class="form-control form-control-sm" placeholder="Observaciones">` }
            ]
        });

        // Inicializa DataTable para materiales
        const tablaMateriales = $('#tabla_materiales').DataTable({
            ...commonDtOptions,
            columns: [
                { data: "nombre" }, { data: "tipo" }, { data: "cantidad_prestada" },
                { data: "id", orderable: false, render: (d,t,r) => `<input type="number" name="cantidad_${d}" class="form-control form-control-sm" min="1" max="${r.cantidad_prestada}" value="${r.cantidad_prestada}" required>` },
                { data: "id", orderable: false, render: (d) => `<select name="condicion_material_${d}" class="form-select form-select-sm" required><option value="bueno">Bueno</option><option value="deteriorado">Deteriorado</option></select>` },
                { data: "id", orderable: false, render: (d) => `<input type="checkbox" class="form-check-input" name="materiales_seleccionados[]" value="${d}">` },
                { data: "id", orderable: false, render: (d) => `<input type="text" name="observacion_material_${d}" class="form-control form-control-sm" placeholder="Observaciones">` }
            ]
        });

        // Muestra/oculta formularios según el tipo de devolución seleccionado
        tipoSelect.on('change', function() {
            const tipo = $(this).val();
            formDevoluciones.toggle(tipo !== '');
            devolucionEquipo.toggle(tipo === 'equipos');
            devolucionMaterial.toggle(tipo === 'materiales');
            
            tablaEquipos.clear().draw();
            tablaMateriales.clear().draw();
            instructorSelect.val('').trigger('change');
        });

        // Al seleccionar instructor, carga los ítems prestados por AJAX
        instructorSelect.on('change', function() {
            const instructorId = $(this).val();
            const tipo = tipoSelect.val();
            if (!instructorId || !tipo) return;

            $.ajax({
                url: 'obtener_prestamos_instructor.php',
                type: 'GET',
                data: { instructor_id: instructorId, tipo: tipo },
                dataType: 'json',
                success: function(response) {
                    const tabla = (tipo === 'equipos') ? tablaEquipos : tablaMateriales;
                    tabla.clear().rows.add(response).draw();
                },
                error: (xhr) => alert("Error al cargar los préstamos del instructor.")
            });
        });

        // Validación antes de enviar el formulario
        $('#form_devoluciones').on('submit', function(e) {
            const tipo = tipoSelect.val();
            let seleccionados = (tipo === 'equipos') ? $('input[name="equipos_seleccionados[]"]:checked').length : $('input[name="materiales_seleccionados[]"]:checked').length;

            if (seleccionados === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor seleccione al menos un ítem para devolver.'
                });
                e.preventDefault();
                return false;
            }
            if ($('#select_almacenista').length && $('#select_almacenista').val() === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor seleccione un almacenista responsable.'
                });
                e.preventDefault();
                return false;
            }
            return true;
        });
        
        // Coloca la fecha y hora actual por defecto en el campo de fecha de devolución
        const now = new Date();
        const localISOTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
        $('#fecha_devolucion').val(localISOTime);
    });
</script>
</body>
</html>