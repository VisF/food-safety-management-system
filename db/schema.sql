-- Schema SQL for Sistema TUDAI - Manipulacion de Alimentos
-- Generated May 2026
-- Engine: InnoDB, Charset: utf8mb4

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_nombre_uq` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `dni` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `domicilio` VARCHAR(255) DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuarios_dni_uq` (`dni`),
  UNIQUE KEY `usuarios_email_uq` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `rol_id` INT UNSIGNED NOT NULL,
  `fecha_asignacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ur_usuario_idx` (`usuario_id`),
  KEY `ur_rol_idx` (`rol_id`),
  CONSTRAINT `ur_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ur_rol_fk` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipo_inscripcion` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cursos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `modalidad` ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
  `descripcion` TEXT DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fecha_cursos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `curso_id` INT UNSIGNED NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `cupos` INT NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fc_curso_idx` (`curso_id`),
  CONSTRAINT `fc_curso_fk` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `examenes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `hora` TIME DEFAULT NULL,
  `ubicacion` VARCHAR(255) DEFAULT NULL,
  `aula` VARCHAR(120) DEFAULT NULL,
  `cupos` INT NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `estados_tramite` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `estado_nombre_uq` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inscripciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `curso_id` INT UNSIGNED DEFAULT NULL,
  `examen_id` INT UNSIGNED DEFAULT NULL,
  `tipo_inscripcion_id` INT UNSIGNED DEFAULT NULL,
  `fecha_inscripcion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_tramite_id` INT UNSIGNED DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ins_usuario_idx` (`usuario_id`),
  KEY `ins_curso_idx` (`curso_id`),
  KEY `ins_examen_idx` (`examen_id`),
  KEY `ins_estado_idx` (`estado_tramite_id`),
  CONSTRAINT `ins_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ins_curso_fk` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `ins_examen_fk` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `ins_tipo_fk` FOREIGN KEY (`tipo_inscripcion_id`) REFERENCES `tipo_inscripcion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `ins_estado_fk` FOREIGN KEY (`estado_tramite_id`) REFERENCES `estados_tramite` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `historial_tramite` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` INT UNSIGNED NOT NULL,
  `estado_anterior_id` INT UNSIGNED DEFAULT NULL,
  `estado_nuevo_id` INT UNSIGNED NOT NULL,
  `fecha_cambio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_admin_id` INT UNSIGNED DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hist_inscripcion_idx` (`inscripcion_id`),
  KEY `hist_estado_anterior_idx` (`estado_anterior_id`),
  KEY `hist_estado_nuevo_idx` (`estado_nuevo_id`),
  KEY `hist_usuario_admin_idx` (`usuario_admin_id`),
  CONSTRAINT `hist_inscripcion_fk` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `hist_estado_anterior_fk` FOREIGN KEY (`estado_anterior_id`) REFERENCES `estados_tramite` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `hist_estado_nuevo_fk` FOREIGN KEY (`estado_nuevo_id`) REFERENCES `estados_tramite` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `hist_usuario_admin_fk` FOREIGN KEY (`usuario_admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `resultado_examen` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` INT UNSIGNED NOT NULL,
  `examen_id` INT UNSIGNED NOT NULL,
  `nota` DECIMAL(5,2) DEFAULT NULL,
  `aprobado` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_resultado` DATETIME DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `res_insc_idx` (`inscripcion_id`),
  KEY `res_exam_idx` (`examen_id`),
  CONSTRAINT `res_insc_fk` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `res_exam_fk` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `asistencias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` INT UNSIGNED NOT NULL,
  `fecha` DATE NOT NULL,
  `presente` TINYINT(1) NOT NULL DEFAULT 0,
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asis_insc_idx` (`inscripcion_id`),
  CONSTRAINT `asis_insc_fk` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `documentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` INT UNSIGNED NOT NULL,
  `tipo_documento` VARCHAR(100) NOT NULL,
  `ruta_archivo` VARCHAR(512) NOT NULL,
  `validado` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_subida` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doc_insc_idx` (`inscripcion_id`),
  CONSTRAINT `doc_insc_fk` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carnets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` INT UNSIGNED NOT NULL,
  `numero_carnet` VARCHAR(100) NOT NULL,
  `fecha_emision` DATE DEFAULT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `ruta_pdf` VARCHAR(512) DEFAULT NULL,
  `vigente` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `carnet_insc_idx` (`inscripcion_id`),
  UNIQUE KEY `carnet_inscripcion_uq` (`inscripcion_id`),
  CONSTRAINT `carnet_insc_fk` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `carnet_numero_uq` (`numero_carnet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comprobantes_tramite` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` INT UNSIGNED NOT NULL,
  `codigo_comprobante` VARCHAR(120) NOT NULL,
  `fecha_emision` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ruta_pdf` VARCHAR(512) DEFAULT NULL,
  `hash_verificacion` VARCHAR(255) DEFAULT NULL,
  `vigente` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `comp_insc_idx` (`inscripcion_id`),
  UNIQUE KEY `comp_insc_uq` (`inscripcion_id`),
  UNIQUE KEY `comp_codigo_uq` (`codigo_comprobante`),
  CONSTRAINT `comp_insc_fk` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auditoria_acciones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `tabla_afectada` VARCHAR(128) DEFAULT NULL,
  `accion` VARCHAR(32) DEFAULT NULL,
  `datos_anteriores` JSON DEFAULT NULL,
  `datos_nuevos` JSON DEFAULT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aud_usuario_idx` (`usuario_id`),
  CONSTRAINT `aud_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notificaciones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,
  `asunto` VARCHAR(255) DEFAULT NULL,
  `mensaje` TEXT DEFAULT NULL,
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` TEXT DEFAULT NULL,
  `enviado` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_envio` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `not_usuario_idx` (`usuario_id`),
  CONSTRAINT `not_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recovery_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expiracion` DATETIME NOT NULL,
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `rt_usuario_idx` (`usuario_id`),
  CONSTRAINT `rt_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Datos de ejemplo completos: ver db/seed.sql
