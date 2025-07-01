<?php
session_start(); // Inicia la sesión
include("../conexion.php"); // Incluye la conexión a la base de datos

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Verifica si la petición es POST
    // Verificar sesión y rol
    if (!isset($_SESSION['id']) || !in_array($_SESSION['rol'], ['administrador', 'almacenista'])) {
        // Si no hay sesión o el rol no es válido, redirige al login
        $_SESSION['mensaje'] = "Error: Acceso no autorizado";
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: ../login.php');
        exit();
    }

    // Capturar y sanitizar datos del formulario
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $cedula = trim($_POST["cedula"]);
    $correo = trim($_POST["correo"]);
    $telefono = trim($_POST["telefono"]);
    $estado = trim($_POST["estado"]);
    $almacenista_id = null; // Inicializa el id de almacenista

    // Lógica para determinar almacenista_id según el rol del usuario
    if ($_SESSION['rol'] === 'almacenista') {
        // Si el usuario es almacenista, verifica que exista como tal
        $sql_verificar = "SELECT id FROM almacenista WHERE usuario_id = ?";
        $stmt_verificar = mysqli_prepare($connect, $sql_verificar);
        mysqli_stmt_bind_param($stmt_verificar, "i", $_SESSION['id']);
        mysqli_stmt_execute($stmt_verificar);
        mysqli_stmt_store_result($stmt_verificar);
        
        if (mysqli_stmt_num_rows($stmt_verificar) > 0) {
            // Si existe, obtiene el id de almacenista
            mysqli_stmt_bind_result($stmt_verificar, $almacenista_id);
            mysqli_stmt_fetch($stmt_verificar);
        } else {
            // Si no existe, muestra error y redirige
            $_SESSION['mensaje'] = "Error: No estás registrado como almacenista válido";
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: 1registrofuncionario.php');
            exit();
        }
        mysqli_stmt_close($stmt_verificar);
    } 
    elseif ($_SESSION['rol'] === 'administrador') {
        // Si el usuario es administrador
        if (isset($_POST['almacenista_id'])) {
            // Si se envió un almacenista específico desde el formulario
            $almacenista_id = (int)$_POST['almacenista_id'];
        } else {
            // Si no, toma el primer almacenista activo como predeterminado
            $sql_almacenista = "SELECT id FROM almacenista WHERE estado = 'activo' LIMIT 1";
            $result = mysqli_query($connect, $sql_almacenista);
            if ($row = mysqli_fetch_assoc($result)) {
                $almacenista_id = $row['id'];
            } else {
                // Si no hay almacenistas activos, muestra error y redirige
                $_SESSION['mensaje'] = "Error: No hay almacenistas activos registrados";
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: 1registrofuncionario.php');
                exit();
            }
        }
    }

    // Validaciones de campos obligatorios
    if (empty($nombre) || empty($apellido) || empty($cedula) || empty($correo)) {
        $_SESSION['mensaje'] = "Error: Campos obligatorios faltantes";
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: 1registrofuncionario.php');
        exit();
    }

    // Verificar duplicados de correo y cédula
    $errores = [];
    
    // Verifica si el correo ya está registrado
    $check = "SELECT id FROM instructores WHERE correo = ?";
    $stmt = mysqli_prepare($connect, $check);
    mysqli_stmt_bind_param($stmt, "s", $correo);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) $errores[] = "El correo ya está registrado";
    mysqli_stmt_close($stmt);
    
    // Verifica si la cédula ya está registrada
    $check = "SELECT id FROM instructores WHERE cedula = ?";
    $stmt = mysqli_prepare($connect, $check);
    mysqli_stmt_bind_param($stmt, "s", $cedula);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) $errores[] = "La cédula ya está registrada";
    mysqli_stmt_close($stmt);
    
    if (!empty($errores)) {
        // Si hay errores de duplicados, muestra mensaje y redirige
        $_SESSION['mensaje'] = "Error: " . implode(", ", $errores);
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: 1registrofuncionario.php');
        exit();
    }

    // Inserta el nuevo instructor en la base de datos
    $sql = "INSERT INTO instructores (nombre, apellido, cedula, correo, telefono, estado, almacenista_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $nombre, $apellido, $cedula, $correo, $telefono, $estado, $almacenista_id);

    if (mysqli_stmt_execute($stmt)) {
        // Si la inserción es exitosa, muestra mensaje de éxito
        $_SESSION['mensaje'] = "Instructor registrado exitosamente";
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        // Si hay error técnico, muestra mensaje de error
        $_SESSION['mensaje'] = "Error técnico al registrar: " . mysqli_error($connect);
        $_SESSION['tipo_mensaje'] = 'error';
    }

    mysqli_stmt_close($stmt);
    header('Location: 1registrofuncionario.php'); // Redirige al formulario
    exit();
}