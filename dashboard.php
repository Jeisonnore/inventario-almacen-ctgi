<?php
// --- dashboard.php (Componente reutilizable) ---

// Este archivo asume que 'conexion.php' ya fue incluido antes de llamarlo.

// --- LÓGICA DE CONTEO PARA LAS TARJETAS ---

// Consulta para contar equipos disponibles
$total_equipos_query = "SELECT COUNT(*) as total FROM equipos WHERE estado = 'disponible'";

// Consulta para sumar materiales con cantidad mayor a 0
$total_materiales_query = "SELECT SUM(cantidad) as total FROM materiales WHERE cantidad > 0";

// Ejecutar consultas
$total_equipos_result = mysqli_query($connect, $total_equipos_query);
$total_materiales_result = mysqli_query($connect, $total_materiales_query);

// Obtener resultados de las consultas
$total_equipos = mysqli_fetch_assoc($total_equipos_result)['total'] ?? 0;
$total_materiales = mysqli_fetch_assoc($total_materiales_result)['total'] ?? 0;

// Sumar equipos y materiales para obtener total de insumos
$total_insumos = $total_equipos + $total_materiales;

// Consulta para contar préstamos activos (equipos y materiales no devueltos)
$prestamos_activos_query = "
    SELECT 
        (SELECT COUNT(*) FROM prestamos_equipos pe LEFT JOIN devolucion_equipos de ON pe.id = de.prestamo_equipo_id WHERE de.id IS NULL) +
        (SELECT COUNT(*) FROM prestamo_materiales pm LEFT JOIN devolucion_materiales dm ON pm.id = dm.prestamo_material_id WHERE dm.id IS NULL)
    AS total_activos
";
$prestamos_activos_result = mysqli_query($connect, $prestamos_activos_query);
$total_prestamos_activos = mysqli_fetch_assoc($prestamos_activos_result)['total_activos'] ?? 0;

// Consulta para contar funcionarios registrados
$total_funcionarios_query = "SELECT COUNT(*) as total FROM instructores";
$total_funcionarios_result = mysqli_query($connect, $total_funcionarios_query);
$total_funcionarios = mysqli_fetch_assoc($total_funcionarios_result)['total'] ?? 0;

// --- NUEVA CONSULTA (SOLO PARA EL ADMIN) ---
// Inicializar almacenistas en 0 por defecto
$total_almacenistas = 0;
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador') {
    // Si el usuario es administrador, contar almacenistas
    $almacenistas_query = "SELECT COUNT(*) as total FROM almacenista";
    $almacenistas_result = mysqli_query($connect, $almacenistas_query);
    $total_almacenistas = mysqli_fetch_assoc($almacenistas_result)['total'] ?? 0;
}
?>

<!-- Agrega la referencia al archivo CSS externo -->
<link rel="stylesheet" href="css/dashboard.css">

<div class="cards">
    <!-- Tarjeta de insumos en stock -->
    <div class="card" data-info="insumos">
        <i class="fas fa-box"></i>
        <h3><?php echo $total_insumos; ?></h3>
        <p>Insumos en stock</p>
    </div>
    <!-- Tarjeta de préstamos activos -->
    <div class="card" data-info="prestamos">
        <i class="fas fa-hand-holding-hand"></i>
        <h3><?php echo $total_prestamos_activos; ?></h3>
        <p>Préstamos activos</p>
    </div>
    <!-- Tarjeta de funcionarios registrados -->
    <div class="card" data-info="funcionarios">
        <i class="fas fa-users"></i>
        <h3><?php echo $total_funcionarios; ?></h3>
        <p>Funcionarios registrados</p>
    </div>

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
    <!-- Tarjeta de almacenistas (solo para administradores) -->
    <div class="card" data-info="almacenistas">
        <i class="fas fa-user-shield"></i>
        <h3><?php echo $total_almacenistas; ?></h3>
        <p>Almacenistas</p>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para mostrar detalles al hacer clic en una tarjeta -->
<div id="infoModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2 id="modalTitle"></h2>
        <div id="modalBody"></div>
    </div>
</div>

<script>
// Script para manejar la apertura y cierre del modal y cargar detalles dinámicamente
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('infoModal');
    if (modal) {
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const closeButton = modal.querySelector('.close-button');
        const cards = document.querySelectorAll('.card');

        // Función para abrir el modal y cargar información vía AJAX
        const openModal = (infoType) => {
            modalTitle.textContent = 'Cargando...';
            modalBody.innerHTML = '<p style="text-align: center;">Por favor, espere.</p>';
            modal.style.display = 'block';
            fetch(`obtener_detalles.php?tipo=${infoType}`)
                .then(response => response.ok ? response.json() : Promise.reject('Error de red'))
                .then(data => {
                    modalTitle.textContent = data.titulo;
                    modalBody.innerHTML = data.html;
                })
                .catch(error => {
                    console.error('Error al cargar los detalles:', error);
                    modalTitle.textContent = 'Error';
                    modalBody.innerHTML = '<p>No se pudo cargar la información.</p>';
                });
        };

        // Asignar evento click a cada tarjeta
        cards.forEach(card => card.addEventListener('click', () => {
            const infoType = card.dataset.info;
            if (infoType) openModal(infoType);
        }));
        
        // Función para cerrar el modal
        const closeModal = () => { modal.style.display = 'none'; };
        closeButton.addEventListener('click', closeModal);
        window.addEventListener('click', (event) => {
            if (event.target == modal) closeModal();
        });
    }
});
</script>