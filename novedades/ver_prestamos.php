<?php
session_start();
include '../conexion.php';

if (!isset($_SESSION['id'])) {
    echo "Acceso no autorizado.";
    exit;
}

date_default_timezone_set('America/Bogota');
$hoy = date('Y-m-d H:i:s');

$sql = "
    SELECT 
        i.nombre AS instructor,
        i.cedula,
        'Equipo' AS tipo,
        e.serie,
        e.marca,
        NULL AS nombre_material,
        NULL AS cantidad,
        pe.fecha_prestamo,
        pe.fecha_devolucion
    FROM prestamos_equipos pe
    JOIN instructores i ON i.id_instructores = pe.instructores_id_instructores
    JOIN equipos e ON e.id_equipos = pe.equipos_id_equipos

    UNION

    SELECT 
        i.nombre AS instructor,
        i.cedula,
        'Material' AS tipo,
        NULL AS serie,
        NULL AS marca,
        m.nombre AS nombre_material,
        pm.cantidad,
        pm.fecha_prestamo,
        pm.fecha_devolucion
    FROM prestamo_materiales pm
    JOIN instructores i ON i.id_instructores = pm.instructores_id_instructores
    JOIN materiales m ON m.id_material = pm.materiales_id_material

    ORDER BY fecha_prestamo DESC
";

$res = $connect->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro funcionarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../css/boostranp.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- jQuery y DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

</head>

<body>

    <!-- Sidebar -->
    <nav>
        <h2>Almacén SENA</h2>
        <ul>
            <li>
                <a href="<?php echo ($_SESSION['rol'] === 'administrador') ? '../admin.php' : '../almacenista.php'; ?>">
                    <i class="fas fa-home"></i> Inicio
                </a>
            </li>

            <li><a href="../funcionarios/1registrofuncionario.php"><i class="fas fa-laptop"></i> Registro de Funcionarios</a></li>
            <li><a href="../prestamo1/prestamos.php"><i class="fas fa-hand-holding"></i> Préstamos</a></li>
             <li><a href="../lista_prestamos/listar_prestamos.php"><i class="fas fa-list"></i> Listar Préstamos</a></li>
            <li><a href="../devoluciones/4devolucion.php"><i class="fas fa-laptop"></i> Devolución</a></li>
            <li><a href="../insumos/2insumos.php"><i class="fas fa-box-open"></i> Insumos</a></li>
            <li><a href="../reportes/reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a></li>
            <li><a href="../novedades/ver_prestamos.php"><i class="fas fa-chart-bar"></i> Novedades </a></li>

        </ul>
    </nav>


    <!-- Main Content -->
    <main class="main-content">
        <h1 class="text-center mb-4">📋 Préstamos Registrados</h1>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla_prestamos" class="display">
                        <thead class="table-dark">
                            <tr>
                                <th>Instructor</th>
                                <th>Cédula</th>
                                <th>Tipo</th>
                                <th>Marca</th>
                                <th>Serie</th>
                                <th>Material</th>
                                <th>Cantidad</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Devolución</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $res->fetch_assoc()): 
                                $vencido = ($row['fecha_devolucion'] < $hoy) ? 'Sí' : 'No';
                            ?>
                                <tr class="<?php echo ($vencido === 'Sí') ? 'vencido' : ''; ?>">
                                    <td><?php echo $row['instructor']; ?></td>
                                    <td><?php echo $row['cedula']; ?></td>
                                    <td><?php echo $row['tipo']; ?></td>
                                    <td><?php echo $row['marca'] ?? '-'; ?></td>
                                    <td><?php echo $row['serie'] ?? '-'; ?></td>
                                    <td><?php echo $row['nombre_material'] ?? '-'; ?></td>
                                    <td><?php echo $row['cantidad'] ?? '-'; ?></td>
                                    <td><?php echo $row['fecha_prestamo']; ?></td>
                                    <td><?php echo $row['fecha_devolucion']; ?></td>
                                    <td>
                                        <?php echo ($vencido === 'Sí') 
                                            ? '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Vencido</span>' 
                                            : '<span class="text-success"><i class="fas fa-check-circle"></i> A tiempo</span>'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#tabla_prestamos').DataTable({
                pageLength: 10,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });
    </script>

</body>
</html>
