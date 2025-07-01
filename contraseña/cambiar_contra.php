<?php
include '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = trim($_POST['token']);
    $nueva_contra = password_hash($_POST['nueva_contra'], PASSWORD_DEFAULT);

    // Verificar que el token sea válido y no haya expirado
    $stmt_verificar = $connect->prepare("SELECT id FROM usuario WHERE token_recuperacion = ? AND token_expiracion > NOW()");
    $stmt_verificar->bind_param("s", $token);
    $stmt_verificar->execute();
    $resultado = $stmt_verificar->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        $usuario_id = $usuario['id'];

        // Actualizar contraseña y limpiar el token
        $stmt_actualizar = $connect->prepare("UPDATE usuario SET contraseña = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE id = ?");
        $stmt_actualizar->bind_param("si", $nueva_contra, $usuario_id);
        if ($stmt_actualizar->execute()) {
            echo '<div style="text-align:center;margin-top:50px">✅ Contraseña actualizada correctamente. <br><a href="../index.php">Iniciar sesión</a></div>';
        } else {
            echo "❌ Error al actualizar la contraseña.";
        }
    } else {
        echo "⚠️ Token inválido o expirado.";
    }
}
?>
