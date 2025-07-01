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

// --- PROCESAMIENTO DEL FORMULARIO (SI ES POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y limpiar datos
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $tipo = trim($_POST['tipo']);
    $cantidad = trim($_POST['cantidad']);
    $serie = trim($_POST['serie']);
    $estado = trim($_POST['estado']);

    // Para no consumibles, la cantidad siempre debe ser 1
    if($tipo == 'no consumible') {
        $cantidad = 1;
    }

    $update = $connect->prepare("UPDATE materiales SET nombre = ?, tipo = ?, cantidad = ?, serie = ?, estado = ? WHERE id = ?");
    $update->bind_param("ssissi", $nombre, $tipo, $cantidad, $serie, $estado, $id);

    if($update->execute()) {
        // Registrar en histórico
        $sql_historial = "INSERT INTO historial_materiales (material_id, nombre, tipo, cantidad, serie, fecha, movimiento, cambios) VALUES (?, ?, ?, ?, ?, NOW(), 'edicion', ?)";
        $stmt_historial = $connect->prepare($sql_historial);
        $cambios = "Edición: Nombre=$nombre, Tipo=$tipo, Cantidad=$cantidad, Serie=$serie, Estado=$estado";
        $stmt_historial->bind_param("isssss", $id, $nombre, $tipo, $cantidad, $serie, $cambios);
        $stmt_historial->execute();

        // Notificación SweetAlert2 inmediata
        echo "<!DOCTYPE html>
        <html><head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Material actualizado correctamente.'
            }).then(() => { window.location='2insumos.php'; });
        });
        </script>
        </body></html>";
        exit();
    } else {
        echo "<!DOCTYPE html>
        <html><head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al actualizar el material.'
            }).then(() => { window.location='2insumos.php'; });
        });
        </script>
        </body></html>";
        exit();
    }
}

// --- OBTENER DATOS PARA MOSTRAR EN EL FORMULARIO (SI ES GET) ---
$material = null;
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['mensaje'] = "Error: ID de material inválido.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: 2insumos.php");
    exit;
}

$id = $_GET['id'];
$query = $connect->prepare("SELECT * FROM materiales WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$material = $result->fetch_assoc();
$query->close();

if (!$material) {
    $_SESSION['mensaje'] = "Error: Material no encontrado.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: 2insumos.php");
    exit;
}

// --- LÓGICA PARA EL SIDEBAR ---
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$user_rol = ucfirst($_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Editar Material</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/editar_materiales.css">
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
                <li><a href="../funcionarios/1registrofuncionario.php"><i class="icon fas fa-user-plus"></i><span class="text">Funcionarios</span></a></li>
                <li><a href="../prestamo1/prestamos.php"><i class="icon fas fa-hand-holding-hand"></i><span class="text">Préstamos</span></a></li>
                <li><a href="../lista_prestamos/listar_prestamos.php"><i class="icon fas fa-list"></i><span class="text">Listados</span></a></li>
                <li><a href="../devoluciones/4devolucion.php"><i class="icon fas fa-undo"></i><span class="text">Devoluciones</span></a></li>
                <li class="active"><a href="../insumos/2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
                <li><a href="../reportes/reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes</span></a></li>
                <li><a href="../logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        
        <header><h1><i class="fas fa-edit"></i> Editar Material</h1></header>
        <hr>

        <section class="card-form p-4 rounded" style="max-width: 800px; margin: auto;">
            <form method="POST" action="editar_materiales.php" class="row g-3" name="formEditarMaterial">
                <input type="hidden" name="id" value="<?= htmlspecialchars($material['id']) ?>">
                
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($material['nombre']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="serie" class="form-label">Serie:</label>
                    <input type="text" id="serie" name="serie" class="form-control" value="<?= htmlspecialchars($material['serie']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="tipo" class="form-label">Tipo:</label>
                    <select id="tipo" name="tipo" class="form-select" required>
                        <option value="consumible" <?= ($material['tipo'] == 'consumible') ? 'selected' : '' ?>>Consumible</option>
                        <option value="no consumible" <?= ($material['tipo'] == 'no consumible') ? 'selected' : '' ?>>No Consumible</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="cantidad" class="form-label">Cantidad:</label>
                    <input type="number" id="cantidad" name="cantidad" class="form-control" value="<?= htmlspecialchars($material['cantidad']) ?>" min="1" <?= ($material['tipo'] == 'no consumible') ? 'readonly' : '' ?> required>
                </div>
                
                <div class="col-md-12">
                    <label for="estado" class="form-label">Estado:</label>
                    <select id="estado" name="estado" class="form-select" required>
                        <option value="disponible" <?= ($material['estado'] == 'disponible') ? 'selected' : '' ?>>Disponible</option>
                        <option value="prestado" <?= ($material['estado'] == 'prestado') ? 'selected' : '' ?>>Prestado</option>
                        <option value="deteriorado" <?= ($material['estado'] == 'deteriorado') ? 'selected' : '' ?>>Deteriorado</option>
                    </select>
                </div>
                
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <a href="2insumos.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
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
        $('#tipo, #estado').select2({
            theme: "bootstrap-5",
            minimumResultsForSearch: Infinity // Oculta la barra de búsqueda
        });
        
        // Deshabilitar campo cantidad si es no consumible
        $('#tipo').on('change', function() {
            const cantidadField = $('#cantidad');
            if(this.value === 'no consumible') {
                cantidadField.val(1);
                cantidadField.prop('readonly', true);
            } else {
                cantidadField.prop('readonly', false);
            }
        });
    });

    function validarFormularioMaterial() {
        const nombre = document.getElementById('nombre').value.trim();
        const serie = document.getElementById('serie').value.trim();
        const tipo = document.getElementById('tipo').value;
        const cantidad = document.getElementById('cantidad').value;
        const estado = document.getElementById('estado').value;

        if (!nombre || !serie || !tipo || !cantidad || !estado) {
            Swal.fire({
                icon: 'warning',
                title: 'Validación',
                text: 'Todos los campos son obligatorios.'
            });
            return false;
        }
        if (parseInt(cantidad) < 1 || isNaN(parseInt(cantidad))) {
            Swal.fire({
                icon: 'warning',
                title: 'Validación',
                text: 'La cantidad debe ser mayor o igual a 1.'
            });
            return false;
        }
        return true;
    }

    // Asignar la función al formulario
    document.forms['formEditarMaterial'].onsubmit = validarFormularioMaterial;
</script>

<?php
if (isset($_SESSION['mensaje'])) {
    $tipo = $_SESSION['tipo_mensaje'] ?? 'info';
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: " . json_encode($tipo === "success" ? "success" : ($tipo === "error" ? "error" : "info")) . ",
                title: " . ($tipo === "success" ? "'¡Éxito!'" : ($tipo === "error" ? "'Error'" : "'Aviso'")) . ",
                text: " . json_encode($mensaje) . ",
                confirmButtonText: 'Entendido'
            });
        });
    </script>";
}
?>
</body>
</html>