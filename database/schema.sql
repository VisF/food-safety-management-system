CREATE DATABASE IF NOT EXISTS manipulacion_alimentos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE manipulacion_alimentos;

-- ==========================================
-- SEGURIDAD
-- ==========================================

CREATE TABLE roles (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
nombre VARCHAR(50) NOT NULL UNIQUE,
descripcion VARCHAR(255),
activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE usuarios (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
dni VARCHAR(20) NOT NULL UNIQUE,
nombre VARCHAR(100) NOT NULL,
apellido VARCHAR(100) NOT NULL,
email VARCHAR(150) NOT NULL UNIQUE,
telefono VARCHAR(50),
password_hash VARCHAR(255) NOT NULL,
activo TINYINT(1) NOT NULL DEFAULT 1,
fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
ultimo_acceso DATETIME NULL
);

CREATE TABLE usuario_roles (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
usuario_id INT UNSIGNED NOT NULL,
rol_id INT UNSIGNED NOT NULL,
UNIQUE(usuario_id, rol_id),
FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- ==========================================
-- CURSOS
-- ==========================================

CREATE TABLE cursos (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
nombre VARCHAR(150) NOT NULL,
descripcion TEXT,
modalidad ENUM('presencial','virtual','mixta') DEFAULT 'presencial',
activo TINYINT(1) DEFAULT 1,
fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fechas_curso (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
curso_id INT UNSIGNED NOT NULL,
fecha_inicio DATETIME NOT NULL,
fecha_fin DATETIME NOT NULL,
cupo INT NOT NULL,
estado ENUM('abierta','cerrada','finalizada','cancelada') DEFAULT 'abierta',
FOREIGN KEY (curso_id) REFERENCES cursos(id)
);

-- ==========================================
-- TRAMITES
-- ==========================================

CREATE TABLE estados_tramite (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
nombre VARCHAR(100) NOT NULL UNIQUE,
descripcion TEXT
);

CREATE TABLE tipos_inscripcion (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
nombre VARCHAR(100) NOT NULL,
descripcion TEXT
);

CREATE TABLE inscripciones (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
usuario_id INT UNSIGNED NOT NULL,
fecha_curso_id INT UNSIGNED NOT NULL,
tipo_inscripcion_id INT UNSIGNED NOT NULL,
estado_tramite_id INT UNSIGNED NOT NULL,
fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
observaciones TEXT,
FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
FOREIGN KEY (fecha_curso_id) REFERENCES fechas_curso(id),
FOREIGN KEY (tipo_inscripcion_id) REFERENCES tipos_inscripcion(id),
FOREIGN KEY (estado_tramite_id) REFERENCES estados_tramite(id)
);

-- ==========================================
-- DOCUMENTOS
-- ==========================================

CREATE TABLE documentos (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
usuario_id INT UNSIGNED NOT NULL,
tipo_documento VARCHAR(100) NOT NULL,
nombre_original VARCHAR(255) NOT NULL,
ruta_archivo VARCHAR(500) NOT NULL,
estado ENUM('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
observaciones TEXT,
fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
fecha_revision DATETIME NULL,
FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ==========================================
-- ASISTENCIAS
-- ==========================================

CREATE TABLE asistencias (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
id_inscripcion INT UNSIGNED NOT NULL,
fecha DATE NOT NULL,
presente TINYINT(1) NOT NULL DEFAULT 0,
observaciones TEXT,
FOREIGN KEY (id_inscripcion) REFERENCES inscripciones(id)
);

-- ==========================================
-- EXAMENES
-- ==========================================

CREATE TABLE examenes (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
fecha_curso_id INT UNSIGNED NOT NULL,
fecha_examen DATETIME NOT NULL,
puntaje_aprobacion DECIMAL(5,2) DEFAULT 60.00,
activo TINYINT(1) DEFAULT 1,
FOREIGN KEY (fecha_curso_id) REFERENCES fechas_curso(id)
);

CREATE TABLE resultados_examen (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
examen_id INT UNSIGNED NOT NULL,
inscripcion_id INT UNSIGNED NOT NULL,
nota DECIMAL(5,2),
aprobado TINYINT(1),
fecha_correccion DATETIME,
FOREIGN KEY (examen_id) REFERENCES examenes(id),
FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id)
);

-- ==========================================
-- RECURSANTES
-- ==========================================

CREATE TABLE plazos_recursantes (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
inscripcion_id INT UNSIGNED NOT NULL,
fecha_examen_desaprobado DATE NOT NULL,
fecha_limite DATE NOT NULL,
eligible TINYINT(1) DEFAULT 0,
FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id)
);

-- ==========================================
-- CARNETS
-- ==========================================

CREATE TABLE carnets (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
inscripcion_id INT UNSIGNED NOT NULL,
numero_carnet VARCHAR(100) UNIQUE,
codigo_qr VARCHAR(255),
fecha_emision DATE NOT NULL,
fecha_vencimiento DATE NOT NULL,
ruta_pdf VARCHAR(500),
vigente TINYINT(1) DEFAULT 1,
FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id)
);

-- ==========================================
-- NOTIFICACIONES
-- ==========================================

CREATE TABLE notificaciones (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
usuario_id INT UNSIGNED NOT NULL,
titulo VARCHAR(255) NOT NULL,
mensaje TEXT NOT NULL,
leida TINYINT(1) DEFAULT 0,
fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE alertas (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
usuario_id INT UNSIGNED NOT NULL,
tipo VARCHAR(50) NOT NULL,
payload JSON NULL,
fecha_programada DATETIME NOT NULL,
enviada TINYINT(1) DEFAULT 0,
fecha_envio DATETIME NULL,
FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ==========================================
-- RESERVAS
-- ==========================================

CREATE TABLE reservas (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
fecha_curso_id INT UNSIGNED NOT NULL,
usuario_id INT UNSIGNED NOT NULL,
estado ENUM('reservado','cancelado','expirado') DEFAULT 'reservado',
fecha_reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
fecha_expiracion DATETIME NOT NULL,
token VARCHAR(128) NOT NULL,
FOREIGN KEY (fecha_curso_id) REFERENCES fechas_curso(id),
FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ==========================================
-- AUDITORIA
-- ==========================================

CREATE TABLE auditoria_acciones (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
id_usuario INT UNSIGNED NOT NULL,
tabla_afectada VARCHAR(100) NOT NULL,
accion VARCHAR(20) NOT NULL,
datos_anteriores JSON NULL,
datos_nuevos JSON NULL,
fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
ip VARCHAR(45),
user_agent TEXT,
FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

-- ==========================================
-- INSPECTORES
-- ==========================================

CREATE TABLE historial_busquedas (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
inspector_id INT UNSIGNED NOT NULL,
criterio TEXT NOT NULL,
result_count INT NOT NULL,
ip VARCHAR(45),
fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (inspector_id) REFERENCES usuarios(id)
);

-- ==========================================
-- MOODLE
-- ==========================================

CREATE TABLE moodle_sync (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
usuario_id INT UNSIGNED NOT NULL,
moodle_user_id INT NULL,
curso_id INT UNSIGNED NULL,
moodle_course_id INT NULL,
estado VARCHAR(50),
ultima_sync DATETIME,
error TEXT,
FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
