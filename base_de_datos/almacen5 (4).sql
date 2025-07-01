-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-07-2025 a las 13:49:01
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `almacen5`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacenista`
--

CREATE TABLE `almacenista` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `cedula` varchar(20) NOT NULL DEFAULT 'NOT NULL UNIQUE' COMMENT 'Número de cédula único del almacenista',
  `correo` varchar(255) NOT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL COMMENT 'Estado laboral: activo o inactivo',
  `hora_ingreso` datetime DEFAULT NULL,
  `hora_salida` datetime DEFAULT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Relación con la tabla de usuarios para autenticación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Registro de empleados del almacén con información personal';

--
-- Volcado de datos para la tabla `almacenista`
--

INSERT INTO `almacenista` (`id`, `nombre`, `apellido`, `cedula`, `correo`, `telefono`, `estado`, `hora_ingreso`, `hora_salida`, `usuario_id`) VALUES
(2, 'Carlos', 'Velasquez', '3217298534', 'carlos@gmail.com', '888846688', 'activo', '2025-06-18 15:29:48', NULL, 3),
(3, 'JEISON', 'NOREÑA', '1017240266', 'jei-son-2503@hotmail.com', '8888466', 'activo', '2025-06-18 15:30:57', NULL, 4),
(4, 'mario', 'cardona', '1025364542', 'jei142563@gmail.com', '888846615', 'activo', '2025-06-19 10:35:50', '2025-06-19 10:41:17', 5),
(5, 'Vanesa', 'sanchez', '159753456', 'vanesa@gmail.com', '1597534568', 'activo', '2025-06-18 09:59:17', '2025-06-18 23:05:14', 6),
(7, 'joan', 'm', '3102656455', 'joanm5507@gmail.com', '42454545', 'activo', '2025-06-24 11:47:45', '2025-06-24 11:47:48', 8),
(11, ',m,mk,m', 'njnmj', '10172402', '3@gmail.com', '56544545', 'activo', '2025-06-24 13:21:09', '2025-06-24 13:21:54', 12),
(12, 'JEISON', 'monsalve', '889966', 'jeisonnorena264@gmail.com', '6465456', 'activo', '2025-08-02 18:48:20', '2025-08-02 20:49:17', 13),
(13, 'jorge', 'sanchez', '545643135', 'jj@gmail.com', '88884665', 'inactivo', '2025-06-24 13:27:42', '2025-06-24 13:34:47', 14),
(14, 'mama', 'mm', '654683', 'jei-son-12@hotmail.com', '35446635', 'inactivo', '2025-07-02 15:38:07', '2025-07-02 15:38:10', 15),
(16, 'mario', 'cardona', '3513541', 'carlos658velasquez25@gmail.com', '888555', 'inactivo', '2025-06-26 18:01:24', '2025-06-26 18:03:22', 17),
(20, 'maria', 'cifuentes', '90027504', 'mueito1996@gmail.com', '88884661', '', '2025-07-04 06:48:07', '2025-07-02 15:37:59', 21);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devolucion_equipos`
--

