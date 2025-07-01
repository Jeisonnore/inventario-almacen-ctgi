<?php
include("../conexion.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $cantidad = (int)$_POST['cantidad'];
    $serie = $_POST['serie'];

    // Comportamiento diferente para consumibles y no consumibles
    if ($tipo == 'consumible') {
        // Registrar en histórico
        $sql_historico = "INSERT INTO historial_materiales (nombre, tipo, cantidad, serie, fecha, movimiento) 
                         VALUES (?, ?, ?, ?, NOW(), 'ingreso')";
        $stmt_historico = $connect->prepare($sql_historico);
        $stmt_historico->bind_param("ssis", $nombre, $tipo, $cantidad, $serie);
        $stmt_historico->execute();

        // Actualizar stock (sumar cantidades)
        $verificar = $connect->prepare("SELECT * FROM materiales WHERE nombre = ? AND tipo = ?");
        $verificar->bind_param("ss", $nombre, $tipo);
        $verificar->execute();
        $resultado = $verificar->get_result();
        
        if ($resultado->num_rows > 0) {
            $material = $resultado->fetch_assoc();
            $nueva_cantidad = $material['cantidad'] + $cantidad;
            
            $actualizar = $connect->prepare("UPDATE materiales SET cantidad = ? WHERE id = ?");
            $actualizar->bind_param("ii", $nueva_cantidad, $material['id']);
            
            if ($actualizar->execute()) {
                echo "<!DOCTYPE html>
                <html><head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head><body>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Material consumible actualizado (Sumadas $cantidad unidades)'
                    }).then(() => { window.location='2insumos.php'; });
                });
                </script>
                </body></html>";
            } else {
                echo "<!DOCTYPE html>
                <html><head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head><body>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al actualizar material consumible'
                    }).then(() => { window.location='2insumos.php'; });
                });
                </script>
                </body></html>";
            }
        } else {
            $sql = "INSERT INTO materiales (nombre, tipo, cantidad, serie, estado) VALUES (?, ?, ?, ?, 'disponible')";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ssis", $nombre, $tipo, $cantidad, $serie);
            
            if ($stmt->execute()) {
                echo "<!DOCTYPE html>
                <html><head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head><body>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Nuevo material consumible registrado'
                    }).then(() => { window.location='2insumos.php'; });
                });
                </script>
                </body></html>";
            } else {
                echo "<!DOCTYPE html>
                <html><head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head><body>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al registrar material consumible'
                    }).then(() => { window.location='2insumos.php'; });
                });
                </script>
                </body></html>";
            }
        }
    } else { // Para no consumibles
        // Verificar solo por serie (no por nombre)
        $verificar = $connect->prepare("SELECT * FROM materiales WHERE serie = ? AND tipo = 'no consumible'");
        $verificar->bind_param("s", $serie);
        $verificar->execute();
        $verificar->store_result();
        
        if ($verificar->num_rows > 0) {
            echo "<!DOCTYPE html>
            <html><head>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head><body>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: Ya existe un material no consumible con esta serie'
                }).then(() => { window.location='2insumos.php'; });
            });
            </script>
            </body></html>";
        } else {
            // Insertar como nuevo registro (cantidad siempre será 1 para no consumibles)
            $sql = "INSERT INTO materiales (nombre, tipo, cantidad, serie, estado) VALUES (?, ?, 1, ?, 'disponible')";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("sss", $nombre, $tipo, $serie);
            
            if ($stmt->execute()) {
                // Registrar en histórico
                $nuevo_id = $connect->insert_id;
                $sql_historial = "INSERT INTO historial_materiales 
                                 (material_id, nombre, tipo, cantidad, serie, fecha, movimiento) 
                                 VALUES (?, ?, ?, 1, ?, NOW(), 'ingreso')";
                $stmt_historial = $connect->prepare($sql_historial);
                $stmt_historial->bind_param("isss", $nuevo_id, $nombre, $tipo, $serie);
                $stmt_historial->execute();
                
                echo "<!DOCTYPE html>
                <html><head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head><body>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Material no consumible registrado con éxito'
                    }).then(() => { window.location='2insumos.php'; });
                });
                </script>
                </body></html>";
            } else {
                echo "<!DOCTYPE html>
                <html><head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head><body>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al registrar material no consumible'
                    }).then(() => { window.location='2insumos.php'; });
                });
                </script>
                </body></html>";
            }
        }
    }
    exit();
}
?>