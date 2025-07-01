<?php
session_start();
// Incluye el archivo de conexión a la base de datos
include 'conexion.php'; 

// Verifica la conexión a la base de datos
if (!$connect) {
    header('Content-Type: application/json');
    echo json_encode(['titulo' => 'Error', 'html' => '<p>Error de Conexión a la Base de Datos.</p>']);
    exit;
}

// Obtiene el tipo de detalle solicitado desde la URL
$tipo = $_GET['tipo'] ?? '';

// Define los títulos para cada tipo de detalle
$titulos = [
    'insumos'       => 'Detalle de Insumos en Stock',
    'prestamos'     => 'Detalle de Préstamos Activos',
    'funcionarios'  => 'Lista de Funcionarios Registrados',
    'almacenistas'  => 'Lista de Almacenistas Registrados'
];

// Valida que el tipo solicitado sea válido
if (!array_key_exists($tipo, $titulos)) {
    header('Content-Type: application/json');
    echo json_encode(['titulo' => 'Error', 'html' => '<p>Tipo de solicitud no válida.</p>']);
    exit;
}

// Restringe el acceso a la lista de almacenistas solo a administradores
if ($tipo === 'almacenistas' && (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador')) {
    header('Content-Type: application/json');
    echo json_encode(['titulo' => 'Acceso Denegado', 'html' => '<ul><li>No tienes permiso para ver esta información.</li></ul>']);
    exit;
}

// Inicializa la respuesta y la variable de salida HTML
$respuesta = ['titulo' => $titulos[$tipo], 'html' => ''];
$html_output = '<ul>';
$query = ''; 
$result = null; 

// Selecciona la consulta SQL según el tipo solicitado
switch ($tipo) {
    case 'insumos':
        // Consulta para obtener equipos disponibles y materiales con stock
        $query = "(SELECT marca AS nombre, 1 AS cantidad, 'Equipo' AS tipo FROM equipos WHERE estado = 'disponible') UNION ALL (SELECT nombre, cantidad, 'Material' AS tipo FROM materiales WHERE cantidad > 0) ORDER BY nombre";
        $result = mysqli_query($connect, $query);
        break;
    case 'prestamos':
        // Consulta para obtener préstamos activos de equipos y materiales
        $query = "SELECT COALESCE(i.nombre, 'Funcionario no encontrado') AS funcionario, COALESCE(e.marca, 'Equipo no encontrado') AS item, pe.fecha_prestamo, 'Equipo' as tipo_item FROM prestamos_equipos pe LEFT JOIN instructores i ON pe.instructor_id = i.id LEFT JOIN equipos e ON pe.equipo_id = e.id LEFT JOIN devolucion_equipos de ON pe.id = de.prestamo_equipo_id WHERE de.id IS NULL UNION ALL SELECT COALESCE(i.nombre, 'Funcionario no encontrado') AS funcionario, COALESCE(m.nombre, 'Material no encontrado') AS item, pm.fecha_prestamo, 'Material' as tipo_item FROM prestamo_materiales pm LEFT JOIN instructores i ON pm.instructor_id = i.id LEFT JOIN materiales m ON pm.material_id = m.id LEFT JOIN devolucion_materiales dm ON pm.id = dm.prestamo_material_id WHERE dm.id IS NULL ORDER BY fecha_prestamo DESC";
        $result = mysqli_query($connect, $query);
        break;
    case 'funcionarios':
        // Consulta para obtener la lista de funcionarios
        $query = "SELECT nombre, correo, cedula FROM instructores ORDER BY nombre";
        $result = mysqli_query($connect, $query);
        break;
    case 'almacenistas':
        // Consulta para obtener la lista de almacenistas
        $query = "SELECT nombre, apellido, correo, estado FROM almacenista ORDER BY nombre";
        $result = mysqli_query($connect, $query);
        break;
}

// Si la consulta falla, muestra el error SQL
if (!$result) {
    $html_output .= '<li><strong>Error de SQL:</strong> ' . mysqli_error($connect) . '</li>';
} 
// Si no hay resultados, muestra un mensaje según el tipo
elseif (mysqli_num_rows($result) == 0) {
    if ($tipo == 'insumos') $html_output .= '<li>No hay insumos con stock disponible.</li>';
    if ($tipo == 'prestamos') $html_output .= '<li>No hay préstamos activos actualmente.</li>';
    if ($tipo == 'funcionarios') $html_output .= '<li>No hay funcionarios registrados.</li>';
    if ($tipo == 'almacenistas') $html_output .= '<li>No hay almacenistas registrados.</li>';
} 
// Si hay resultados, los recorre y arma la lista HTML
else {
    while ($row = mysqli_fetch_assoc($result)) {
        switch ($tipo) {
            case 'insumos':
                // Muestra nombre, cantidad y tipo de insumo
                $html_output .= '<li><strong>' . htmlspecialchars($row['nombre']) . '</strong> - Cantidad: ' . $row['cantidad'] . ' <em>(' . $row['tipo'] . ')</em></li>';
                break;
            case 'prestamos':
                // Muestra información del préstamo
                $fecha = date_create($row['fecha_prestamo']);
                $fecha_formateada = $fecha ? date_format($fecha, 'd/m/Y H:i') : 'Fecha inválida';
                $html_output .= '<li><strong>' . htmlspecialchars($row['item']) . '</strong> (' . $row['tipo_item'] . ') prestado a <em>' . htmlspecialchars($row['funcionario']) . '</em> el ' . $fecha_formateada . '</li>';
                break;
            case 'funcionarios':
                // Muestra nombre, cédula y correo del funcionario
                $html_output .= '<li><strong>' . htmlspecialchars($row['nombre']) . '</strong><br><small>Cédula: ' . htmlspecialchars($row['cedula']) . ' - Correo: ' . htmlspecialchars($row['correo']) . '</small></li>';
                break;
            case 'almacenistas':
                // Muestra nombre, correo y estado del almacenista
                $nombre_completo = htmlspecialchars($row['nombre'] . ' ' . $row['apellido']);
                $estado_clase = $row['estado'] === 'activo' ? 'color:green;' : 'color:red;';
                $html_output .= '<li><strong>' . $nombre_completo . '</strong><br><small>Correo: ' . htmlspecialchars($row['correo']) . ' - Estado: <span style="' . $estado_clase . '">' . htmlspecialchars(ucfirst($row['estado'])) . '</span></small></li>';
                break;
        }
    }
}

// Finaliza la lista HTML y prepara la respuesta
$html_output .= '</ul>';
$respuesta['html'] = $html_output;

// Cierra la conexión y devuelve la respuesta en formato JSON
mysqli_close($connect);
header('Content-Type: application/json');
echo json_encode($respuesta);
?>