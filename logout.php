<?php
session_start();
require_once 'conexion.php';

// Mostrar errores (solo en desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión activa
if (isset($_SESSION['usuario']) && isset($_SESSION['rol'])) {
    $usuario = $_SESSION['usuario'];
    $rol = $_SESSION['rol'];

    // Registrar hora de salida para almacenistas
    if ($rol === 'almacenista' && isset($_SESSION['almacenista_id'])) {
        date_default_timezone_set("America/Bogota");
        $hora_salida = date('Y-m-d H:i:s');
        $fecha_actual = date('Y-m-d');
        $almacenista_id = $_SESSION['almacenista_id'];

        try {
            // 1. Actualizar hora_salida en tabla almacenista
            $stmt_almacenista = $connect->prepare("UPDATE almacenista SET hora_salida = ? WHERE id = ?");
            if ($stmt_almacenista) {
                $stmt_almacenista->bind_param("si", $hora_salida, $almacenista_id);
                $stmt_almacenista->execute();
            } else {
                error_log("Error en prepare stmt_almacenista: " . $connect->error);
            }

            // 2. Actualizar registro_horas
            $stmt_registro = $connect->prepare("UPDATE registro_horas SET hora_salida = ? WHERE almacenista_id = ? AND fecha = ? AND hora_salida IS NULL");
            if ($stmt_registro) {
                $stmt_registro->bind_param("sis", $hora_salida, $almacenista_id, $fecha_actual);
                $stmt_registro->execute();

                if ($stmt_registro->affected_rows === 0) {
                    // Si no se encontró registro, insertar uno nuevo
                    $stmt_insert = $connect->prepare("INSERT INTO registro_horas (fecha, hora_ingreso, hora_salida, almacenista_id) VALUES (?, ?, ?, ?)");
                    if ($stmt_insert) {
                        $hora_ingreso_estimada = date('Y-m-d 08:00:00');
                        $stmt_insert->bind_param("sssi", $fecha_actual, $hora_ingreso_estimada, $hora_salida, $almacenista_id);
                        $stmt_insert->execute();
                    } else {
                        error_log("Error en prepare stmt_insert: " . $connect->error);
                    }
                }
            } else {
                error_log("Error en prepare stmt_registro: " . $connect->error);
            }

            // 3. Registrar log de cierre de sesión
            $stmt_log = $connect->prepare("INSERT INTO logs_sesiones (usuario_id, accion, fecha_hora) VALUES (?, 'Cierre de sesión', ?)");
            if ($stmt_log) {
                $stmt_log->bind_param("is", $almacenista_id, $hora_salida);
                $stmt_log->execute();
            } else {
                error_log("Error en prepare stmt_log: " . $connect->error);
            }

        } catch (Exception $e) {
            error_log("Error general al cerrar sesión: " . $e->getMessage());
        }
    }
}

// Destruir la sesión completamente
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirigir al login
header("Location: index.php?logout=1");
exit;
?>