CREATE TABLE `devolucion_equipos` (
  `id` int(11) NOT NULL,
  `estado_devolucion` enum('bueno','deteriorado') NOT NULL COMMENT 'Condición del equipo al devolver: bueno o deteriorado',
  `fecha_devolucion` datetime NOT NULL,
  `observaciones` text DEFAULT NULL,
  `prestamo_equipo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Registro de devoluciones de equipos prestados';

--
-- Volcado de datos para la tabla `devolucion_equipos`
--

INSERT INTO `devolucion_equipos` (`id`, `estado_devolucion`, `fecha_devolucion`, `observaciones`, `prestamo_equipo_id`) VALUES
(1, 'bueno', '2025-06-18 09:31:00', '', 3),
(2, 'bueno', '2025-06-18 09:31:00', '', 4),
(4, 'bueno', '2025-06-18 10:54:00', 'sin cargador', 6),
(7, 'bueno', '2025-06-18 10:55:00', 'sin observacion', 9),
(10, 'bueno', '2025-06-27 08:04:00', '', 10),
(11, 'bueno', '2025-06-27 08:04:00', '', 11),
(16, 'deteriorado', '2025-06-29 15:22:00', 'pantalla rayada y con mugre', 18),
(17, 'bueno', '2025-06-29 15:22:00', 'muy bueno ', 21),
(18, 'bueno', '2025-06-29 15:43:00', 'hola', 22),
(19, 'bueno', '2025-06-29 15:43:00', 'hoal', 23),
(20, 'bueno', '2025-06-29 15:48:00', 'jjjja', 24);

--
-- Disparadores `devolucion_equipos`
--
DELIMITER $$
CREATE TRIGGER `actualizar_estado_equipo_devuelto` AFTER INSERT ON `devolucion_equipos` FOR EACH ROW BEGIN
  UPDATE equipos
  SET estado = CASE 
    WHEN NEW.estado_devolucion = 'bueno' THEN 'disponible'
    WHEN NEW.estado_devolucion = 'deteriorado' THEN 'deteriorado'
  END
  WHERE id = (
    SELECT equipo_id
    FROM prestamos_equipos
    WHERE id = NEW.prestamo_equipo_id
  );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devolucion_materiales`
--

CREATE TABLE `devolucion_materiales` (
  `id` int(11) NOT NULL,
  `fecha_devolucion` datetime NOT NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `condicion_entrega` enum('bueno','deteriorado') DEFAULT NULL COMMENT 'Condición del material al devolver: bueno o deteriorado',
  `prestamo_material_id` int(11) NOT NULL,
  `almacenista_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de devoluciones de materiales prestados';

--
-- Volcado de datos para la tabla `devolucion_materiales`
--

INSERT INTO `devolucion_materiales` (`id`, `fecha_devolucion`, `observaciones`, `cantidad`, `condicion_entrega`, `prestamo_material_id`, `almacenista_id`) VALUES
(18, '2025-06-30 16:21:00', 'sin pilas', 1, 'bueno', 45, 2);

--
-- Disparadores `devolucion_materiales`
--
DELIMITER $$
CREATE TRIGGER `actualizar_materiales_devolucion` AFTER INSERT ON `devolucion_materiales` FOR EACH ROW BEGIN
    DECLARE material_tipo VARCHAR(20);
    DECLARE material_id INT;
    DECLARE prestamo_cantidad INT;
    
    SELECT m.tipo, m.id, pm.cantidad 
    INTO material_tipo, material_id, prestamo_cantidad
    FROM materiales m
    JOIN prestamo_materiales pm ON m.id = pm.material_id
    WHERE pm.id = NEW.prestamo_material_id;
    
    IF material_tipo = 'consumible' THEN
        
        SET @dummy = 0; 
    ELSE
        
        IF NEW.condicion_entrega = 'bueno' THEN
            UPDATE materiales SET estado = 'disponible' WHERE id = material_id;
        ELSE
            UPDATE materiales SET estado = 'deteriorado' WHERE id = material_id;
        END IF;
    END IF;
    
    
    INSERT INTO historial_materiales (material_id, nombre, tipo, cantidad, serie, fecha, movimiento, cambios)
    SELECT 
        id, 
        nombre, 
        tipo, 
        prestamo_cantidad, 
        serie, 
        NOW(), 
        'entrada', 
        CONCAT('Devolución de material. Condición: ', NEW.condicion_entrega)
    FROM materiales 
    WHERE id = material_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id` int(11) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `serie` varchar(50) NOT NULL COMMENT 'Número de serie único del equipo',
  `estado` enum('disponible','deteriorado','prestado','fuera de servicio') DEFAULT NULL COMMENT 'Estado actual: disponible, prestado, deteriorado o fuera de servicio',
  `fecha_ingreso` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Inventario de equipos tecnológicos del almacén';

--
-- Volcado de datos para la tabla `equipos`
--

INSERT INTO `equipos` (`id`, `marca`, `serie`, `estado`, `fecha_ingreso`) VALUES
(6, 'aspire', '6548641234', 'deteriorado', '2025-06-18 13:58:13'),
(8, 'Aspire', '159753456', 'disponible', '2025-06-18 14:29:37'),
(31, 'aspire', '12365466', 'prestado', '2025-06-29 19:46:58'),
(32, 'aspire', '1236969656', 'disponible', '2025-06-29 20:19:10');

--
-- Disparadores `equipos`
--
DELIMITER $$
CREATE TRIGGER `registrar_ingreso_equipo` AFTER INSERT ON `equipos` FOR EACH ROW BEGIN
    
    IF NOT EXISTS (
        SELECT 1 FROM historial_equipos 
        WHERE serie = NEW.serie AND movimiento = 'ingreso'
    ) THEN
        INSERT INTO historial_equipos (
            equipo_id, 
            marca, 
            serie, 
            estado, 
            fecha, 
            movimiento, 
            cambios
        ) VALUES (
            NEW.id,
            NEW.marca, 
            NEW.serie, 
            NEW.estado, 
            NOW(), 
            'ingreso', 
            NULL
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_equipos`
--

CREATE TABLE `historial_equipos` (
  `id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `serie` varchar(50) NOT NULL,
  `estado` varchar(30) NOT NULL,
  `fecha` datetime NOT NULL,
  `movimiento` enum('ingreso','edicion','eliminado','prestamo','devolucion') DEFAULT NULL,
  `cambios` text DEFAULT NULL COMMENT 'Detalles de lo que se modificó en ediciones'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_equipos`
--

INSERT INTO `historial_equipos` (`id`, `equipo_id`, `marca`, `serie`, `estado`, `fecha`, `movimiento`, `cambios`) VALUES
(10, 6, 'aspire', '6548641234', 'disponible', '2025-06-18 08:58:13', 'ingreso', NULL),
(15, 8, 'Aspire', '159753456', 'disponible', '2025-06-18 09:29:37', 'ingreso', NULL),
(16, 6, 'aspire', '6548641234', 'prestado', '2025-06-18 09:30:28', 'prestamo', NULL),
(17, 8, 'Aspire', '159753456', 'prestado', '2025-06-18 09:30:28', 'prestamo', NULL),
(19, 6, 'aspire', '6548641234', 'bueno', '2025-06-18 09:31:30', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(20, 8, 'Aspire', '159753456', 'bueno', '2025-06-18 09:31:30', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(24, 8, 'Aspire', '159753456', 'prestado', '2025-06-18 09:52:17', 'prestamo', NULL),
(27, 6, 'aspire', '6548641234', 'prestado', '2025-06-18 09:53:05', 'prestamo', NULL),
(28, 8, 'Aspire', '159753456', 'bueno', '2025-06-18 09:54:29', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(31, 6, 'aspire', '6548641234', 'bueno', '2025-06-18 09:55:44', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(32, 6, 'aspire', '6548641234', 'prestado', '2025-06-19 11:06:04', 'prestamo', NULL),
(33, 8, 'Aspire', '159753456', 'prestado', '2025-06-19 11:06:04', 'prestamo', NULL),
(46, 6, 'aspire', '6548641234', 'bueno', '2025-06-27 08:04:41', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(47, 8, 'Aspire', '159753456', 'bueno', '2025-06-27 08:04:41', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(56, 6, 'aspire', '6548641234', 'prestado', '2025-06-27 10:10:12', 'prestamo', 'Préstamo de equipo'),
(59, 8, 'Aspire', '159753456', 'prestado', '2025-06-27 11:25:00', 'prestamo', 'Préstamo de equipo'),
(158, 31, 'aspire', '12365466', 'disponible', '2025-06-29 14:46:58', 'ingreso', NULL),
(160, 31, 'aspire', '12365466', 'prestado', '2025-06-29 15:09:00', 'prestamo', 'Préstamo de equipo'),
(161, 32, 'aspire', '1236969656', 'disponible', '2025-06-29 15:19:10', 'ingreso', NULL),
(162, 32, 'aspire', '1236969656', 'prestado', '2025-06-29 15:20:21', 'prestamo', 'Préstamo de equipo'),
(163, 6, 'aspire', '6548641234', 'deteriorado', '2025-06-29 15:23:24', 'devolucion', 'Devolución de equipo. Condición: deteriorado'),
(164, 8, 'Aspire', '159753456', 'bueno', '2025-06-29 15:23:24', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(165, 31, 'aspire', '12365466', 'bueno', '2025-06-29 15:44:36', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(166, 32, 'aspire', '1236969656', 'bueno', '2025-06-29 15:44:36', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(167, 8, 'Aspire', '159753456', 'prestado', '2025-06-29 15:48:16', 'prestamo', 'Préstamo de equipo'),
(168, 31, 'aspire', '12365466', 'prestado', '2025-06-29 15:48:16', 'prestamo', 'Préstamo de equipo'),
(169, 8, 'Aspire', '159753456', 'bueno', '2025-06-29 15:48:52', 'devolucion', 'Devolución de equipo. Condición: bueno'),
(170, 31, '', '', 'disponible', '2025-06-29 16:22:17', 'eliminado', 'Préstamo eliminado, equipo marcado como disponible'),
(171, 8, 'Aspire', '159753456', 'prestado', '2025-06-30 15:53:56', 'prestamo', 'Préstamo de equipo'),
(172, 31, 'aspire', '12365466', 'prestado', '2025-06-30 15:53:56', 'prestamo', 'Préstamo de equipo'),
(173, 32, 'aspire', '1236969656', 'prestado', '2025-06-30 15:53:56', 'prestamo', 'Préstamo de equipo'),
(174, 8, '', '', 'disponible', '2025-06-30 15:56:21', 'eliminado', 'Préstamo ID 26 eliminado, equipo marcado como disponible'),
(175, 32, '', '', 'disponible', '2025-06-30 15:56:45', 'eliminado', 'Préstamo ID 28 eliminado, equipo marcado como disponible'),
(176, 31, '', '', 'disponible', '2025-06-30 15:56:54', 'eliminado', 'Préstamo ID 27 eliminado, equipo marcado como disponible'),
(177, 8, 'Aspire', '159753456', 'prestado', '2025-06-30 16:20:41', 'prestamo', 'Préstamo de equipo'),
(178, 31, 'aspire', '12365466', 'prestado', '2025-06-30 16:20:41', 'prestamo', 'Préstamo de equipo'),
(179, 32, 'aspire', '1236969656', 'prestado', '2025-06-30 16:20:41', 'prestamo', 'Préstamo de equipo'),
(180, 8, '', '', 'disponible', '2025-06-30 16:53:42', 'eliminado', 'Préstamo ID 29 eliminado, equipo marcado como disponible'),
(181, 32, '', '', 'disponible', '2025-06-30 16:53:51', 'eliminado', 'Préstamo ID 31 eliminado, equipo marcado como disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_materiales`
--

CREATE TABLE `historial_materiales` (
  `id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('consumible','no consumible') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `serie` varchar(255) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `movimiento` enum('ingreso','edicion','eliminado','salida','prestamo','entrada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cambios` text DEFAULT NULL COMMENT 'Detalles de lo que se modificó en ediciones'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_materiales`
--

INSERT INTO `historial_materiales` (`id`, `material_id`, `nombre`, `tipo`, `cantidad`, `serie`, `fecha`, `movimiento`, `cambios`) VALUES
(1, NULL, 'lapicero', 'consumible', 1, '6548641234', '2025-06-18 08:35:58', 'ingreso', NULL),
(2, NULL, 'lapicero', 'consumible', 1, '123696955', '2025-06-18 08:36:58', 'ingreso', NULL),
(3, NULL, 'Borrador', 'consumible', 1, '7523852', '2025-06-18 08:37:41', 'ingreso', NULL),
(4, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-18 08:38:07', 'ingreso', NULL),
(5, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-18 08:41:17', 'ingreso', NULL),
(6, 2, 'Borrador', 'consumible', 1, NULL, '2025-06-18 08:44:13', 'salida', NULL),
(7, 3, 'Borrador', 'no consumible', 1, NULL, '2025-06-18 08:44:13', 'salida', NULL),
(8, 1, 'lapicero', 'consumible', 2, NULL, '2025-06-18 08:44:44', 'salida', NULL),
(9, 4, 'Marcador', 'no consumible', 1, NULL, '2025-06-18 08:44:44', 'salida', NULL),
(10, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-18 09:33:11', 'entrada', 'Devolución de material. Condición: bueno'),
(11, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-18 09:33:11', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(12, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-18 09:33:11', 'entrada', 'Devolución de material. Condición: bueno'),
(13, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-18 09:33:11', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(14, NULL, 'Libro Tic', 'consumible', 10, '1597545645', '2025-06-18 09:50:37', 'ingreso', NULL),
(15, 6, 'Resmas', 'no consumible', 1, '456789258', '2025-06-18 09:51:19', 'ingreso', NULL),
(16, 3, 'Borrador', 'no consumible', 1, NULL, '2025-06-18 09:52:51', 'salida', NULL),
(17, 4, 'Marcador', 'no consumible', 1, NULL, '2025-06-18 09:52:51', 'salida', NULL),
(18, 6, 'Resmas', 'no consumible', 1, NULL, '2025-06-18 09:52:51', 'salida', NULL),
(19, 5, 'Libro Tic', 'consumible', 10, NULL, '2025-06-18 09:53:14', 'salida', NULL),
(20, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-18 09:55:03', 'entrada', 'Devolución de material. Condición: bueno'),
(21, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-18 09:55:03', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(22, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-18 09:55:03', 'entrada', 'Devolución de material. Condición: bueno'),
(23, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-18 09:55:03', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(24, 6, 'Resmas', 'no consumible', 1, '456789258', '2025-06-18 09:55:03', 'entrada', 'Devolución de material. Condición: bueno'),
(25, 6, 'Resmas', 'no consumible', 1, '456789258', '2025-06-18 09:55:03', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(26, NULL, 'bombillo', 'consumible', 5, '564681635', '2025-06-27 22:06:16', 'ingreso', NULL),
(27, 8, 'tv', 'no consumible', 1, '2443545', '2025-06-27 22:07:07', 'ingreso', NULL),
(28, NULL, 'bombillo', 'consumible', 10, '62345835', '2025-06-27 22:07:37', 'ingreso', NULL),
(29, 3, 'Borrador', 'no consumible', 1, NULL, '2025-06-27 22:11:27', 'salida', NULL),
(30, 4, 'Marcador', 'no consumible', 1, NULL, '2025-06-27 22:11:27', 'salida', NULL),
(31, 7, 'bombillo', 'consumible', 5, NULL, '2025-06-27 22:12:50', 'salida', NULL),
(32, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:10:08', 'entrada', 'Devolución de material. Condición: bueno'),
(33, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:10:08', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(34, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-27 08:10:08', 'entrada', 'Devolución de material. Condición: bueno'),
(35, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-27 08:10:08', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(36, 9, 'tv', 'no consumible', 1, '4562', '2025-06-27 08:30:40', 'ingreso', NULL),
(37, NULL, 'Borrador', 'consumible', 1, '65486412344', '2025-06-27 08:31:42', 'ingreso', NULL),
(38, NULL, 'borrador', 'consumible', 1, '123654', '2025-06-27 08:32:51', 'ingreso', NULL),
(39, 3, 'Borrador', 'consumible', 1, '12123132132', '2025-06-27 08:33:05', 'edicion', 'Edición: Nombre=Borrador, Tipo=consumible, Cantidad=1, Serie=12123132132, Estado=disponible'),
(40, 3, 'Borrador', 'consumible', 5, '12123132132', '2025-06-27 08:33:19', 'edicion', 'Edición: Nombre=Borrador, Tipo=consumible, Cantidad=5, Serie=12123132132, Estado=disponible'),
(41, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:33:48', 'edicion', 'Edición: Nombre=Borrador, Tipo=no consumible, Cantidad=1, Serie=12123132132, Estado=disponible'),
(42, 3, 'Borrador', 'no consumible', 1, NULL, '2025-06-27 08:38:04', 'salida', NULL),
(43, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:39:29', 'entrada', 'Devolución de material. Condición: bueno'),
(44, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:39:29', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(45, 3, 'Borrador', 'no consumible', 1, NULL, '2025-06-27 08:40:58', 'salida', NULL),
(46, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:41:19', 'entrada', 'Devolución de material. Condición: bueno'),
(47, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:41:19', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(48, 3, 'Borrador', 'no consumible', 1, NULL, '2025-06-27 08:44:09', 'salida', NULL),
(49, 4, 'Marcador', 'no consumible', 1, NULL, '2025-06-27 08:44:09', 'salida', NULL),
(50, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:48:09', 'entrada', 'Devolución de material. Condición: bueno'),
(51, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-27 08:48:09', 'entrada', 'Devolución de material. Cantidad: 1, Condición: bueno'),
(52, NULL, 'lapiz', 'consumible', 10, '545345454', '2025-06-27 09:51:09', 'ingreso', NULL),
(53, NULL, 'lapiz', 'consumible', 1, '6465454', '2025-06-27 09:51:45', 'ingreso', NULL),
(55, 7, 'bombillo', 'consumible', 5, NULL, '2025-06-27 10:14:04', 'salida', NULL),
(56, 7, 'bombillo', 'consumible', 1, NULL, '2025-06-27 10:14:28', 'salida', NULL),
(57, 6, 'Resmas', 'no consumible', 1, '456789258', '2025-06-27 10:49:47', 'salida', 'Préstamo de material.'),
(58, 8, 'tv', 'no consumible', 1, '2443545', '2025-06-27 10:50:09', 'salida', 'Préstamo de material.'),
(59, 2, 'Borrador', 'consumible', 2, '7523852', '2025-06-27 10:51:47', 'salida', 'Préstamo de material.'),
(60, 6, 'Resmas', 'no consumible', 1, '456789258', '2025-06-27 10:55:27', 'entrada', 'Devolución de material. Condición: bueno'),
(61, 7, 'bombillo', 'consumible', 3, '564681635', '2025-06-27 11:13:26', 'salida', NULL),
(62, 6, 'Resmas', 'no consumible', 1, '456789258', '2025-06-27 11:17:44', 'salida', 'Préstamo de material'),
(63, 9, 'tv', 'no consumible', 1, '4562', '2025-06-27 11:25:13', 'salida', 'Préstamo de material'),
(64, 6, 'Resmas', 'no consumible', 1, '456789258', '2025-06-27 11:38:51', 'entrada', 'Devolución de material. Condición: bueno'),
(65, 9, 'tv', 'no consumible', 1, '4562', '2025-06-27 13:57:39', 'entrada', 'Devolución de material. Condición: bueno'),
(66, 8, 'tv', 'no consumible', 1, '2443545', '2025-06-27 14:06:34', 'entrada', 'Devolución de material. Condición: bueno'),
(67, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-27 14:08:18', 'entrada', 'Devolución de material. Condición: deteriorado'),
(68, NULL, 'JEISON', 'consumible', 1, '5135435145', '2025-06-28 19:06:17', 'ingreso', NULL),
(71, 11, 'JEISON', 'consumible', 1, '5135435145', '2025-06-28 19:30:56', 'eliminado', NULL),
(72, 10, 'lapiz 2', 'consumible', 11, '545345454', '2025-06-28 19:31:59', 'eliminado', NULL),
(73, 9, 'tv', 'no consumible', 1, '4562', '2025-06-28 19:50:26', 'eliminado', NULL),
(74, 9, 'tv', 'no consumible', 1, '4562', '2025-06-28 19:56:34', 'eliminado', NULL),
(75, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-29 11:40:36', 'eliminado', NULL),
(76, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-29 11:43:21', 'eliminado', NULL),
(77, 3, 'Borrador', 'no consumible', 1, '12123132132', '2025-06-29 11:49:23', 'eliminado', NULL),
(78, 1, 'lapicero', 'consumible', 0, '6548641234', '2025-06-29 11:49:29', 'eliminado', NULL),
(79, 8, 'tv', 'no consumible', 1, '2443545', '2025-06-29 11:50:44', 'eliminado', NULL),
(80, 12, 'calculadora', 'no consumible', 1, '566666', '2025-06-29 11:53:29', 'ingreso', NULL),
(81, NULL, 'colores', 'consumible', 4, '665656', '2025-06-29 11:54:45', 'ingreso', NULL),
(82, NULL, 'colores', 'consumible', 5, '8888883', '2025-06-29 11:56:43', 'ingreso', NULL),
(83, 1, 'lapicero', 'consumible', 0, '6548641234', '2025-06-29 12:04:00', 'eliminado', NULL),
(84, 1, 'lapicero', 'consumible', 0, '6548641234', '2025-06-29 12:09:56', 'eliminado', NULL),
(85, 6, 'Resmas', 'no consumible', 1, '456789258', '2025-06-29 12:10:25', 'eliminado', NULL),
(86, 5, 'Libro Tic', 'consumible', 0, '1597545645', '2025-06-29 12:14:34', 'eliminado', NULL),
(87, 9, 'tv', 'no consumible', 1, '4562', '2025-06-29 12:14:41', 'eliminado', NULL),
(88, 2, 'Borrador', 'consumible', 0, '7523852', '2025-06-29 12:17:23', 'eliminado', 'Eliminación manual'),
(89, 12, 'calculadora', 'no consumible', 1, '566666', '2025-06-29 12:17:34', 'eliminado', 'Eliminación manual'),
(90, 7, 'bombillo', 'consumible', 1, '564681635', '2025-06-29 12:29:16', 'eliminado', NULL),
(91, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-29 12:29:41', 'edicion', 'Edición: Nombre=Marcador, Tipo=no consumible, Cantidad=1, Serie=1597534, Estado=disponible'),
(92, 4, 'Marcador', 'no consumible', 1, '1597534', '2025-06-29 12:29:58', 'eliminado', NULL),
(93, 13, 'colores', 'consumible', 9, '665656', '2025-06-29 12:38:45', 'eliminado', 'Eliminación manual'),
(94, NULL, 'lapicero', 'consumible', 4, '1236969', '2025-06-29 12:40:02', 'ingreso', NULL),
(95, 15, 'video vi', 'no consumible', 1, '1236547', '2025-06-29 12:40:16', 'ingreso', NULL),
(96, 16, 'cartulina', 'no consumible', 1, '3545453', '2025-06-29 13:09:49', 'ingreso', NULL),
(97, 15, 'video vi', 'no consumible', 1, '1236547', '2025-06-29 13:17:33', 'eliminado', NULL),
(98, 14, 'lapicero', 'consumible', 4, '1236969', '2025-06-29 13:17:40', 'eliminado', NULL),
(99, 16, 'cartulina', 'no consumible', 1, '3545453', '2025-06-29 14:37:41', 'eliminado', NULL),
(100, NULL, 'lapicero', 'consumible', 2, '34654', '2025-06-29 14:40:25', 'ingreso', NULL),
(101, 18, 'video vit', 'no consumible', 1, '6546846', '2025-06-29 14:44:16', 'ingreso', NULL),
(102, 17, 'lapicero', 'consumible', 2, '34654', '2025-06-29 14:47:56', 'eliminado', NULL),
(103, 18, 'video vit', 'no consumible', 1, '6546846', '2025-06-29 14:48:13', 'eliminado', NULL),
(104, NULL, 'lapiz', 'consumible', 1, '6548641234', '2025-06-29 14:50:19', 'ingreso', NULL),
(105, 20, 'video vi', 'no consumible', 1, '1236546', '2025-06-29 15:09:42', 'ingreso', NULL),
(106, 19, 'lapiz', 'consumible', 1, '6548641234', '2025-06-29 15:12:35', 'salida', NULL),
(107, 20, 'video vi', 'no consumible', 1, '1236546', '2025-06-29 15:12:35', 'salida', 'Préstamo de material'),
(108, 20, 'video vi', 'no consumible', 1, '1236546', '2025-06-29 15:30:49', 'entrada', 'Devolución de material. Condición: deteriorado'),
(109, NULL, 'lapicero', 'consumible', 10, '5464435', '2025-06-29 15:32:01', 'ingreso', NULL),
(110, 21, 'lapicero', 'consumible', 5, '5464435', '2025-06-29 15:32:26', 'salida', NULL),
(111, 20, 'video vi', 'consumible', 1, '1236546', '2025-06-29 15:50:16', 'edicion', 'Edición: Nombre=video vi, Tipo=consumible, Cantidad=1, Serie=1236546, Estado=deteriorado'),
(112, 20, 'video vi', 'consumible', 1, '1236546', '2025-06-29 15:50:52', 'edicion', 'Edición: Nombre=video vi, Tipo=consumible, Cantidad=1, Serie=1236546, Estado=disponible'),
(113, 20, 'video vi', 'consumible', 1, '1236546', '2025-06-29 15:51:08', 'salida', NULL),
(114, 22, 'gafas', 'no consumible', 1, '6515614515', '2025-06-29 15:52:03', 'ingreso', NULL),
(115, 22, 'gafas', 'no consumible', 1, '6515614515', '2025-06-29 15:52:23', 'salida', 'Préstamo de material'),
(116, 22, 'gafas', 'no consumible', 1, '6515614515', '2025-06-29 15:52:50', 'entrada', 'Devolución de material. Condición: bueno'),
(117, 23, 'hdmi', 'no consumible', 1, '6453465', '2025-06-30 15:54:52', 'ingreso', NULL),
(118, 24, 'control  de aire acondicionado', 'no consumible', 1, '888888546', '2025-06-30 15:55:41', 'ingreso', NULL),
(119, 24, 'control  de aire acondicionado', 'no consumible', 1, '888888546', '2025-06-30 15:56:07', 'salida', 'Préstamo de material'),
(120, 22, 'gafas', 'no consumible', 1, '6515614515', '2025-06-30 15:56:07', 'salida', 'Préstamo de material'),
(121, 23, 'hdmi', 'no consumible', 1, '6453465', '2025-06-30 15:56:07', 'salida', 'Préstamo de material'),
(122, 21, 'lapicero', 'consumible', 5, '5464435', '2025-06-30 15:58:04', 'eliminado', NULL),
(123, NULL, 'colores', 'consumible', 1, '5343453', '2025-06-30 15:59:47', 'ingreso', NULL),
(124, 25, 'colores', 'consumible', 1, '5343453', '2025-06-30 16:00:00', 'salida', NULL),
(125, 25, '', 'consumible', 1, NULL, '2025-06-30 16:10:30', 'eliminado', 'Préstamo ID 43 eliminado, material devuelto al stock'),
(126, 23, '', 'consumible', 1, NULL, '2025-06-30 16:11:13', 'eliminado', 'Préstamo ID 42 eliminado, material devuelto al stock'),
(127, 22, '', 'consumible', 1, NULL, '2025-06-30 16:11:26', 'eliminado', 'Préstamo ID 41 eliminado, material devuelto al stock'),
(128, 24, '', 'consumible', 1, NULL, '2025-06-30 16:11:38', 'eliminado', 'Préstamo ID 40 eliminado, material devuelto al stock'),
(129, 19, '', 'consumible', 1, NULL, '2025-06-30 16:12:19', 'eliminado', 'Préstamo ID 35 eliminado, material devuelto al stock'),
(130, 22, '', 'consumible', 1, NULL, '2025-06-30 16:12:54', 'eliminado', 'Préstamo ID 38 eliminado, material devuelto al stock'),
(131, 25, 'colores', 'consumible', 1, '5343453', '2025-06-30 16:21:12', 'salida', NULL),
(132, 24, 'control  de aire acondicionado', 'no consumible', 1, '888888546', '2025-06-30 16:21:12', 'salida', 'Préstamo de material'),
(133, 22, 'gafas', 'no consumible', 1, '6515614515', '2025-06-30 16:21:12', 'salida', 'Préstamo de material'),
(134, 23, 'hdmi', 'no consumible', 1, '6453465', '2025-06-30 16:21:12', 'salida', 'Préstamo de material'),
(135, 19, 'lapiz', 'consumible', 1, '6548641234', '2025-06-30 16:21:12', 'salida', NULL),
(136, 24, 'control  de aire acondicionado', 'no consumible', 1, '888888546', '2025-06-30 16:21:34', 'entrada', 'Devolución de material. Condición: bueno'),
(137, 19, '', 'consumible', 1, NULL, '2025-06-30 16:22:20', 'eliminado', 'Préstamo ID 48 eliminado, material devuelto al stock'),
(138, 25, '', 'consumible', 1, NULL, '2025-06-30 16:39:07', 'eliminado', 'Préstamo ID 44 eliminado, material devuelto al stock'),
(139, 23, '', 'consumible', 1, NULL, '2025-06-30 16:39:23', 'eliminado', 'Préstamo ID 47 eliminado, material devuelto al stock'),
(140, 22, '', 'consumible', 1, NULL, '2025-06-30 16:54:11', 'eliminado', 'Préstamo ID 46 eliminado, material devuelto al stock'),
(141, 25, 'colores', 'consumible', 1, '5343453', '2025-06-30 16:54:33', 'salida', NULL),
(142, 25, '', 'consumible', 1, NULL, '2025-06-30 16:55:07', 'eliminado', 'Préstamo ID 49 eliminado, material devuelto al stock');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instructores`
--

CREATE TABLE `instructores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL DEFAULT 'NOT NULL UNIQUE' COMMENT 'Número de cédula único del instructor',
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT NULL COMMENT 'Estado actual: activo o inactivo',
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp(),
  `almacenista_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Registro de instructores que solicitan materiales/equipos';

--
-- Volcado de datos para la tabla `instructores`
--

INSERT INTO `instructores` (`id`, `nombre`, `apellido`, `cedula`, `correo`, `telefono`, `estado`, `fecha_ingreso`, `almacenista_id`) VALUES
(1, 'Camilo', 'sanchez', '1017240255', 'maerio@gmail.com', '3017534825', 'activo', '2025-06-18 13:31:58', 2),
(2, 'mario', 'cardona', '321738456', 'jcch40f@hotmail.com', '8888466544', 'activo', '2025-06-18 13:32:34', 3),
(3, 'Camilo', 'Ramirez', '159753456', 'carlos@gmail.com', '159753456', 'activo', '2025-06-18 14:46:09', 3),
(4, 'guillermo', 'cano', '4354125121', '2@gmail.com', '88884665', 'activo', '2025-06-24 18:19:24', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales`
--

CREATE TABLE `materiales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('consumible','no consumible') NOT NULL COMMENT 'Clasificación: consumible o no consumible',
  `cantidad` int(11) NOT NULL COMMENT 'Cantidad disponible en inventario',
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp(),
  `serie` varchar(255) DEFAULT NULL,
  `estado` enum('disponible','prestado','deteriorado') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Inventario de materiales del almacén';

--
-- Volcado de datos para la tabla `materiales`
--

INSERT INTO `materiales` (`id`, `nombre`, `tipo`, `cantidad`, `fecha_ingreso`, `serie`, `estado`) VALUES
(19, 'lapiz', 'consumible', 1, '2025-06-29 19:50:19', '6548641234', 'disponible'),
(22, 'gafas', 'no consumible', 1, '2025-06-29 20:52:03', '6515614515', 'disponible'),
(23, 'hdmi', 'no consumible', 1, '2025-06-30 20:54:52', '6453465', 'disponible'),
(24, 'control  de aire acondicionado', 'no consumible', 1, '2025-06-30 20:55:41', '888888546', 'disponible'),
(25, 'colores', 'consumible', 1, '2025-06-30 20:59:47', '5343453', 'disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos_equipos`
--

CREATE TABLE `prestamos_equipos` (
  `id` int(11) NOT NULL,
  `fecha_prestamo` datetime NOT NULL COMMENT 'Fecha y hora del préstamo',
  `fecha_devolucion` datetime DEFAULT NULL COMMENT 'Fecha y hora de devolución (null si no devuelto)',
  `almacenista_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Registro de préstamos de equipos a instructores';

--
-- Volcado de datos para la tabla `prestamos_equipos`
--

INSERT INTO `prestamos_equipos` (`id`, `fecha_prestamo`, `fecha_devolucion`, `almacenista_id`, `instructor_id`, `equipo_id`) VALUES
(3, '2025-06-18 09:30:00', '2025-06-18 09:31:00', 2, 2, 6),
(4, '2025-06-18 09:30:00', '2025-06-18 09:31:00', 2, 1, 8),
(6, '2025-06-18 09:52:00', '2025-06-18 10:54:00', 5, 3, 8),
(9, '2025-06-18 09:52:00', '2025-06-18 10:55:00', 5, 1, 6),
(10, '2025-06-19 11:05:00', '2025-06-27 08:04:00', 2, 2, 6),
(11, '2025-06-19 11:05:00', '2025-06-27 08:04:00', 2, 2, 8),
(18, '2025-06-27 10:09:00', '2025-06-29 15:22:00', 2, 1, 6),
(21, '2025-06-27 11:25:00', '2025-06-29 15:22:00', 2, 1, 8),
(22, '2025-06-29 15:09:00', '2025-06-29 15:43:00', 2, 1, 31),
(23, '2025-06-29 15:20:00', '2025-06-29 15:43:00', 2, 1, 32),
(24, '2025-06-29 15:47:00', '2025-06-29 15:48:00', 2, 2, 8),
(30, '2025-06-30 16:20:00', '2025-06-30 20:20:00', 2, 1, 31);

--
-- Disparadores `prestamos_equipos`
--
DELIMITER $$
CREATE TRIGGER `actualizar_estado_equipo_prestado` AFTER INSERT ON `prestamos_equipos` FOR EACH ROW BEGIN
  UPDATE equipos
  SET estado = 'prestado'
  WHERE id = NEW.equipo_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo_materiales`
--

CREATE TABLE `prestamo_materiales` (
  `id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL COMMENT 'Cantidad de material prestado',
  `fecha_prestamo` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_devolucion` datetime DEFAULT NULL,
  `material_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `almacenista_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de préstamos de materiales a instructores';

--
-- Volcado de datos para la tabla `prestamo_materiales`
--

INSERT INTO `prestamo_materiales` (`id`, `cantidad`, `fecha_prestamo`, `fecha_devolucion`, `material_id`, `instructor_id`, `almacenista_id`) VALUES
(45, 0, '2025-06-30 16:20:00', '2025-06-30 16:21:00', 24, 1, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_horas`
--

CREATE TABLE `registro_horas` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_ingreso` datetime NOT NULL COMMENT 'Hora de ingreso del almacenista',
  `hora_salida` datetime DEFAULT NULL COMMENT 'Hora de salida del almacenista (null si no registrada)',
  `almacenista_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Control de horarios de los almacenistas';

--
-- Volcado de datos para la tabla `registro_horas`
--

INSERT INTO `registro_horas` (`id`, `fecha`, `hora_ingreso`, `hora_salida`, `almacenista_id`) VALUES
(1, '2025-06-18', '2025-06-18 16:35:30', NULL, 4),
(3, '2025-06-18', '2025-06-18 16:47:21', '2025-06-18 09:58:10', 5),
(4, '2025-06-19', '2025-06-19 08:00:00', '2025-06-19 09:52:49', 4),
(5, '2025-06-24', '2025-06-24 08:00:00', '2025-06-24 11:20:30', 7),
(6, '2025-06-24', '2025-06-24 20:21:09', '2025-06-24 13:21:54', 11),
(7, '2025-06-24', '2025-06-24 20:23:39', '2025-06-24 13:23:52', 12),
(8, '2025-06-24', '2025-06-24 08:00:00', '2025-06-24 13:34:47', 13),
(9, '2025-06-24', '2025-06-24 08:00:00', '2025-06-24 13:36:09', 14),
(10, '2025-06-26', '2025-06-26 08:00:00', '2025-06-26 17:34:29', 12),
(11, '2025-06-26', '2025-06-26 08:00:00', '2025-06-26 18:03:22', 16),
(12, '2025-06-26', '2025-06-26 08:00:00', '2025-06-26 18:19:12', 14),
(13, '2025-06-27', '2025-06-27 08:00:00', '2025-06-27 21:45:14', 12),
(14, '2025-06-28', '2025-06-28 08:00:00', '2025-06-28 16:57:34', 12),
(18, '2025-06-30', '2025-06-30 08:00:00', '2025-06-30 17:38:16', 20),
(19, '2025-07-01', '2025-07-01 08:00:00', '2025-07-01 08:41:09', 20),
(20, '2025-07-02', '2025-07-02 08:00:00', '2025-07-02 19:22:40', 12),
(21, '2025-07-02', '2025-07-02 08:00:00', '2025-07-02 15:31:49', 20),
(22, '2025-07-02', '2025-07-02 08:00:00', '2025-07-02 15:38:10', 14),
(23, '2025-08-02', '2025-08-02 08:00:00', '2025-08-02 20:49:17', 12);

--
-- Disparadores `registro_horas`
--
DELIMITER $$
CREATE TRIGGER `evitar_ingreso_duplicado` BEFORE INSERT ON `registro_horas` FOR EACH ROW BEGIN
  IF EXISTS (
    SELECT 1 FROM registro_horas
    WHERE almacenista_id = NEW.almacenista_id
      AND fecha = NEW.fecha
  ) THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Ya existe un ingreso para este almacenista en esta fecha.';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL COMMENT 'Nombre de usuario único para autenticación',
  `contraseña` varchar(255) NOT NULL COMMENT 'Contraseña encriptada del usuario',
  `rol` enum('administrador','almacenista') NOT NULL COMMENT 'Rol del usuario: administrador o almacenista',
  `token_recuperacion` varchar(255) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Tabla de usuarios del sistema con credenciales de acceso y roles';

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `usuario`, `contraseña`, `rol`, `token_recuperacion`, `token_expiracion`) VALUES
(1, 'jeisonhitler81@gmail.com', '$2y$10$xnESbPHaa6.eWoZF2p4tK.HD5DBi9uop0laYbPWr9vmAXs52nti/m', 'administrador', NULL, NULL),
(3, 'carlos@gmail.com', '$2y$10$oo0F0ap71DIsDdXevHMrH.UdyFM7pc8auuJ.mFpDxLzX0kXQPDBG2', 'almacenista', NULL, NULL),
(4, 'jei-son-2503@hotmail.com', '$2y$10$kwSWWuZwjzlJJOfiFmojL.DaadFjz/qyGxm7uElfyogxHPVONakam', 'almacenista', 'aa494da156c7328cc86adade43d475f7', '2025-06-27 01:46:25'),
(5, 'jei142563@gmail.com', '$2y$10$6pX2iBEGpDjCAhU44tw8HOL1dHnVL.8KCJ9YfyhSxSw0XbvWmAI.q', 'almacenista', NULL, NULL),
(6, 'vanesa@gmail.com', '$2y$10$.FJFeOo8BeLkaU/ol9.Mou2Fkq8Fpou/qJUrS0T/3r4qhVXuDPzDi', 'almacenista', NULL, NULL),
(8, 'joanm5507@gmail.com', '$2y$10$.Wq2SwEEUR44jKZkVmBqc.pnyXQWtRHGl2gqcj9bCPF/yWn8hB6Uu', 'almacenista', NULL, NULL),
(12, '3@gmail.com', '$2y$10$Fm2VK5C.NwB6/Q6Fwvyzi.ElY3w3MLtTTDb.TTqwI6c3j4/PPAMpS', 'almacenista', NULL, NULL),
(13, 'jeisonnorena264@gmail.com', '$2y$10$vJdGfJvRmzUo3OQYJ9giIeMwjdilM69e5MIkoVZs3ZVff22dcv3OS', 'almacenista', '3bf2f3bdbf6034edc4ff613cbb8e9d1f', '2025-06-29 22:29:27'),
(14, 'jj@gmail.com', '$2y$10$PckvoyoL6p4gKLntSh/b6.GlET5ZZLoX.D3J/KWIhDn4KrslCfN4i', 'almacenista', NULL, NULL),
(15, 'jei-son-12@hotmail.com', '$2y$10$0PVLNp6ZTXOTp4tEM5SeRuvyEKuk3fjHWVG26.P.nlvheJnWnSiF2', 'almacenista', '94d760ba4146efd068ec133e8ddcb647', '2025-07-01 01:29:17'),
(17, 'carlos658velasquez25@gmail.com', '$2y$10$ehxN2Y3T/VUkfUQm4CfkhePJGDp9tnaOPWVbJBxTtwZMwbK/BNTy.', 'almacenista', 'e0ad3606127a0d908e7f4cee3a6c0692', '2025-06-29 01:04:09'),
(21, 'mueito1996@gmail.com', '$2y$10$cFp6siAJ4XXOsvINa6Nz4.YDys6UeMIxTh1mpi42SM35M.JcPEgWy', 'almacenista', '5e0315b4262c6855e9e38f12c1bb54e2', '2025-07-02 05:56:50');

--
-- Disparadores `usuario`
--
DELIMITER $$
CREATE TRIGGER `bloquear_eliminacion_admin` BEFORE DELETE ON `usuario` FOR EACH ROW BEGIN
    IF OLD.rol = 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede eliminar un usuario administrador';
    END IF;
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `almacenista`
--
ALTER TABLE `almacenista`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo_UNIQUE` (`correo`),
  ADD UNIQUE KEY `cédula` (`cedula`),
  ADD KEY `fk_almacenista_usuario1_idx` (`usuario_id`);

--
-- Indices de la tabla `devolucion_equipos`
--
ALTER TABLE `devolucion_equipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_devolucion_equipos_prestamos_equipos1_idx` (`prestamo_equipo_id`);

--
-- Indices de la tabla `devolucion_materiales`
--
ALTER TABLE `devolucion_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dm_prestamo_material` (`prestamo_material_id`),
  ADD KEY `fk_dm_almacenista` (`almacenista_id`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serie_UNIQUE` (`serie`);

--
-- Indices de la tabla `historial_equipos`
--
ALTER TABLE `historial_equipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_equipo_historial` (`equipo_id`);

--
-- Indices de la tabla `historial_materiales`
--
ALTER TABLE `historial_materiales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `instructores`
--
ALTER TABLE `instructores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo_UNIQUE` (`correo`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `fk_instructores_almacenista1_idx` (`almacenista_id`);

--
-- Indices de la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `prestamos_equipos`
--
ALTER TABLE `prestamos_equipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prestamos_equipos_almacenista_idx` (`almacenista_id`),
  ADD KEY `fk_prestamos_equipos_instructores1_idx` (`instructor_id`),
  ADD KEY `fk_prestamos_equipos_equipos1_idx` (`equipo_id`);

--
-- Indices de la tabla `prestamo_materiales`
--
ALTER TABLE `prestamo_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pm_material` (`material_id`),
  ADD KEY `fk_pm_instructor` (`instructor_id`),
  ADD KEY `fk_pm_almacenista` (`almacenista_id`);

--
-- Indices de la tabla `registro_horas`
--
ALTER TABLE `registro_horas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_registro_horas_almacenista1_idx` (`almacenista_id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `almacenista`
--
ALTER TABLE `almacenista`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `devolucion_equipos`
--
ALTER TABLE `devolucion_equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `devolucion_materiales`
--
ALTER TABLE `devolucion_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `historial_equipos`
--
ALTER TABLE `historial_equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT de la tabla `historial_materiales`
--
ALTER TABLE `historial_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT de la tabla `instructores`
--
ALTER TABLE `instructores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `prestamos_equipos`
--
ALTER TABLE `prestamos_equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `prestamo_materiales`
--
ALTER TABLE `prestamo_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `registro_horas`
--
ALTER TABLE `registro_horas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `almacenista`
--
ALTER TABLE `almacenista`
  ADD CONSTRAINT `fk_almacenista_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `devolucion_equipos`
--
ALTER TABLE `devolucion_equipos`
  ADD CONSTRAINT `fk_devolucion_prestamo` FOREIGN KEY (`prestamo_equipo_id`) REFERENCES `prestamos_equipos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `devolucion_materiales`
--
ALTER TABLE `devolucion_materiales`
  ADD CONSTRAINT `fk_dm_almacenista` FOREIGN KEY (`almacenista_id`) REFERENCES `almacenista` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dm_prestamo_material` FOREIGN KEY (`prestamo_material_id`) REFERENCES `prestamo_materiales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_equipos`
--
ALTER TABLE `historial_equipos`
  ADD CONSTRAINT `fk_equipo_historial` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`);

--
-- Filtros para la tabla `instructores`
--
ALTER TABLE `instructores`
  ADD CONSTRAINT `fk_instructores_almacenista1` FOREIGN KEY (`almacenista_id`) REFERENCES `almacenista` (`id`);

--
-- Filtros para la tabla `prestamos_equipos`
--
ALTER TABLE `prestamos_equipos`
  ADD CONSTRAINT `fk_pe_almacenista` FOREIGN KEY (`almacenista_id`) REFERENCES `almacenista` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pe_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pe_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructores` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `prestamo_materiales`
--
ALTER TABLE `prestamo_materiales`
  ADD CONSTRAINT `fk_pm_almacenista` FOREIGN KEY (`almacenista_id`) REFERENCES `almacenista` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructores` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_material` FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `registro_horas`
--
ALTER TABLE `registro_horas`
  ADD CONSTRAINT `fk_registro_horas_almacenista1` FOREIGN KEY (`almacenista_id`) REFERENCES `almacenista` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
