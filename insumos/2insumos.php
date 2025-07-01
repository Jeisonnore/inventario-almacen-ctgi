<?php
// Conexión y sesión
include("../conexion.php");
session_start();

// --- VALIDACIÓN DE SESIÓN Y ROL (Añadido para seguridad) ---
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos y enlaces a estilos y scripts externos -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Insumos</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/insumos.css">
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
                <li><a href="../lista_prestamos/listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listados</span></a></li>
                <li><a href="../devoluciones/4devolucion.php"><i class="icon fas fa-undo"></i><span class="text">Devoluciones</span></a></li>
                <li class="active"><a href="2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
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
        
        <header><h1><i class="fas fa-box-open"></i> Registro y Gestión de Insumos</h1></header>
        <hr>

        <!-- Mensaje de éxito con SweetAlert2 si existe el parámetro 'var' -->
        <?php if (isset($_GET['var'])) : ?>
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

        <!-- Mensaje de error con SweetAlert2 si existe el parámetro 'error' -->
        <?php if (isset($_GET['error'])) : ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error', // Esto muestra una X roja en lugar de un chulo
                        title: 'Error al registrar',
                        text: <?= json_encode($_GET['error']) ?>,
                        confirmButtonText: 'Entendido'
                    });
                });
            </script>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12">
                <!-- Formulario para registrar equipos o materiales -->
                <section class="card-form p-4 rounded mb-4">
                    <h2>Registrar Nuevo Ítem</h2>
                    <div class="mb-3" style="max-width: 500px;">
                        <label for="tipo_registro" class="form-label">Seleccione el tipo:</label>
                        <select id="tipo_registro" class="form-select">
                            <option value="">-- Seleccionar --</option>
                            <option value="equipos">Equipos</option>
                            <option value="materiales">Materiales</option>
                        </select>
                    </div>
                    <!-- Formulario de equipos -->
                    <div id="form_equipos" class="d-none mt-3">
                        <h3>Registro de Equipos</h3>
                        <form id="miformulario1" action="registro_equipos.php" onsubmit="return validarFormularioEquipos();" method="POST" class="row g-3">
                            <div class="col-md-6"><label for="marca" class="form-label">Marca:</label><input type="text" id="marca" class="form-control" name="marca" required placeholder="Marca"></div>
                            <div class="col-md-6"><label for="serie" class="form-label">Serie:</label><input type="text" id="serie" class="form-control" name="serie" required placeholder="Serie"></div>
                            <div class="col-md-6"><label class="form-label">Estado:</label><select class="form-select" name="estado" required><option value="">-- Seleccionar --</option><option value="disponible">Disponible</option><option value="deteriorado">Deteriorado</option></select></div>
                            <div class="col-12"><button type="submit" class="btn btn-primary">Registrar Equipo</button></div>
                        </form>
                    </div>

                    <!-- Formulario de materiales -->
                    <div id="form_materiales" class="d-none mt-3">
                        <h3>Registro de Materiales</h3>
                        <form id="miformulario2" action="registro_materiales.php" onsubmit="return validarFormularioMateriales();" method="POST" class="row g-3">
                            <div class="col-md-6"><label for="nombre_mat" class="form-label">Nombre:</label><input type="text" id="nombre_mat" class="form-control" name="nombre" required placeholder="Nombre"></div>
                            <div class="col-md-6"><label for="serie_mat" class="form-label">Serie:</label><input type="text" id="serie_mat" class="form-control" name="serie" required placeholder="Serie"></div>
                            <div class="col-md-6"><label class="form-label">Tipo:</label><select class="form-select" name="tipo" required onchange="actualizarCampoCantidad()"><option value="">-- Seleccionar --</option><option value="consumible">Consumible</option><option value="no consumible">No Consumible</option></select></div>
                            <div class="col-md-6"><label class="form-label">Cantidad:</label><input type="number" id="cantidad" class="form-control" name="cantidad" placeholder="Cantidad" required min="1" value="1"></div>
                            <div class="col-12"><button type="submit" class="btn btn-primary">Registrar Material</button></div>
                        </form>
                    </div>
                </section>
            </div>
        </div>

        <div class="row">
            <!-- Tabla de equipos -->
            <div class="col-md-6">
                <section class="card-form p-4 rounded mb-4">
                    <h2>Lista de Equipos</h2>
                    <div class="table-responsive"><table id="tablaEquipos" class="display table table-striped table-hover" style="width:100%;">
                        <thead><tr><th>ID</th><th>Marca</th><th>Serie</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php
                            // Consulta y muestra los equipos registrados
                            $sql_eq = $connect->query("SELECT * FROM equipos ORDER BY id DESC");
                            foreach ($sql_eq as $fila) {
                                echo "<tr>
                                    <td>{$fila['id']}</td>
                                    <td>" . htmlspecialchars($fila['marca']) . "</td>
                                    <td>" . htmlspecialchars($fila['serie']) . "</td>
                                    <td><span class='badge bg-" . ($fila['estado'] == 'disponible' ? 'success' : 'danger') . "'>" . htmlspecialchars($fila['estado']) . "</span></td>
                                    <td class='text-center'>
                                        <a href='editar_equipos.php?id={$fila['id']}' class='btn btn-success btn-sm'>Editar</a>
                                        <a href='eliminar.php?id={$fila['id']}&tipo=equipo' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro?\");'>Eliminar</a>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table></div>
                </section>
            </div>
            <!-- Tabla de materiales -->
            <div class="col-md-6">
                <section class="card-form p-4 rounded">
                    <h2>Lista de Materiales</h2>
                    <div class="table-responsive"><table id="tablaMateriales" class="display table table-striped table-hover" style="width:100%;">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Cantidad</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php
                            // Consulta y muestra los materiales registrados
                            $sql_mat = $connect->query("SELECT * FROM materiales ORDER BY id DESC");
                            foreach ($sql_mat as $fila) {
                                $estado_color = ['disponible' => 'success', 'prestado' => 'warning', 'deteriorado' => 'danger'][$fila['estado']] ?? 'secondary';
                                echo "<tr>
                                    <td>{$fila['id']}</td>
                                    <td>" . htmlspecialchars($fila['nombre']) . "</td>
                                    <td>" . htmlspecialchars($fila['tipo']) . "</td>
                                    <td>{$fila['cantidad']}</td>
                                    <td><span class='badge bg-{$estado_color}'>" . htmlspecialchars($fila['estado']) . "</span></td>
                                    <td class='text-center'>
                                        <a href='editar_materiales.php?id={$fila['id']}' class='btn btn-success btn-sm'>Editar</a>
                                        <a href='eliminar.php?id={$fila['id']}&tipo=material' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro?\");'>Eliminar</a>
                                    </td>
                                </tr>";
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

    // Muestra el formulario correspondiente según el tipo seleccionado
    function mostrarFormulario() {
        const tipo = $('#tipo_registro').val();
        $('#form_equipos').toggleClass('d-none', tipo !== 'equipos');
        $('#form_materiales').toggleClass('d-none', tipo !== 'materiales');
    }
    
    // Actualiza el campo cantidad según el tipo de material
    function actualizarCampoCantidad() {
        const tipo = document.querySelector('select[name="tipo"]').value;
        const cantidadInput = document.getElementById('cantidad');
        if(tipo === 'no consumible') {
            cantidadInput.value = 1;
            cantidadInput.readOnly = true;
        } else {
            cantidadInput.readOnly = false;
        }
    }

    $(document).ready(function() {
        // Aplica el tema guardado en localStorage
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            document.body.classList.remove('light');
            $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
        }
        
        // Inicializa select2 en los selects
        $('#tipo_registro, select[name="estado"], select[name="tipo"]').select2({
            theme: "bootstrap-5",
            minimumResultsForSearch: Infinity
        });
        
        // Opciones comunes para DataTables
        const commonDtOptions = {
            dom: 'lfrtip',
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
        };

        // Inicializa DataTables para ambas tablas
        $('#tablaEquipos').DataTable(commonDtOptions);
        $('#tablaMateriales').DataTable(commonDtOptions);

        // Muestra el formulario correspondiente al cambiar el tipo
        $('#tipo_registro').on('change', mostrarFormulario);
    });

    // Validación del formulario de equipos
    function validarFormularioEquipos() {
        const marca = document.forms["miformulario1"]["marca"].value.trim();
        const serie = document.forms["miformulario1"]["serie"].value.trim();
        const estado = document.forms["miformulario1"]["estado"].value;
        const letrasRegex = /^[A-Za-zÁÉÍÓÚáéíóúñÑ\s]{2,50}$/;
        const alfanumericoRegex = /^[A-Za-z0-9\-]{3,20}$/;

        if (!marca || !serie || !estado) {
            alert("Todos los campos de equipos son obligatorios.");
            return false;
        }
        if (!letrasRegex.test(marca)) {
            alert("La marca debe tener entre 2 y 50 letras.");
            return false;
        }
        if (!alfanumericoRegex.test(serie)) {
            alert("La serie debe tener entre 3 y 20 caracteres alfanuméricos.");
            return false;
        }
        return true;
    }

    // Validación del formulario de materiales
    function validarFormularioMateriales() {
        const nombre = document.forms["miformulario2"]["nombre"].value.trim();
        const serie = document.forms["miformulario2"]["serie"].value.trim();
        const tipo = document.forms["miformulario2"]["tipo"].value;
        const cantidad = document.forms["miformulario2"]["cantidad"].value.trim();
        const letrasRegex = /^[A-Za-zÁÉÍÓÚáéíóúñÑ\s]{2,50}$/;
        const alfanumericoRegex = /^[A-Za-z0-9\-]{3,20}$/;
        const numerosRegex = /^[0-9]{1,5}$/;

        if (!nombre || !serie || !tipo || !cantidad) {
            alert("Todos los campos de materiales son obligatorios.");
            return false;
        }
        if (!letrasRegex.test(nombre)) {
            alert("El nombre debe tener entre 2 y 50 letras.");
            return false;
        }
        if (!alfanumericoRegex.test(serie)) {
            alert("La serie debe tener entre 3 y 20 caracteres alfanuméricos.");
            return false;
        }
        if (tipo === "") {
            alert("Debe seleccionar un tipo válido.");
            return false;
        }
        if (!numerosRegex.test(cantidad) || parseInt(cantidad) < 1) {
            alert("La cantidad debe ser un número válido mayor a 0.");
            return false;
        }
        return true;
    }
</script>
</body>
</html>