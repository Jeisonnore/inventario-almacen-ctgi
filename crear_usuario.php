<?php
include("../conexion.php");

$usuario = "joan@gmail.com";
$contraseña_plana = "admin123";
$rol = "administrador";

// Encriptar la contraseña
$hash = password_hash($contraseña_plana, PASSWORD_DEFAULT);

// Insertar en la base de datos
$stmt = $connect->prepare("INSERT INTO usuario (usuario, contraseña, rol) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $usuario, $hash, $rol);

if ($stmt->execute()) {
    echo "Usuario creado correctamente.<br>";
    echo "Hash generado: " . $hash;
} else {
    echo "Error al crear el usuario: " . $stmt->error;
}

$stmt->close();
?>

