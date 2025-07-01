<?php
// Inicia la sesión y conecta con la base de datos
session_start();
include '../conexion.php';

// --- VALIDACIÓN DE SESIÓN Y ROL ---
if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit;
}
$allowed_roles = ['administrador', 'almacenista'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: ../acceso_denegado.php');
    exit;
}

// --- LÓGICA DE FILTROS ---
$tipo_vista = $_GET['tipo'] ?? 'prestamos_equipos';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$estado_prestamo = isset($_GET['estado_prestamo']) ? $_GET['estado_prestamo'] : 'todos';
$estado_devolucion = ($tipo_vista === 'reportes') ? ($_GET['estado_devolucion'] ?? '') : '';


// --- LÓGICA PARA EL SIDEBAR ---
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$user_rol = ucfirst($_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos y enlaces a estilos y scripts externos -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Listados y Reportes</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/listar_prestamos.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    

</head>
<body class="light">

<div class="container-fluid">
    <!-- Sidebar lateral con menú y datos de usuario -->
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
                 <li class="active"><a href="listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listados</span></a></li>
                 <li><a href="../devoluciones/4devolucion.php"><i class="icon fas fa-undo"></i><span class="text">Devoluciones</span></a></li>
                 <li><a href="../insumos/2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
                 <li><a href="../reportes/reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes</span></a></li>
                 <li><a href="../logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
             </ul>
        </div>
    </div>

    <main class="main">
        <!-- Botón para cambiar tema -->
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        
        <header><h1><i class="fas fa-list-alt"></i> Listados y Reportes</h1></header>
        <hr>
        
        <!-- Carrusel de opciones para cambiar entre tipos de listados -->
        <section class="mb-4">
            <div id="opcionesCarrusel" class="carousel slide" data-bs-ride="false">
                <div class="carousel-inner">
                    <!-- Opción: Préstamos de Equipos -->
                    <div class="carousel-item" data-tipo="prestamos_equipos"><div class="option-card" id="cardprestamos_equipos"><i class="fas fa-laptop"></i><h3>Préstamos de Equipos</h3></div></div>
                    <!-- Opción: Préstamos de Materiales -->
                    <div class="carousel-item" data-tipo="prestamos_materiales"><div class="option-card" id="cardprestamos_materiales"><i class="fas fa-box-open"></i><h3>Préstamos de Materiales</h3></div></div>
                    <!-- Opción: Reportes Devolución -->
                    <div class="carousel-item" data-tipo="reportes"><div class="option-card" id="cardreportes"><i class="fas fa-file-invoice"></i><h3>Reportes Devolución</h3></div></div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#opcionesCarrusel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#opcionesCarrusel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span></button>
            </div>
        </section>

        <div id="content-container">
            <!-- Sección de préstamos de equipos -->
            <div id="content_prestamos_equipos" class="content-section d-none">
                <section class="card-form p-4 rounded mb-4">
                    <h2>Filtrar Préstamos de Equipos</h2>
                    <form method="get" class="row g-3">
                        <input type="hidden" name="tipo" value="prestamos_equipos">
                        <div class="col-md-4"><label for="fecha_desde_pr_eq" class="form-label">Fecha desde:</label><input type="date" id="fecha_desde_pr_eq" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>"></div>
                        <div class="col-md-4"><label for="fecha_hasta_pr_eq" class="form-label">Fecha hasta:</label><input type="date" id="fecha_hasta_pr_eq" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"></div>
                        <div class="col-md-4"><label for="estado_prestamo_eq" class="form-label">Estado:</label><select id="estado_prestamo_eq" class="form-select" name="estado_prestamo"><option value="todos" <?= $estado_prestamo == 'todos' ? 'selected' : '' ?>>Todos</option><option value="pendiente" <?= $estado_prestamo == 'pendiente' ? 'selected' : '' ?>>Pendiente</option><option value="devuelto" <?= $estado_prestamo == 'devuelto' ? 'selected' : '' ?>>Devuelto</option></select></div>
                        <div class="col-12 mt-3"><button type="submit" class="btn btn-primary">Filtrar Préstamos</button><a href="listar_prestamos.php?tipo=prestamos_equipos" class="btn btn-secondary ms-2">Limpiar</a></div>
                    </form>
                </section>
                <section class="card-form p-4 rounded"><div class="table-responsive"><table id="tablaPrestamosEquipos" class="display table table-striped table-hover" style="width:100%;">
                    <thead><tr><th>ID</th><th>Equipo</th><th>Instructor</th><th>F. Préstamo</th><th>F. Devolución</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php
                            $query_equipos = "SELECT pe.id, e.marca, e.serie, i.nombre as instructor, pe.fecha_prestamo, pe.fecha_devolucion, (SELECT 1 FROM devolucion_equipos de WHERE de.prestamo_equipo_id = pe.id LIMIT 1) as devuelto FROM prestamos_equipos pe JOIN equipos e ON pe.equipo_id = e.id JOIN instructores i ON pe.instructor_id = i.id";
                            $result_equipos = $connect->query($query_equipos);
                            if ($result_equipos && $result_equipos->num_rows > 0) {
                                while ($row = $result_equipos->fetch_assoc()) {
                                    $estado_actual = $row['devuelto'] ? 'Devuelto' : 'Pendiente';
                                    $estado_color = $row['devuelto'] ? 'success' : 'warning';
                                    $disabled = $row['devuelto'] ? 'disabled' : '';
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['id']) . "</td>
                                        <td>" . htmlspecialchars($row['marca'] . ' - ' . $row['serie']) . "</td>
                                        <td>" . htmlspecialchars($row['instructor']) . "</td>
                                        <td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_prestamo']))) . "</td>
                                        <td>" . ($row['fecha_devolucion'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_devolucion']))) : '--') . "</td>
                                        <td><span class='badge bg-{$estado_color}'>{$estado_actual}</span></td>
                                        <td>
                                            <a href='editar_prestamo.php?id=" . $row['id'] . "&tipo=equipo' class='btn btn-sm btn-primary' $disabled><i class='fas fa-edit'></i> Editar</a>
                                            <button onclick='confirmarEliminacion(" . $row['id'] . ", \"equipo\")' class='btn btn-sm btn-danger' $disabled><i class='fas fa-trash'></i> Eliminar</button>
                                        </td>
                                    </tr>";
                                }
                            }
                        ?>
                    </tbody>
                </table></div></section>
            </div>
            
            <!-- Sección de préstamos de materiales -->
            <div id="content_prestamos_materiales" class="content-section d-none">
                 <section class="card-form p-4 rounded mb-4">
                    <h2>Filtrar Préstamos de Materiales</h2>
                    <form method="get" class="row g-3">
                        <input type="hidden" name="tipo" value="prestamos_materiales">
                         <div class="col-md-4"><label for="fecha_desde_pr_mat" class="form-label">Fecha desde:</label><input type="date" id="fecha_desde_pr_mat" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>"></div>
                        <div class="col-md-4"><label for="fecha_hasta_pr_mat" class="form-label">Fecha hasta:</label><input type="date" id="fecha_hasta_pr_mat" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"></div>
                        <div class="col-md-4"><label for="estado_prestamo_mat" class="form-label">Estado:</label><select id="estado_prestamo_mat" class="form-select" name="estado_prestamo"><option value="todos" <?= $estado_prestamo == 'todos' ? 'selected' : '' ?>>Todos</option><option value="pendiente" <?= $estado_prestamo == 'pendiente' ? 'selected' : '' ?>>Pendiente</option><option value="devuelto" <?= $estado_prestamo == 'devuelto' ? 'selected' : '' ?>>Devuelto</option></select></div>
                        <div class="col-12 mt-3"><button type="submit" class="btn btn-primary">Filtrar Préstamos</button><a href="listar_prestamos.php?tipo=prestamos_materiales" class="btn btn-secondary ms-2">Limpiar</a></div>
                    </form>
                </section>
                <section class="card-form p-4 rounded"><div class="table-responsive"><table id="tablaPrestamosMateriales" class="display table table-striped table-hover" style="width:100%;">
                    <thead><tr><th>ID</th><th>Material</th><th>Tipo</th><th>Instructor</th><th>Cant.</th><th>F. Préstamo</th><th>F. Devolución</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php
                        $query_materiales = "
                            SELECT 
                                pm.id, 
                                m.nombre, 
                                m.tipo,
                                i.nombre as instructor, 
                                pm.cantidad, 
                                pm.fecha_prestamo, 
                                pm.fecha_devolucion,
                                (SELECT 1 FROM devolucion_materiales dm WHERE dm.prestamo_material_id = pm.id LIMIT 1) as devuelto
                            FROM prestamo_materiales pm
                            JOIN materiales m ON pm.material_id = m.id
                            JOIN instructores i ON pm.instructor_id = i.id
                        ";
                        
                        $result_materiales = $connect->query($query_materiales);
                        if ($result_materiales && $result_materiales->num_rows > 0) {
                            while ($row = $result_materiales->fetch_assoc()) {
                                $estado_actual = $row['devuelto'] ? 'Devuelto' : 'Pendiente';
                                $estado_color = $row['devuelto'] ? 'success' : 'warning';
                                $disabled = $row['devuelto'] ? 'disabled' : '';
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['id']) . "</td>
                                    <td>" . htmlspecialchars($row['nombre']) . "</td>
                                    <td>" . htmlspecialchars($row['tipo']) . "</td>
                                    <td>" . htmlspecialchars($row['instructor']) . "</td>
                                    <td>" . htmlspecialchars($row['cantidad']) . "</td>
                                    <td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_prestamo']))) . "</td>
                                    <td>" . ($row['fecha_devolucion'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_devolucion']))) : '--') . "</td>
                                    <td><span class='badge bg-{$estado_color}'>{$estado_actual}</span></td>
                                    <td>
                                        <a href='editar_prestamo.php?id=" . $row['id'] . "&tipo=material' class='btn btn-sm btn-primary' $disabled><i class='fas fa-edit'></i> Editar</a>
                                        <button onclick='confirmarEliminacion(" . $row['id'] . ", \"material\")' class='btn btn-sm btn-danger' $disabled><i class='fas fa-trash'></i> Eliminar</button>
                                    </td>
                                </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table></div></section>
            </div>

            <!-- Sección de reportes de devoluciones -->
            <div id="content_reportes" class="content-section d-none">
                <section class="card-form p-4 rounded mb-4">
                    <h2>Filtrar Reportes de Devoluciones</h2>
                    <form method="get" class="row g-3">
                        <input type="hidden" name="tipo" value="reportes">
                        <div class="col-md-4"><label for="fecha_desde_rep" class="form-label">Fecha desde:</label><input type="date" id="fecha_desde_rep" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>"></div>
                        <div class="col-md-4"><label for="fecha_hasta_rep" class="form-label">Fecha hasta:</label><input type="date" id="fecha_hasta_rep" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"></div>
                        <div class="col-md-4"><label for="estado_devolucion" class="form-label">Condición:</label><select id="estado_devolucion" class="form-select" name="estado_devolucion"><option value="">Todos</option><option value="bueno" <?= ($estado_devolucion == 'bueno') ? 'selected' : '' ?>>Bueno</option><option value="deteriorado" <?= ($estado_devolucion == 'deteriorado') ? 'selected' : '' ?>>Deteriorado</option></select></div>
                        <div class="col-12 mt-3"><button type="submit" class="btn btn-primary">Filtrar Reportes</button><a href="listar_prestamos.php?tipo=reportes" class="btn btn-secondary ms-2">Limpiar</a></div>
                    </form>
                </section>
                <section class="card-form p-4 rounded">
                    <h2 class="mb-3">Devoluciones de Equipos</h2>
                    <div class="table-responsive"><table id="tablaReporteEquipos" class="display table table-striped table-hover" style="width:100%;">
                        <thead><tr><th>ID Dev.</th><th>Equipo</th><th>Instructor</th><th>Almacenista</th><th>F. Préstamo</th><th>F. Devolución</th><th>Condición</th><th>Obs.</th></tr></thead>
                        <tbody>
                            <?php
                            $query_reporte_equipos = "
                                SELECT 
                                    de.id,
                                    e.marca, 
                                    e.serie,
                                    i.nombre as instructor,
                                    a.nombre as almacenista,
                                    pe.fecha_prestamo,
                                    de.fecha_devolucion,
                                    de.estado_devolucion,
                                    de.observaciones
                                FROM devolucion_equipos de
                                JOIN prestamos_equipos pe ON de.prestamo_equipo_id = pe.id
                                JOIN equipos e ON pe.equipo_id = e.id
                                JOIN instructores i ON pe.instructor_id = i.id
                                JOIN almacenista a ON pe.almacenista_id = a.id
                            ";
                            $result_rep_eq = $connect->query($query_reporte_equipos);
                            if ($result_rep_eq && $result_rep_eq->num_rows > 0) {
                                while ($row = $result_rep_eq->fetch_assoc()) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['id']) . "</td>
                                        <td>" . htmlspecialchars($row['marca'] . ' - ' . $row['serie']) . "</td>
                                        <td>" . htmlspecialchars($row['instructor']) . "</td>
                                        <td>" . htmlspecialchars($row['almacenista']) . "</td>
                                        <td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_prestamo']))) . "</td>
                                        <td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_devolucion']))) . "</td>
                                        <td><span class='badge bg-info'>" . htmlspecialchars(ucfirst($row['estado_devolucion'])) . "</span></td>
                                        <td>" . htmlspecialchars($row['observaciones']) . "</td>
                                    </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table></div>
                    <hr class="my-4">
                    <h2 class="mb-3">Devoluciones de Materiales</h2>
                    <div class="table-responsive"><table id="tablaReporteMateriales" class="display table table-striped table-hover" style="width:100%;">
                        <thead><tr><th>ID Dev.</th><th>Material</th><th>Tipo</th><th>Instructor</th><th>Almacenista</th><th>Cant.</th><th>F. Préstamo</th><th>F. Devolución</th><th>Condición</th><th>Obs.</th></tr></thead>
                        <tbody>
                             <?php
                             $query_reporte_materiales = "
                                SELECT 
                                    dm.id,
                                    m.nombre,
                                    m.tipo,
                                    i.nombre as instructor,
                                    a.nombre as almacenista,
                                    dm.cantidad,
                                    pm.fecha_prestamo,
                                    dm.fecha_devolucion,
                                    dm.condicion_entrega,
                                    dm.observaciones
                                FROM devolucion_materiales dm
                                JOIN prestamo_materiales pm ON dm.prestamo_material_id = pm.id
                                JOIN materiales m ON pm.material_id = m.id
                                JOIN instructores i ON pm.instructor_id = i.id
                                JOIN almacenista a ON dm.almacenista_id = a.id
                            ";
                            $result_rep_mat = $connect->query($query_reporte_materiales);
                             if ($result_rep_mat && $result_rep_mat->num_rows > 0) {
                                while ($row = $result_rep_mat->fetch_assoc()) {
                                     echo "<tr>
                                        <td>" . htmlspecialchars($row['id']) . "</td>
                                        <td>" . htmlspecialchars($row['nombre']) . "</td>
                                        <td>" . htmlspecialchars($row['tipo']) . "</td>
                                        <td>" . htmlspecialchars($row['instructor']) . "</td>
                                        <td>" . htmlspecialchars($row['almacenista']) . "</td>
                                        <td>" . htmlspecialchars($row['cantidad']) . "</td>
                                        <td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_prestamo']))) . "</td>
                                        <td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_devolucion']))) . "</td>
                                        <td><span class='badge bg-info'>" . htmlspecialchars(ucfirst($row['condicion_entrega'])) . "</span></td>
                                        <td>" . htmlspecialchars($row['observaciones']) . "</td>
                                    </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table></div>
                </section>
            </div>
        </div>
        
    </main>
</div>

<!-- Scripts de librerías y lógica JS para la página -->
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
    // Cambia el tema claro/oscuro y guarda preferencia en localStorage
    function toggleTheme() {
        document.body.classList.toggle('dark');
        document.body.classList.toggle('light');
        if(document.body.classList.contains('dark')) { localStorage.setItem('theme', 'dark'); } 
        else { localStorage.setItem('theme', 'light'); }
        const icon = document.getElementById('theme-icon');
        icon.classList.toggle('fa-moon');
        icon.classList.toggle('fa-sun');
    }

    // Función para confirmar eliminación con SweetAlert2
    function confirmarEliminacion(id, tipo) {
        Swal.fire({
            title: '¿Eliminar préstamo?',
            text: '¡Esta acción no se puede deshacer!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `eliminar_prestamo.php?id=${id}&tipo=${tipo}`;
            }
        });
    }

    $(document).ready(function() {
        // Aplica el tema guardado en localStorage
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            document.body.classList.remove('light');
            $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
        }

        // Inicializa select2 en los selects
        $('.form-select').select2({
            theme: "bootstrap-5"
        });
        
        // Opciones comunes para DataTables con botones de exportar
        const commonDtOptions = {
            dom: 'lBfrtip',
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Exportar a Excel', className: 'btn-success' },
                { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> Exportar a PDF', className: 'btn-danger' }
            ],
            lengthMenu: [10, 25, 50],
            pageLength: 10,
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
        };
        
        // Inicializa DataTables para todas las tablas
        $('#tablaPrestamosEquipos, #tablaPrestamosMateriales, #tablaReporteEquipos, #tablaReporteMateriales').DataTable(commonDtOptions);

        // Lógica para el carrusel de opciones
        const carruselElement = document.getElementById('opcionesCarrusel');
        const carrusel = new bootstrap.Carousel(carruselElement, { interval: false });

        // Selecciona la opción y muestra la sección correspondiente
        function seleccionarOpcion(tipo) {
            $('.option-card').removeClass('active');
            $('.content-section').addClass('d-none');
            
            if (tipo) {
                $('#card' + tipo).addClass('active');
                $('#content_' + tipo).removeClass('d-none');
            }
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
        
        // Cambia la sección al cambiar el slide del carrusel
        carruselElement.addEventListener('slid.bs.carousel', function (event) {
            const tipo = event.relatedTarget.getAttribute('data-tipo');
            const url = new URL(window.location);
            url.searchParams.set('tipo', tipo);
            window.history.pushState({}, '', url);
            seleccionarOpcion(tipo);
        });

        // Permite seleccionar la opción haciendo click en la tarjeta
        $('.carousel-item').on('click', function() {
            carrusel.to($(this).index());
        });

        // Selecciona la opción inicial según el parámetro de la URL
        const urlParams = new URLSearchParams(window.location.search);
        const tipoInicial = urlParams.get('tipo') || 'prestamos_equipos';
        
        let slideInicial = 0;
        if (tipoInicial === 'prestamos_materiales') slideInicial = 1;
        if (tipoInicial === 'reportes') slideInicial = 2;

        carruselElement.querySelector('.carousel-item.active')?.classList.remove('active');
        carruselElement.querySelectorAll('.carousel-item')[slideInicial].classList.add('active');
        seleccionarOpcion(tipoInicial);
        
    });
</script>
</body>
</html>