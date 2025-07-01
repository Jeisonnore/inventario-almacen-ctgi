<?php
session_start(); // Inicia la sesión

include("../conexion.php"); // Incluye el archivo de conexión a la base de datos

// Verifica que el usuario tenga rol de administrador y que la petición sea POST
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador' || $_SERVER["REQUEST_METHOD"] != "POST") {
    header('Location: ../login.php'); // Redirige al login si no cumple condiciones
    exit;
}

// --- CAPTURAR Y VALIDAR DATOS ---
// Verifica que los campos 'id' y 'usuario_id' existan y sean dígitos
if (!isset($_POST['id'], $_POST['usuario_id']) || !ctype_digit($_POST['id']) || !ctype_digit($_POST['usuario_id'])) {
    $_SESSION['mensaje'] = "Error: ID de usuario o almacenista inválido."; // Mensaje de error
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: registrar_almacenista.php"); // Redirige
    exit;
}

// Captura y limpia los datos del formulario
$id = trim($_POST['id']);
$usuario_id = trim($_POST['usuario_id']);
$nombre = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$cedula = trim($_POST['cedula']);
$correo = trim($_POST['correo']);
$telefono = trim($_POST['telefono']);
$estado = trim($_POST['estado']);
$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);

// --- VALIDACIONES DEL SERVIDOR ---

// Si se ingresó una contraseña, verifica que ambas coincidan
if (!empty($password) && $password !== $confirm_password) {
    $_SESSION['mensaje'] = "Error: Las contraseñas no coinciden."; // Mensaje de error
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: editar_2alma.php?id=$id"); // Redirige
    exit;
}

// Verifica que el correo o cédula no estén duplicados (excluyendo el registro actual)
$sql_verificar = "SELECT id FROM almacenista WHERE (correo = ? OR cedula = ?) AND id != ?";
$stmt_verificar = $connect->prepare($sql_verificar);
$stmt_verificar->bind_param("ssi", $correo, $cedula, $id);
$stmt_verificar->execute();
if ($stmt_verificar->get_result()->num_rows > 0) {
    $_SESSION['mensaje'] = "Error: El correo o la cédula ya están registrados para otro almacenista."; // Mensaje de error
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: editar_2alma.php?id=$id"); // Redirige
    exit;
}
$stmt_verificar->close();

// --- ACTUALIZAR DATOS ---

// 1. Actualiza la tabla 'almacenista' con los datos personales
$sql_almacenista = "UPDATE almacenista SET nombre = ?, apellido = ?, cedula = ?, correo = ?, telefono = ?, estado = ? WHERE id = ?";
$stmt_almacenista = $connect->prepare($sql_almacenista);
$stmt_almacenista->bind_param("ssssssi", $nombre, $apellido, $cedula, $correo, $telefono, $estado, $id);
$exito_almacenista = $stmt_almacenista->execute();
$stmt_almacenista->close();

// 2. Si se proporcionó una nueva contraseña y la actualización anterior fue exitosa
if (!empty($password) && $exito_almacenista) {
    // Cifra la nueva contraseña de forma segura
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Actualiza la contraseña en la tabla 'usuario'
    $sql_usuario = "UPDATE usuario SET password = ? WHERE id = ?";
    $stmt_usuario = $connect->prepare($sql_usuario);
    $stmt_usuario->bind_param("si", $hashed_password, $usuario_id);
    $stmt_usuario->execute();
    $stmt_usuario->close();
}

// --- REDIRIGIR CON MENSAJE ---
// Si la actualización fue exitosa
if ($exito_almacenista) {
    $_SESSION['mensaje'] = "Almacenista '$nombre $apellido' actualizado correctamente."; // Mensaje de éxito
    $_SESSION['tipo_mensaje'] = "success";
    header("Location: registrar_almacenista.php"); // Redirige
} else {
    $_SESSION['mensaje'] = "Error: No se pudo actualizar el almacenista."; // Mensaje de error
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: editar_2alma.php?id=$id"); // Redirige
}
exit; // Termina el script
?>