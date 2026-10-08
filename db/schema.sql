-- ============================================================
-- SCHEMA - MANIPULACIÓN DE ALIMENTOS
-- ============================================================

CREATE DATABASE IF NOT EXISTS manipulacion_alimentos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE manipulacion_alimentos;

-- ============================================================
-- 1. TABLA: roles
-- ============================================================

CREATE TABLE roles (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. TABLA: estados_tramite
-- ============================================================

CREATE TABLE estados_tramite (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_estados_tramite_nombre (nombre)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. TABLA: tipo_inscripcion
-- ============================================================

CREATE TABLE tipo_inscripcion (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT NULL,

    PRIMARY KEY (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. TABLA: configuracion_sistema
-- ============================================================

CREATE TABLE configuracion_sistema (
    id INT(11) NOT NULL AUTO_INCREMENT,
    clave VARCHAR(100) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_configuracion_sistema_clave (clave)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. TABLA: usuarios
-- ============================================================

CREATE TABLE usuarios (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cuil_cuit VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(50) NULL,
    domicilio VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_cuil_cuit (cuil_cuit),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. TABLA: usuario_roles
-- ============================================================

CREATE TABLE usuario_roles (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT(10) UNSIGNED NOT NULL,
    rol_id INT(10) UNSIGNED NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_usuario_roles_usuario (usuario_id),
    KEY idx_usuario_roles_rol (rol_id),

    CONSTRAINT ur_usuario_fk
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT ur_rol_fk
        FOREIGN KEY (rol_id)
        REFERENCES roles (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. TABLA: cursos
-- ============================================================

CREATE TABLE cursos (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    modalidad ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio DATE NULL,
    hora_inicio TIME NULL,
    ubicacion VARCHAR(255) NULL,
    cupos INT(10) UNSIGNED NULL DEFAULT 30,

    PRIMARY KEY (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. TABLA: examenes
-- ============================================================

CREATE TABLE examenes (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    hora TIME NULL,
    ubicacion VARCHAR(255) NULL,
    aula VARCHAR(120) NULL,
    cupos INT(11) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. TABLA: inscripciones
-- ============================================================

CREATE TABLE inscripciones (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT(10) UNSIGNED NOT NULL,
    curso_id INT(10) UNSIGNED NULL,
    examen_id INT(10) UNSIGNED NULL,
    tipo_inscripcion_id INT(10) UNSIGNED NULL,
    fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado_tramite_id INT(10) UNSIGNED NULL,
    observaciones TEXT NULL,
    fecha_fin_curso DATE NULL,

    PRIMARY KEY (id),

    KEY idx_inscripciones_usuario (usuario_id),
    KEY idx_inscripciones_curso (curso_id),
    KEY idx_inscripciones_examen (examen_id),
    KEY idx_inscripciones_tipo (tipo_inscripcion_id),
    KEY idx_inscripciones_estado (estado_tramite_id),

    CONSTRAINT ins_usuario_fk
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT ins_curso_fk
        FOREIGN KEY (curso_id)
        REFERENCES cursos (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT ins_examen_fk
        FOREIGN KEY (examen_id)
        REFERENCES examenes (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT ins_tipo_fk
        FOREIGN KEY (tipo_inscripcion_id)
        REFERENCES tipo_inscripcion (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT ins_estado_fk
        FOREIGN KEY (estado_tramite_id)
        REFERENCES estados_tramite (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. TABLA: documentos
-- ============================================================

CREATE TABLE documentos (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT(10) UNSIGNED NOT NULL,
    tipo_documento ENUM('dni','foto_carnet','asistencia','moodle') NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(512) NOT NULL,
    estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    observaciones TEXT NULL,
    fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision DATETIME NULL,

    PRIMARY KEY (id),

    KEY idx_documentos_usuario (usuario_id),

    CONSTRAINT doc_usuario_fk
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. TABLA: resultado_examen
-- ============================================================

CREATE TABLE resultado_examen (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    inscripcion_id INT(10) UNSIGNED NOT NULL,
    examen_id INT(10) UNSIGNED NOT NULL,
    nota DECIMAL(5,2) NULL,
    aprobado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_resultado DATETIME NULL,
    observaciones TEXT NULL,

    PRIMARY KEY (id),

    KEY idx_resultado_examen_inscripcion (inscripcion_id),
    KEY idx_resultado_examen_examen (examen_id),

    CONSTRAINT res_insc_fk
        FOREIGN KEY (inscripcion_id)
        REFERENCES inscripciones (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT res_exam_fk
        FOREIGN KEY (examen_id)
        REFERENCES examenes (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. TABLA: carnets
-- ============================================================

CREATE TABLE carnets (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    inscripcion_id INT(10) UNSIGNED NOT NULL,
    numero_carnet VARCHAR(100) NOT NULL,
    fecha_emision DATE NULL,
    fecha_vencimiento DATE NULL,
    ruta_pdf VARCHAR(512) NULL,
    vigente TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,

    PRIMARY KEY (id),

    UNIQUE KEY uq_carnets_inscripcion (inscripcion_id),
    UNIQUE KEY uq_carnets_numero (numero_carnet),

    CONSTRAINT carnet_insc_fk
        FOREIGN KEY (inscripcion_id)
        REFERENCES inscripciones (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;