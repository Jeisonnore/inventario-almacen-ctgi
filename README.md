# 📦 Sistema de Inventario Almacén CTGI


**Repositorio en GitHub:**  
🔗 [https://github.com/Jeisonnore/inventario-almacen-ctgi.git](https://github.com/Jeisonnore/inventario-almacen-ctgi.git)

**Versión en línea (Hostinger):**  
🌐 [https://gold-zebra-473949.hostingersite.com/](https://gold-zebra-473949.hostingersite.com/)

**Demo en GitHub Pages (solo frontend):**  
🧪 [[https://jeisonnore.github.io/inventario-almacen-ctgi/]

---

## 🎯 Descripción

Sistema web desarrollado como proyecto formativo del SENA para el control de inventario del **Centro Textil y de Gestión Industrial (CTGI)**.  
Permite administrar de forma centralizada el préstamo, devolución y seguimiento de equipos, materiales, ambientes, instructores y novedades técnicas.


---

## 🚀 Funcionalidades

- Panel de administración con permisos por rol (administrador, almacenista, instructor)  
- Registro, edición y eliminación de productos, categorías y ambientes  
- Gestión de préstamos y devoluciones con seguimiento de usuarios  
- Registro de novedades: fallas, solicitudes técnicas y control de auditorías  
- Generación de reportes en PDF y Excel  
- Búsqueda dinámica, paginación y filtros con DataTables  
- Autenticación segura y recuperación de contraseña  
- Diseño responsive basado en Bootstrap 5  

---

## 🛠 Tecnologías

| Capa         | Tecnologías                    |
|--------------|-------------------------------|
| **Frontend** | HTML5 · CSS3 · JavaScript · Bootstrap 5 · DataTables · SweetAlert2 |
| **Backend**  | PHP (vanilla) · MySQL          |
| **Herramientas** | XAMPP · Git · MySQL Workbench  |

---

## 📁 Estructura del proyecto

inventario-almacen-ctgi/
├── css/ # Estilos personalizados
├── js/ # Scripts del sistema
├── modulos/ # Funciones por módulo (equipos, préstamos, reportes…)
├── reportes/ # Generación de reportes e informes
├── img/ # Imágenes e íconos del sistema
├── conexion.php # Conexión a la base de datos
├── index.php # Página de login y punto de entrada
├── README.md # Documentación
└── LICENSE # Licencia MIT y colaboradores


---

## 🧭 Instalación y ejecución

1. Clona este repositorio:
git clone https://github.com/Jeisonnore/inventario-almacen-ctgi.git
cd inventario-almacen-ctgi
2. Instala **XAMPP** (incluye Apache, PHP y MySQL).
3. Copia la carpeta en `htdocs` (Windows) o directorio equivalente.
4. Ingresa a [phpMyAdmin] y crea la base de datos:
- Nombre: `almacen3`
- Importa el archivo `.sql` si está disponible.
5. Edita `conexion.php` para ajustar tus credenciales de MySQL.
6. Accede desde el navegador:

http://localhost/inventario-almacen-ctgi/

---

## 👥 Colaboradores

Desarrollado por aprendices del SENA:

- **Jeison Noreña Monsalve**  
- **Carlos Velásquez**  
- **Georgette Osuna**  
- **Jhonatan Sebastián Serna Ramírez**  
- **Juan Camilo Carmona García**

---

## 📝 Licencia

Este proyecto está bajo la licencia **MIT**. Consulta el archivo `LICENSE` para más detalles.

---

## 📌 Estado y mejoras futuras

✔️ **Estado actual**: funcional y en uso formativo.  
🔧 **Próximas mejoras**: integración de autenticación más robusta (OAuth), historial detallado de transacciones, roles más avanzados y optimización general del backend.

---
## 📝 Licencia

Este proyecto está bajo la licencia **MIT**.

Los derechos de autor pertenecen a:

- **Jeison Noreña Monsalve**
- **Carlos Velasquez**
- **Georgette Osuna**

📄 Consulta la licencia personalizada de este sistema en [`LICENSE`](./LICENSE). **Solo lectura.**


📩 Para consultas técnicas o legales: **jeisonnorena@gmail.com**


---

## 📬 Contacto

Para soporte, sugerencias o colaboración, contáctanos a través del repositorio o con tu instructor del SENA CTGI.
