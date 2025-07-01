<?php
session_start();
include '../conexion.php';

// Validar sesión y rol
if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    header('Location: ../login.php');
    exit;
}

$allowed_roles = ['administrador', 'almacenista'];
if (!in_array($_SESSION['rol'], $allowed_roles)) {
    header('Location: ../acceso_denegado.php');
    exit;
}

// Obtener ID del préstamo
$prestamo_id = $_GET['id'] ?? 0;
$tipo = $_GET['tipo'] ?? ''; // 'equipo' o 'material'

if (!$prestamo_id || !in_array($tipo, ['equipo', 'material'])) {
    // MODIFICACIÓN: Redirigir a listar_prestamos.php con error
    header('Location: listar_prestamos.php?error=Datos+inválidos+para+editar');
    exit;
}

// Cargar datos del préstamo... (lógica sin cambios)
if ($tipo === 'equipo') {
    $stmt = $connect->prepare("
        SELECT pe.*, e.marca, e.serie, i.nombre as instructor_nombre, i.id as instructor_id
        FROM prestamos_equipos pe
        JOIN equipos e ON pe.equipo_id = e.id
        JOIN instructores i ON pe.instructor_id = i.id
        WHERE pe.id = ?");
    $stmt->bind_param("i", $prestamo_id);
    $stmt->execute();
    $prestamo = $stmt->get_result()->fetch_assoc();

    // Obtener lista de instructores para editar
    $instructores = $connect->query("SELECT id, nombre FROM instructores ORDER BY nombre");
} else {
    $stmt = $connect->prepare("
        SELECT pm.*, m.id as material_id, m.nombre, m.tipo, m.cantidad as stock_total, i.nombre as instructor_nombre, i.id as instructor_id
        FROM prestamo_materiales pm
        JOIN materiales m ON pm.material_id = m.id
        JOIN instructores i ON pm.instructor_id = i.id
        WHERE pm.id = ?");
    $stmt->bind_param("i", $prestamo_id);
    $stmt->execute();
    $prestamo = $stmt->get_result()->fetch_assoc();

    // Obtener lista de instructores para editar
    $instructores = $connect->query("SELECT id, nombre FROM instructores ORDER BY nombre");

    if ($prestamo) {
        if ($prestamo['tipo'] === 'consumible') {
            $prestamo['max_permitido'] = $prestamo['stock_total'] + $prestamo['cantidad'];
        } else {
            $prestamo['max_permitido'] = 1;
        }
    }
}


if (!$prestamo) {
    header('Location: listar_prestamos.php?error=Préstamo no encontrado');
    exit;
}

$error_message = null; // Variable para almacenar el error

// Obtener lista de instructores y equipos/materiales para los selects
$instructores = $connect->query("SELECT id, nombre FROM instructores ORDER BY nombre");
if ($tipo === 'equipo') {
    $equipos = $connect->query("SELECT id, marca, serie FROM equipos ORDER BY marca, serie");
} else {
    $materiales = $connect->query("SELECT id, nombre, tipo FROM materiales ORDER BY nombre");
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? '';
    $fecha_devolucion = !empty($_POST['fecha_devolucion']) ? $_POST['fecha_devolucion'] : null;
    $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : null;
    $instructor_id = isset($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;
    $equipo_id = isset($_POST['equipo_id']) ? (int)$_POST['equipo_id'] : null;
    $material_id = isset($_POST['material_id']) ? (int)$_POST['material_id'] : null;

    if (!$fecha_prestamo) {
        $error_message = "Fecha de préstamo es obligatoria";
    } elseif (!$instructor_id) {
        $error_message = "Debe seleccionar un instructor";
    } elseif ($tipo === 'equipo' && !$equipo_id) {
        $error_message = "Debe seleccionar un equipo";
    } elseif ($tipo === 'material' && !$material_id) {
        $error_message = "Debe seleccionar un material";
    } else {
        $connect->begin_transaction();

        try {
            if ($tipo === 'equipo') {
                if (!$fecha_devolucion) {
                    throw new Exception("Fecha de devolución es obligatoria para equipos");
                }
                // Actualizar préstamo de equipo (incluye instructor y equipo)
                $stmt = $connect->prepare("UPDATE prestamos_equipos SET fecha_prestamo = ?, fecha_devolucion = ?, instructor_id = ?, equipo_id = ? WHERE id = ?");
                $stmt->bind_param("ssiii", $fecha_prestamo, $fecha_devolucion, $instructor_id, $equipo_id, $prestamo_id);
                if (!$stmt->execute()) {
                    throw new Exception("No se pudo actualizar el préstamo de equipo.");
                }
            } else { // Materiales
                if ($prestamo['tipo'] === 'no consumible' && !$fecha_devolucion) {
                    throw new Exception("Fecha de devolución es obligatoria para materiales no consumibles");
                }
                if ($cantidad === null || $cantidad <= 0) {
                    throw new Exception("La cantidad debe ser mayor a cero");
                }
                // Lógica de stock para consumibles
                if ($prestamo['tipo'] === 'consumible') {
                    $max_permitido = $prestamo['stock_total'] + $prestamo['cantidad'];
                    if ($cantidad > $max_permitido) {
                        throw new Exception("No puede asignar más de $max_permitido unidades.");
                    }
                    $diferencia = $cantidad - $prestamo['cantidad'];
                    if ($diferencia != 0) {
                        $stmt_mat = $connect->prepare("UPDATE materiales SET cantidad = cantidad - ? WHERE id = ?");
                        $stmt_mat->bind_param("ii", $diferencia, $material_id);
                        if (!$stmt_mat->execute()) {
                            throw new Exception("No se pudo actualizar el stock de materiales.");
                        }
                        $stmt_mat->close();
                    }
                }
                // Actualizar préstamo de material (incluye instructor y material)
                $stmt = $connect->prepare("UPDATE prestamo_materiales SET fecha_prestamo = ?, fecha_devolucion = ?, cantidad = ?, instructor_id = ?, material_id = ? WHERE id = ?");
                $stmt->bind_param("ssiiii", $fecha_prestamo, $fecha_devolucion, $cantidad, $instructor_id, $material_id, $prestamo_id);
                if (!$stmt->execute()) {
                    throw new Exception("No se pudo actualizar el préstamo de material.");
                }
            }

            $connect->commit();
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
                    text: 'Préstamo actualizado correctamente.'
                }).then(() => { window.location='listar_prestamos.php'; });
            });
            </script>
            </body></html>";
            exit;

        } catch (Exception $e) {
            $connect->rollback();
            $error_message = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Préstamo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/editar_prestamo.css">
</head>
<body>
    <div class="container">
        <header class="my-4">
            <h1><i class="fas fa-edit"></i> Editar Préstamo</h1>
        </header>
        
        <section class="prestamo-form">
            <form method="POST" onsubmit="return validarFormulario(event)">
                <div class="form-group">
                    <label>Instructor:</label>
                    <select name="instructor_id" class="form-control" required>
                        <option value="">Seleccione un instructor</option>
                        <?php foreach ($instructores as $inst): ?>
                            <option value="<?= $inst['id'] ?>" <?= ($inst['id'] == $prestamo['instructor_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inst['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($tipo === 'equipo'): ?>
                <div class="form-group">
                    <label>Equipo:</label>
                    <select name="equipo_id" class="form-control" required>
                        <option value="">Seleccione un equipo</option>
                        <?php foreach ($equipos as $eq): ?>
                            <option value="<?= $eq['id'] ?>" <?= ($eq['id'] == $prestamo['equipo_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($eq['marca'] . ' - ' . $eq['serie']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php elseif ($tipo === 'material'): ?>
                <div class="form-group">
                    <label>Material:</label>
                    <select name="material_id" class="form-control" required>
                        <option value="">Seleccione un material</option>
                        <?php foreach ($materiales as $mat): ?>
                            <option value="<?= $mat['id'] ?>" <?= ($mat['id'] == $prestamo['material_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mat['nombre'] . ' (' . $mat['tipo'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Fecha Préstamo:</label>
                    <input type="datetime-local" name="fecha_prestamo" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($prestamo['fecha_prestamo'])) ?>" required>
                </div>
                <?php if ($tipo === 'equipo' || ($tipo === 'material' && $prestamo['tipo'] === 'no consumible')): ?>
                    <div class="form-group">
                        <label>Fecha Devolución:</label>
                        <input type="datetime-local" name="fecha_devolucion" class="form-control" value="<?= $prestamo['fecha_devolucion'] ? date('Y-m-d\TH:i', strtotime($prestamo['fecha_devolucion'])) : '' ?>" <?= ($tipo === 'equipo' || $prestamo['tipo'] === 'no consumible') ? 'required' : '' ?>>
                    </div>
                <?php endif; ?>
                <?php if ($tipo === 'material'): ?>
                    <div class="form-group">
                        <label>Cantidad:</label>
                        <input type="number" name="cantidad" class="form-control" value="<?= $prestamo['cantidad'] ?>" min="1" max="<?= $prestamo['max_permitido'] ?>" <?= ($prestamo['tipo'] === 'no consumible') ? 'readonly' : '' ?> required>
                        <?php if ($prestamo['tipo'] === 'consumible'): ?>
                            <div class="info-disponibilidad">
                                Puedes asignar hasta <?= $prestamo['max_permitido'] ?> unidades.
                            </div>
                        <?php else: ?>
                            <div class="info-disponibilidad">
                                Material no consumible - Solo se puede prestar 1 unidad.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-success mt-3">Guardar Cambios</button>
                <a href="listar_prestamos.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // MODIFICACIÓN: Función de validación con SweetAlert2
        function validarFormulario(event) {
            const tipo = "<?= $tipo ?>";
            const materialTipo = "<?= $prestamo['tipo'] ?? '' ?>";
            const fechaPrestamo = document.querySelector('input[name="fecha_prestamo"]').value;
            const fechaDevolucionInput = document.querySelector('input[name="fecha_devolucion"]');
            const fechaDevolucion = fechaDevolucionInput ? fechaDevolucionInput.value : null;
            
            if (!fechaPrestamo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validación',
                    text: 'La fecha de préstamo es obligatoria'
                });
                return false;
            }

            if ((tipo === 'equipo' || (tipo === 'material' && materialTipo === 'no consumible')) && !fechaDevolucion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validación',
                    text: 'Este tipo de préstamo requiere una fecha de devolución'
                });
                return false;
            }

            if (fechaDevolucion && new Date(fechaPrestamo) >= new Date(fechaDevolucion)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validación',
                    text: 'La fecha de devolución no puede ser anterior o igual a la fecha de préstamo'
                });
                return false;
            }

            return true; // Si todo está bien, permite que el formulario se envíe
        }

        // MODIFICACIÓN: Mostrar error del servidor con SweetAlert2
        <?php if ($error_message): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al procesar',
                    text: <?= json_encode($error_message) ?>
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>