<?php
session_start();
require_once 'conexion.php';

// Verificar si se enviaron los datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['usuario']) || !isset($_POST['contraseña'])) {
    header("Location: index.php?error=datos_incompletos");
    exit;
}

// Limpiar y validar datos
$usuario = trim($_POST['usuario']);
$contraseña = $_POST['contraseña'];

if (empty($usuario) || empty($contraseña)) {
    header("Location: index.php?error=campos_vacios");
    exit;
}

// Buscar usuario en la base de datos
$sql = "SELECT id, usuario, contraseña, rol FROM usuario WHERE usuario = ?";
$stmt = $connect->prepare($sql);

if (!$stmt) {
    header("Location: index.php?error=error_bd");
    exit;
}

$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar credenciales
if ($resultado->num_rows !== 1) {
    header("Location: index.php?error=credenciales_invalidas");
    exit;
}

$fila = $resultado->fetch_assoc();

if (!password_verify($contraseña, $fila['contraseña'])) {
    echo "Contraseña ingresada: " . $contraseña . "<br>";
    echo "Contraseña en la BD: " . $fila['contraseña'] . "<br>";
    echo "Verificación: " . (password_verify($contraseña, $fila['contraseña']) ? "Correcta" : "Incorrecta");
    exit;
}


// Configurar sesión
$_SESSION['id'] = $fila['id'];
$_SESSION['usuario'] = $fila['usuario'];
$_SESSION['rol'] = $fila['rol'];
date_default_timezone_set("America/Bogota");

// Proceso específico para almacenistas
if ($fila['rol'] === 'almacenista') {
    $sql_almacenista = "SELECT id FROM almacenista WHERE usuario_id = ?";
    $stmt_alm = $connect->prepare($sql_almacenista);
    $stmt_alm->bind_param("i", $fila['id']);
    $stmt_alm->execute();
    $result_alm = $stmt_alm->get_result();
    
    if ($result_alm->num_rows === 1) {
        $almacenista = $result_alm->fetch_assoc();
        $_SESSION['almacenista_id'] = $almacenista['id'];
        
        // Registrar hora de ingreso
        $hora_ingreso = date('Y-m-d H:i:s');
        $sql_update = "UPDATE almacenista SET hora_ingreso = ? WHERE id = ?";
        $stmt_update = $connect->prepare($sql_update);
        $stmt_update->bind_param("si", $hora_ingreso, $almacenista['id']);
        $stmt_update->execute();
    } else {
        header("Location: index.php?error=almacenista_no_encontrado");
        exit;
    }
}

// Redirección según rol
switch ($fila['rol']) {
    case 'administrador':
        header("Location: admin.php");
        break;
    case 'almacenista':
        header("Location: almacenista.php");
        break;
    default:
        header("Location: index.php?error=rol_no_valido");
}

// Cerrar conexiones
$stmt->close();
if (isset($stmt_alm)) $stmt_alm->close();
if (isset($stmt_update)) $stmt_update->close();
$connect->close();
exit;
?>