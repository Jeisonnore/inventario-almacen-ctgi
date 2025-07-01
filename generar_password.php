<?php
$clave_plana = "admin123"; 
$clave_encriptada = password_hash($clave_plana, PASSWORD_DEFAULT);
echo "Contraseña encriptada: " . $clave_encriptada;
?>
