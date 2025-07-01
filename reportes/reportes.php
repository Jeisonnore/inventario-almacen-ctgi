<?php
session_start();
include '../conexion.php';

// Validar que solo los administradores puedan acceder
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    // Redirigir según el rol
    $inicio_url = isset($_SESSION['rol']) && $_SESSION['rol'] === 'almacenista' ? "../almacenista.php" : "../login.php";
    header('Location: ' . $inicio_url);
    exit;
}

date_default_timezone_set("America/Bogota");

// Obtener parámetros de filtrado desde la URL (GET)
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$almacenista_id = $_GET['almacenista_id'] ?? '';

// Construir consulta base para el reporte de horas
$sql = "SELECT 
            rh.id, 
            a.nombre, 
            a.apellido, 
            rh.fecha, 
            TIME(rh.hora_ingreso) as hora_ingreso_real,
            TIME(rh.hora_salida) as hora_salida_real,
            TIMESTAMPDIFF(MINUTE, rh.hora_ingreso, rh.hora_salida) as minutos_conectado
        FROM registro_horas rh
        INNER JOIN almacenista a ON rh.almacenista_id = a.id";

// Construir condiciones dinámicamente según los filtros
$where = [];
$params = [];
$types = '';
if (!empty($fecha_inicio)) {
    $where[] = "rh.fecha >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}
if (!empty($fecha_fin)) {
    $where[] = "rh.fecha <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}
if (!empty($almacenista_id)) {
    $where[] = "rh.almacenista_id = ?";
    $params[] = $almacenista_id;
    $types .= 'i';
}
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY rh.fecha DESC, TIME(rh.hora_ingreso) DESC";

// Preparar y ejecutar la consulta
$stmt = $connect->prepare($sql);
if (!$stmt) { die("Error en la consulta: {$connect->error}"); }
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$resultado = $stmt->get_result();

// Obtener lista de almacenistas para el filtro
$sql_almacenistas = "SELECT id, nombre, apellido FROM almacenista ORDER BY nombre";
$almacenistas = $connect->query($sql_almacenistas);
if (!$almacenistas) { die("Error al obtener almacenistas: {$connect->error}"); }

// Obtener datos del usuario actual
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$user_rol = ucfirst($_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Horas</title>
    
    <!-- Cargar estilos y librerías externas -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/reportes.css">
</head>
<body class="light">

<div class="container-fluid">
    <!-- Barra lateral de navegación -->
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
                <!-- Menú de navegación -->
                <li><a href="<?php echo ($_SESSION['rol'] === 'administrador') ? '../admin.php' : '../almacenista.php'; ?>"><i class="icon fas fa-home"></i><span class="text">Inicio</span></a></li>
                <li><a href="../funcionarios/1registrofuncionario.php"><i class="icon fas fa-user-plus"></i><span class="text">Funcionarios</span></a></li>
                <li><a href="../prestamo1/prestamos.php"><i class="icon fas fa-hand-holding-hand"></i><span class="text">Préstamos</span></a></li>
                <li><a href="../lista_prestamos/listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listados</span></a></li>
                <li><a href="../devoluciones/4devolucion.php"><i class="icon fas fa-undo"></i><span class="text">Devoluciones</span></a></li>
                <li><a href="../insumos/2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
                <li class="active"><a href="reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes</span></a></li>
                <li><a href="../logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <!-- Botón para cambiar tema claro/oscuro -->
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        
        <header><h1><i class="fas fa-clock"></i> Reporte de Horas de Conexión</h1></header>
        <hr>

        <!-- Formulario de filtros -->
        <section class="card-form p-4 rounded mb-4">
            <h2 class="mb-3"><i class="fas fa-filter"></i> Filtros de Búsqueda</h2>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-md-4">
                    <label for="almacenista_id" class="form-label">Almacenista</label>
                    <select class="form-select" id="almacenista_id" name="almacenista_id">
                        <option value="">Todos los almacenistas</option>
                        <?php mysqli_data_seek($almacenistas, 0); ?>
                        <?php while ($alm = $almacenistas->fetch_assoc()) : ?>
                            <option value="<?= $alm['id'] ?>" <?= ($alm['id'] == $almacenista_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($alm['nombre'] . ' ' . $alm['apellido']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="reportes.php" class="btn btn-secondary ms-2"><i class="fas fa-sync-alt"></i> Limpiar</a>
                </div>
            </form>
        </section>

        <!-- Tabla de resultados -->
        <section class="card-form p-4 rounded">
            <div class="table-responsive">
                <table id="tablaHoras" class="display table table-striped table-hover" style="width:100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Almacenista</th>
                            <th>Fecha</th>
                            <th>Hora Ingreso</th>
                            <th>Hora Salida</th>
                            <th>Tiempo Conectado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fila = $resultado->fetch_assoc()) { 
                            // Formatear horas de ingreso y salida
                            $hora_ingreso = date("h:i A", strtotime($fila['hora_ingreso_real']));
                            $hora_salida = $fila['hora_salida_real'] ? date("h:i A", strtotime($fila['hora_salida_real'])) : '—';
                            
                            // Calcular tiempo conectado en horas y minutos
                            $tiempo_conectado = '—';
                            if ($fila['minutos_conectado'] !== null) {
                                $horas = floor($fila['minutos_conectado'] / 60);
                                $minutos = $fila['minutos_conectado'] % 60;
                                $tiempo_conectado = "$horas h $minutos min";
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($fila['id']) ?></td>
                                <td><?= htmlspecialchars($fila['nombre'] . ' ' . $fila['apellido']) ?></td>
                                <td><?= htmlspecialchars($fila['fecha']) ?></td>
                                <td><?= $hora_ingreso ?></td>
                                <td><?= $hora_salida ?></td>
                                <td><?= $tiempo_conectado ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- Scripts de librerías externas y configuración de DataTables y Select2 -->
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
        // Aplicar tema guardado en localStorage
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            document.body.classList.remove('light');
            $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
        }

        // Inicializar Select2 para el filtro de almacenistas
        $('#almacenista_id').select2({
            theme: "bootstrap-5",
            placeholder: "Todos los almacenistas"
        });
        
        // Inicializar DataTable con botones de exportación y traducción al español
        $('#tablaHoras').DataTable({
            dom: 'lBfrtip',
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Exportar a Excel', className: 'btn btn-success', title: 'Reporte_Horas_<?= date("Y-m-d") ?>' },
                { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn-danger', title: 'Reporte_Horas_<?= date("Y-m-d") ?>' }
            ],
            lengthMenu: [10, 25, 50],
            pageLength: 10,
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/Spanish.json",
                info: "Mostrando _START_ a _END_ de _TOTAL_ materiales",
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
        });
    });
</script>
</body>
</html>
<?php
// Cerrar conexiones y liberar recursos
$stmt->close();
$almacenistas->close();
$connect->close();
?>