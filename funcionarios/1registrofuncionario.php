<?php
session_start();
include("../conexion.php");

// Verificar sesión y rol
if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    header('Location: ../login.php');
    exit;
}

// Obtener parámetros de filtro
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';

// Construir la consulta SQL con filtros
$sql = "SELECT i.*, a.nombre AS nombre_almacenista, a.apellido AS apellido_almacenista 
        FROM instructores i
        LEFT JOIN almacenista a ON i.almacenista_id = a.id 
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($fecha_desde)) {
    $sql .= " AND DATE(i.fecha_ingreso) >= ?";
    $types .= 's';
    $params[] = $fecha_desde;
}
if (!empty($fecha_hasta)) {
    $sql .= " AND DATE(i.fecha_ingreso) <= ?";
    $types .= 's';
    $params[] = $fecha_hasta;
}
if ($estado != 'todos') {
    $sql .= " AND i.estado = ?";
    $types .= 's';
    $params[] = $estado;
}
$sql .= " ORDER BY i.id DESC";
$stmt = $connect->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Para la información del usuario en el sidebar
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$user_rol = ucfirst($_SESSION['rol']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Instructores</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/funcionarios.css">
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
                <li class="active"><a href="1registrofuncionario.php"><i class="icon fas fa-user-plus"></i><span class="text">Funcionarios</span></a></li>
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
        
        <header><h1><i class="fas fa-user-plus"></i> Registro de Instructores</h1></header>
        <hr>

        <?php if (isset($_GET['var'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: <?= json_encode($_GET['var']) ?>,
                        confirmButtonText: 'Entendido'
                    });
                });
            </script>
        <?php endif; ?>
        <?php if (isset($_SESSION['mensaje'])): ?>
            <?php
            $tipo = $_SESSION['tipo_mensaje'] ?? 'info';
            $mensaje = $_SESSION['mensaje'];
            unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: <?= json_encode($tipo === "success" ? "success" : ($tipo === "error" ? "error" : "info")) ?>,
                        title: <?= $tipo === "success" ? "'¡Éxito!'" : ($tipo === "error" ? "'Error'" : "'Aviso'") ?>,
                        text: <?= json_encode($mensaje) ?>,
                        confirmButtonText: 'Entendido'
                    });
                });
            </script>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <section class="card-form h-100 p-4 rounded">
                    <h2>Registrar Nuevo Instructor</h2>
                    <p id="mensajeError" style="color: red; font-weight: bold;"></p>
                    <form id="miformulario" action="insertar.php" method="post" onsubmit="return validarFormulario();" class="row g-3">
                        <div class="col-md-6"><label for="nombre" class="form-label">Nombre:</label><input type="text" id="nombre" class="form-control" name="nombre" required placeholder="Nombre"></div>
                        <div class="col-md-6"><label for="apellido" class="form-label">Apellido:</label><input type="text" id="apellido" class="form-control" name="apellido" required placeholder="Apellido"></div>
                        <div class="col-md-6"><label for="cedula" class="form-label">Cédula:</label><input type="text" id="cedula" class="form-control" name="cedula" required placeholder="Cédula"></div>
                        <div class="col-md-6"><label for="correo" class="form-label">Correo:</label><input type="email" id="correo" class="form-control" name="correo" required placeholder="Correo"></div>
                        <div class="col-md-6"><label for="telefono" class="form-label">Teléfono:</label><input type="text" id="telefono" class="form-control" name="telefono" required placeholder="Teléfono"></div>
                        <?php if ($_SESSION['rol'] === 'administrador'): ?>
                        <div class="col-md-6">
                            <label for="almacenista_id" class="form-label">Asignar a almacenista:</label>
                            <select class="form-select" name="almacenista_id" required>
                                <?php 
                                $query_alm = "SELECT a.id, a.nombre, a.apellido FROM almacenista a JOIN usuario u ON a.usuario_id = u.id WHERE a.estado = 'activo'";
                                $almacenistas = mysqli_query($connect, $query_alm);
                                while ($alm = mysqli_fetch_assoc($almacenistas)): 
                                ?>
                                    <option value="<?= $alm['id'] ?>"><?= htmlspecialchars($alm['nombre'] . ' ' . $alm['apellido']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6"><label for="estado_reg" class="form-label">Estado:</label><select id="estado_reg" class="form-select" name="estado" required><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                        <div class="col-12"><button type="submit" class="btn btn-success">Registrar</button></div>
                    </form>
                </section>
            </div>
            <div class="col-md-6">
                 <section class="card-form h-100 p-4 rounded">
                    <h2>Filtrar Instructores</h2>
                    <form method="get" class="row g-3">
                        <div class="col-md-6"><label for="fecha_desde" class="form-label">Fecha desde:</label><input type="date" id="fecha_desde" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>"></div>
                        <div class="col-md-6"><label for="fecha_hasta" class="form-label">Fecha hasta:</label><input type="date" id="fecha_hasta" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"></div>
                        <div class="col-12"><label for="estado_filtro" class="form-label">Estado:</label><select id="estado_filtro" class="form-select" name="estado"><option value="todos" <?= $estado == 'todos' ? 'selected' : '' ?>>Todos</option><option value="activo" <?= $estado == 'activo' ? 'selected' : '' ?>>Activo</option><option value="inactivo" <?= $estado == 'inactivo' ? 'selected' : '' ?>>Inactivo</option></select></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary">Filtrar</button><a href="1registrofuncionario.php" class="btn btn-secondary ms-2">Limpiar</a></div>
                    </form>
                </section>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <section class="card-form p-4 rounded">
                    <h2>Listado de Instructores</h2>
                    <div class="table-responsive">
                        <table id="miTabla1" class="display table table-striped table-hover" style="width:100%;">
                            <thead><tr><th>ID</th><th>Nombre</th><th>Apellidos</th><th>Cédula</th><th>Correo</th><th>Teléfono</th><th>Estado</th><th>Registro</th><th>Asignado a</th><th>Acciones</th></tr></thead>
                            <tbody>
                                <?php mysqli_data_seek($result, 0); // Reiniciar puntero de resultados ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td><?= htmlspecialchars($row['apellido']) ?></td>
                                        <td><?= htmlspecialchars($row['cedula']) ?></td>
                                        <td><?= htmlspecialchars($row['correo']) ?></td>
                                        <td><?= htmlspecialchars($row['telefono']) ?></td>
                                        <td><span class="badge bg-<?= $row['estado'] == 'activo' ? 'success' : 'danger' ?>"><?= htmlspecialchars($row['estado']) ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($row['fecha_ingreso'])) ?></td>
                                        <td><?= htmlspecialchars($row['nombre_almacenista'] . ' ' . $row['apellido_almacenista']) ?></td>
                                        <td class="text-center"><a href="editar2.php?id=<?= urlencode($row['id']) ?>" class="btn btn-success btn-sm">Editar</a><a href="eliminar.php?id=<?= urlencode($row['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro?');">Eliminar</a></td>
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

        // Inicializar Select2 en todos los selectores de la página
        $('.form-select').select2({
            theme: "bootstrap-5"
        });
        
        $('#miTabla1').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', title: 'Listado de Instructores', className: 'btn btn-success', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] } },
                { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', title: 'Listado de Instructores', className: 'btn btn-danger', orientation: 'landscape', pageSize: 'A4', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] } }
            ],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            columnDefs: [ { orderable: false, targets: [9] } ]
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