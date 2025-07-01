<?php
// Inicia la sesión
session_start();

// Incluye el archivo de conexión a la base de datos
include("../conexion.php");

// Verifica si el usuario tiene permisos de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    // Si no es administrador, redirige al login
    header('Location: ../login.php');
    exit;
}

// Verifica si se recibió el parámetro 'id' por GET
if (isset($_GET['id'])) {
    $id_almacenista = $_GET['id'];
    
    try {
        // 1. Verifica el estado actual del almacenista y obtiene el usuario asociado
        $check_sql = "SELECT estado, usuario_id FROM almacenista WHERE id = ?";
        $check_stmt = $connect->prepare($check_sql);
        $check_stmt->bind_param("i", $id_almacenista);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        // Si no se encuentra el almacenista, lanza una excepción
        if ($result->num_rows === 0) {
            throw new Exception("No se encontró el almacenista con ID: $id_almacenista");
        }
        
        $almacenista = $result->fetch_assoc();
        $usuario_id = $almacenista['usuario_id'];
        $estado_actual = $almacenista['estado'];
        
        // 2. Si el almacenista ya está inactivo, lanza una excepción
        if ($estado_actual == 'inactivo') {
            throw new Exception("No se puede eliminar: El almacenista ya está INACTIVO porque tiene historial en el sistema.");
        }
        
        // 3. Verifica si el almacenista tiene registros/historial en la tabla registro_horas
        $historial_sql = "SELECT COUNT(*) AS total FROM registro_horas WHERE almacenista_id = ?";
        $historial_stmt = $connect->prepare($historial_sql);
        $historial_stmt->bind_param("i", $id_almacenista);
        $historial_stmt->execute();
        $historial_result = $historial_stmt->get_result();
        $tiene_historial = $historial_result->fetch_assoc()['total'] > 0;
        
        if ($tiene_historial) {
            // Si tiene historial, cambia el estado a 'inactivo'
            $update_sql = "UPDATE almacenista SET estado = 'inactivo' WHERE id = ?";
            $update_stmt = $connect->prepare($update_sql);
            $update_stmt->bind_param("i", $id_almacenista);
            
            if ($update_stmt->execute()) {
                // Mensaje de advertencia si se cambió a inactivo
                $_SESSION['mensaje'] = "No se puede eliminar porque tiene historial. Se ha cambiado a estado INACTIVO correctamente.";
                $_SESSION['tipo_mensaje'] = "warning";
            } else {
                // Si falla el cambio de estado, lanza una excepción
                throw new Exception("Error al cambiar a estado INACTIVO");
            }
        } else {
            // Si no tiene historial, elimina completamente al almacenista y su usuario asociado
            $connect->begin_transaction();
            
            // Elimina el almacenista
            $delete_almacenista = $connect->prepare("DELETE FROM almacenista WHERE id = ?");
            $delete_almacenista->bind_param("i", $id_almacenista);
            $delete_almacenista->execute();
            
            // Elimina el usuario asociado
            $delete_usuario = $connect->prepare("DELETE FROM usuario WHERE id = ?");
            $delete_usuario->bind_param("i", $usuario_id);
            $delete_usuario->execute();
            
            // Confirma la transacción
            $connect->commit();
            
            // Mensaje de éxito si se eliminó correctamente
            $_SESSION['mensaje'] = "Almacenista eliminado correctamente del sistema";
            $_SESSION['tipo_mensaje'] = "success";
        }
        
    } catch (Exception $e) {
        // Si ocurre un error, revierte la transacción si es necesario
        if (isset($connect) && $connect->errno) {
            $connect->rollback();
        }
        // Guarda el mensaje de error en la sesión
        $_SESSION['mensaje'] = $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    // Redirige a la página de registro de almacenistas
    header('Location: registrar_almacenista.php');
    exit();
}
?>