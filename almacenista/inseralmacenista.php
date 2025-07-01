<?php
session_start();
include("../conexion.php");

// 1. VERIFICAR QUE LOS DATOS LLEGAN POR MÉTODO POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. LIMPIAR Y OBTENER DATOS DEL FORMULARIO
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $cedula = trim($_POST["cedula"]);
    $correo = trim($_POST["correo"]);
    $telefono = trim($_POST["telefono"]);
    $estado = trim($_POST["estado"]);
    $hora_ingreso = date("Y-m-d H:i:s"); // Fecha y hora actual del registro

    // 3. VALIDACIÓN DE DUPLICADOS (PUNTO CLAVE)
    // Se comprueba si la cédula O el correo ya existen en la tabla 'almacenista'.
    $stmt_verificar = $connect->prepare("SELECT COUNT(*) FROM almacenista WHERE correo = ? OR cedula = ?");
    $stmt_verificar->bind_param("ss", $correo, $cedula);
    $stmt_verificar->execute();
    $stmt_verificar->bind_result($existe);
    $stmt_verificar->fetch();
    $stmt_verificar->close();

    // Si el contador $existe es mayor a 0, significa que ya hay un registro con esa cédula o correo.
    if ($existe > 0) {
        $_SESSION['mensaje'] = 'Error: La cédula o el correo electrónico ya se encuentran registrados.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: registrar_almacenista.php');
        exit(); // Detiene la ejecución del script.
    }

    // 4. CREACIÓN DEL USUARIO PARA INICIO DE SESIÓN
    // Se crea un registro en la tabla 'usuario' para que el almacenista pueda acceder al sistema.
    $rol = "almacenista";
    $clave_predeterminada = bin2hex(random_bytes(4)); // Genera una contraseña aleatoria de 8 caracteres.
    $hash_clave = password_hash($clave_predeterminada, PASSWORD_DEFAULT); // Encripta la contraseña.

    // Se inserta el nuevo usuario en la tabla 'usuario'.
    $stmt_crear_usuario = $connect->prepare("INSERT INTO usuario (usuario, contraseña, rol) VALUES (?, ?, ?)");
    $stmt_crear_usuario->bind_param("sss", $correo, $hash_clave, $rol);
    $stmt_crear_usuario->execute();
    $usuario_id = $stmt_crear_usuario->insert_id; // Se obtiene el ID del usuario recién creado.
    $stmt_crear_usuario->close();

    // 5. INSERCIÓN FINAL DEL ALMACENISTA
    // Ahora que tenemos el usuario_id, se guarda el registro completo en la tabla 'almacenista'.
    $sql_insertar = "INSERT INTO almacenista (nombre, apellido, cedula, correo, telefono, estado, hora_ingreso, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insertar = $connect->prepare($sql_insertar);
    $stmt_insertar->bind_param("sssssssi", $nombre, $apellido, $cedula, $correo, $telefono, $estado, $hora_ingreso, $usuario_id);

    // 6. VERIFICACIÓN Y REDIRECCIÓN FINAL
    if ($stmt_insertar->execute()) {
        // Si todo fue exitoso, se crea un mensaje de éxito que incluye la contraseña generada.
        $_SESSION['mensaje'] = 'Almacenista registrado con éxito. Contraseña para el nuevo usuario: ' . $clave_predeterminada;
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        // Si hubo un error en la inserción final.
        $_SESSION['mensaje'] = 'Error al registrar el almacenista: ' . $connect->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }
    $stmt_insertar->close();
    header('Location: registrar_almacenista.php');
    exit();
}
?>