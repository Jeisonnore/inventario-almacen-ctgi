<?php
session_start();
include('conexion.php');

if (isset($_SESSION['usuario']) && $_SESSION['rol'] === 'almacenista') {
    $usuario = $_SESSION['usuario'];
    $fecha = date("Y-m-d");
    $hora_ingreso = date("Y-m-d H:i:s");

    // Buscar ID del almacenista (CORRECCIÓN: campo es 'id' no 'idalmacenista')
    $stmt = $connect->prepare("SELECT id FROM almacenista WHERE correo = ?");
    $stmt->bind_param("s", $usuario);
    
    if (!$stmt->execute()) {
        die("Error al buscar almacenista: " . $connect->error);
    }
    
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $almacenista_id = $row['id']; // CORRECCIÓN: Usar 'id' que es el campo real

        // Verificar si ya existe registro hoy sin hora de salida (CORRECCIÓN: campo es 'almacenista_id')
        $check = $connect->prepare("SELECT id FROM registro_horas WHERE almacenista_id = ? AND fecha = ? AND hora_salida IS NULL");
        $check->bind_param("is", $almacenista_id, $fecha);
        
        if (!$check->execute()) {
            die("Error al verificar registro existente: " . $connect->error);
        }
        
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            // Insertar nuevo registro de ingreso (CORRECCIÓN: campo es 'almacenista_id')
            $insert = $connect->prepare("INSERT INTO registro_horas (almacenista_id, fecha, hora_ingreso) VALUES (?, ?, ?)");
            $insert->bind_param("iss", $almacenista_id, $fecha, $hora_ingreso);
            
            if ($insert->execute()) {
                echo "Hora de ingreso registrada correctamente.";
            } else {
                echo "Error al registrar ingreso: " . $connect->error;
            }
        } else {
            echo "Ya hay un registro de ingreso abierto hoy.";
        }
    } else {
        echo "No se encontró el almacenista.";
    }
    
    // Cerrar conexiones
    $stmt->close();
    if (isset($check)) $check->close();
    if (isset($insert)) $insert->close();
} else {
    echo "Acceso no autorizado.";
}
?>