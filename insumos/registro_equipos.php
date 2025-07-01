<?php
include("../conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $marca = trim($_POST['marca']);
    $serie = $_POST['serie'];
    $estado = $_POST['estado'];

    // Validar marca: entre 2 y 50 caracteres (letras, números, espacios, guiones y puntos)
    if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\.\-]{2,50}$/u', $marca)) {
        header("Location: 2insumos.php?error=La marca debe tener entre 2 y 50 caracteres (letras, números, guiones o puntos)");
        exit();
    }

    // Verificar si la serie ya existe
    $verificar = $connect->prepare("SELECT * FROM equipos WHERE serie = ?");
    $verificar->bind_param("s", $serie);
    $verificar->execute();
    $verificar->store_result();

    if ($verificar->num_rows > 0) {
        header("Location: 2insumos.php?error=Ya existe un equipo con esta serie");
        exit();
    }

    // Insertar el nuevo equipo
    $sql = "INSERT INTO equipos (marca, serie, estado) VALUES (?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sss", $marca, $serie, $estado);
    
    if ($stmt->execute()) {
        header("Location: 2insumos.php?var=Equipo registrado con éxito");
    } else {
        header("Location: 2insumos.php?error=Error al registrar el equipo");
    }
    exit();
}
?>

<script>
$(document).ready(function() {
    $('#mitabla').DataTable({
        // ...otras opciones...
        language: {
            url: 'es-ES.json'
        }
    });
});
</script>