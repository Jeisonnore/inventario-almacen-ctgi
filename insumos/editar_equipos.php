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

// --- LÓGICA DE LA PÁGINA ---
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    // MODIFICADO: Redirigir con parámetro de error
    header("Location: 2insumos.php?error=" . urlencode("ID de equipo inválido."));
    exit;
}

$id = $_GET['id'];
$sql = "SELECT * FROM equipos WHERE id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$equipo = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$equipo) {
    // MODIFICADO: Redirigir con parámetro de error
    header("Location: 2insumos.php?error=" . urlencode("Equipo no encontrado."));
    exit;
}

// --- LÓGICA PARA EL SIDEBAR ---
$user_correo = $_SESSION['usuario'] ?? 'correo@desconocido.com';
$user_rol = ucfirst($_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="../css/editar_equipos.css">
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
                <li class="active"><a href="2insumos.php"><i class="icon fas fa-box-open"></i><span class="text">Insumos</span></a></li>
                <li><a href="../reportes/reportes.php"><i class="icon fas fa-chart-bar"></i><span class="text">Reportes</span></a></li>
                <li><a href="../logout.php"><i class="icon fas fa-sign-out-alt"></i><span class="text">Cerrar sesión</span></a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="toggle-mode-icons">
            <i id="theme-icon" class="fas fa-moon" onclick="toggleTheme()"></i>
        </div>
        
        <header><h1><i class="fas fa-edit"></i> Editar Equipo</h1></header>
        <hr>

        <section class="card-form p-4 rounded" style="max-width: 800px; margin: auto;">
            <form method="POST" action="editar2_equipos.php" name="miformulario1" onsubmit="return validarFormulario();" class="row g-3">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($equipo['id']); ?>">

                <div class="col-md-6">
                    <label for="marca" class="form-label">Marca:</label>
                    <input type="text" id="marca" name="marca" class="form-control" value="<?php echo htmlspecialchars($equipo['marca']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="serie" class="form-label">Serie:</label>
                    <input type="text" id="serie" name="serie" class="form-control" value="<?php echo htmlspecialchars($equipo['serie']); ?>" required>
                </div>
                <div class="col-md-12">
                    <label for="estado" class="form-label">Estado:</label>
                    <select id="estado" name="estado" class="form-select" required>
                        <option value="disponible" <?php echo ($equipo['estado'] == 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                        <option value="deteriorado" <?php echo ($equipo['estado'] == 'deteriorado') ? 'selected' : ''; ?>>Deteriorado</option>
                    </select>
                </div>
                
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary" name="btn_actualizar">Guardar Cambios</button>
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
    function toggleTheme() { /* ... sin cambios ... */ }

    $(document).ready(function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            document.body.classList.remove('light');
            $('#theme-icon').addClass('fa-sun').removeClass('fa-moon');
        }

        $('#estado').select2({
            theme: "bootstrap-5",
            minimumResultsForSearch: Infinity
        });
    });

    function validarFormulario() {
        const marca = document.forms["miformulario1"]["marca"].value.trim();
        const serie = document.forms["miformulario1"]["serie"].value.trim();
        const estado = document.forms["miformulario1"]["estado"].value;

        const letrasRegex = /^[A-Za-zÁÉÍÓÚáéíóúñÑ\s]{2,50}$/;
        const alfanumericoRegex = /^[A-Za-z0-9\-]{3,20}$/;

        if (!marca || !serie || !estado) {
            Swal.fire({
                icon: 'warning',
                title: 'Validación',
                text: 'Todos los campos son obligatorios.'
            });
            return false;
        }
        if (!letrasRegex.test(marca)) {
            Swal.fire({
                icon: 'warning',
                title: 'Validación',
                text: 'La marca debe tener entre 2 y 50 letras.'
            });
            return false;
        }
        if (!alfanumericoRegex.test(serie)) {
            Swal.fire({
                icon: 'warning',
                title: 'Validación',
                text: 'La serie debe tener entre 3 y 20 caracteres alfanuméricos.'
            });
            return false;
        }
        return true;
    }
</script>

<?php
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que el DOM esté listo y sidebar exista antes de mostrar SweetAlert2
            setTimeout(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación',
                    text: '{$error_message}'
                });
            }, 100);
        });
    </script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $marca = $_POST['marca'];
    $serie = $_POST['serie'];
    $estado = $_POST['estado'];

    // Validación adicional del lado del servidor
    if (empty($marca) || empty($serie) || empty($estado)) {
        echo "<!DOCTYPE html>
        <html><head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Validación',
                text: 'Todos los campos son obligatorios.'
            }).then(() => { window.location='2insumos.php'; });
        });
        </script>
        </body></html>";
        exit();
    }

    $update = $connect->prepare("UPDATE equipos SET marca=?, serie=?, estado=? WHERE id=?");
    $update->bind_param("sssi", $marca, $serie, $estado, $id);

    if($update->execute()) {
        // Registrar en histórico
        $sql_historial = "INSERT INTO historial_equipos (equipo_id, marca, serie, estado, fecha, movimiento, cambios) VALUES (?, ?, ?, ?, NOW(), 'edicion', ?)";
        $stmt_historial = $connect->prepare($sql_historial);
        $cambios = "Edición: Marca=$marca, Serie=$serie, Estado=$estado";
        $stmt_historial->bind_param("issss", $id, $marca, $serie, $estado, $cambios);
        $stmt_historial->execute();

        // Mostrar notificación SweetAlert2 en una página HTML completa
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
                text: 'Equipo actualizado correctamente.'
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
                text: 'Error al actualizar el equipo.'
            }).then(() => { window.location='2insumos.php'; });
        });
        </script>
        </body></html>";
        exit();
    }
}
?>

</body>
</html>