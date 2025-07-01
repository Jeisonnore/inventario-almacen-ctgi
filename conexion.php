<?php
// Datos de conexión
$servidor = "localhost";
$usuario = "root";
$password = "Control19";  // Asegúrate de que la contraseña sea correcta
$base_datos = "almacen7";  // Asegúrate de que el nombre sea correcto

// Crear conexión con MySQLi
$connect = mysqli_connect($servidor, $usuario, $password, $base_datos);

// Verificar la conexión
if (!$connect) {
    die("La conexión ha fallado: " . mysqli_connect_error());
}

// Configurar la codificación de caracteres
mysqli_set_charset($connect, "utf8");

// echo "Conexión exitosa";  // Mensaje para verificar si la conexión funciona

?>
