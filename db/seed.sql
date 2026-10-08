-- ============================================================
-- SEED LIMPIO - MANIPULACIÓN DE ALIMENTOS
-- ============================================================

START TRANSACTION;

-- ============================================================
-- 1. LIMPIEZA
-- ============================================================

-- Primero se eliminan las tablas dependientes
DELETE FROM carnets;
DELETE FROM resultado_examen;
DELETE FROM asistencias;
DELETE FROM comprobantes_tramite;
DELETE FROM historial_tramite;
DELETE FROM documentos;
DELETE FROM fecha_cursos;
DELETE FROM habilitaciones_examen;
DELETE FROM inscripciones;
DELETE FROM examenes;
DELETE FROM cursos;
DELETE FROM notificaciones;
DELETE FROM recovery_tokens;
DELETE FROM auditoria_acciones;
DELETE FROM usuario_roles;
DELETE FROM usuarios;

-- Después se eliminan las tablas base/configuración
DELETE FROM configuracion_sistema;
DELETE FROM roles;
DELETE FROM tipo_inscripcion;
DELETE FROM estados_tramite;

-- ============================================================
-- 2. ESTADOS DEL TRÁMITE
-- ============================================================

INSERT INTO estados_tramite
(id, nombre, descripcion)
VALUES
(1, 'PENDIENTE', 'Trámite iniciado'),
(2, 'DOCUMENTACION_PENDIENTE', 'Falta documentación'),
(3, 'DOCUMENTACION_APROBADA', 'Documentación validada'),
(4, 'INSCRIPTO_EXAMEN', 'Inscripto a examen'),
(5, 'APROBADO', 'Examen aprobado'),
(6, 'RECHAZADO', 'Trámite rechazado'),
(7, 'CANCELADO', 'Trámite cancelado'),
(8, 'CARNET_EMITIDO', 'Carnet emitido'),
(9, 'DESAPROBADO', 'Curso desaprobado');

-- ============================================================
-- 3. TIPOS DE INSCRIPCIÓN
-- ============================================================

INSERT INTO tipo_inscripcion
(id, nombre, descripcion)
VALUES
(1, 'curso', 'Inscripción a curso'),
(2, 'examen', 'Inscripción a examen');

-- ============================================================
-- 4. ROLES
-- ============================================================

INSERT INTO roles
(id, nombre, descripcion)
VALUES
(1, 'usuario', 'Ciudadano'),
(2, 'admin', 'Administrador'),
(3, 'inspector', 'Inspector');

-- ============================================================
-- 5. CONFIGURACIÓN
-- ============================================================

INSERT INTO configuracion_sistema
(id, clave, valor, descripcion)
VALUES
(
    1,
    'plazo_recursante_dias',
    '80',
    'Cantidad de días disponibles para reinscribirse como recursante'
);

-- ============================================================
-- 6. USUARIOS
-- ============================================================

INSERT INTO usuarios
(
    id,
    nombre,
    apellido,
    dni,
    email,
    password,
    telefono,
    domicilio,
    activo
)
VALUES

-- Administración
(
    1,
    'Administrador',
    'Sistema',
    '10000001',
    'admin@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Inspector
(
    2,
    'Inspector',
    'Sistema',
    '10000002',
    'inspector@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Sin documentación
(
    3,
    'Sin',
    'Documentacion',
    '40000001',
    'sin-docs@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Solo DNI
(
    4,
    'Solo',
    'DNI',
    '40000002',
    'solo-dni@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- DNI + foto
(
    5,
    'DNI',
    'Foto',
    '40000003',
    'dni-foto@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Asistencia aprobada
(
    6,
    'Asistencia',
    'Aprobada',
    '40000004',
    'asistencia@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Moodle
(
    7,
    'Moodle',
    'Aprobado',
    '40000005',
    'moodle@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Curso aprobado
(
    8,
    'Curso',
    'Aprobado',
    '40000006',
    'curso@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Curso desaprobado
(
    9,
    'Curso',
    'Desaprobado',
    '40000007',
    'desaprobado@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Examen aprobado
(
    10,
    'Examen',
    'Aprobado',
    '40000008',
    'aprobado@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Examen desaprobado
(
    11,
    'Examen',
    'Desaprobado',
    '40000009',
    'examen-desaprobado@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Recursante
(
    12,
    'Alumno',
    'Recursante',
    '40000010',
    'recursante@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Carnet vigente
(
    13,
    'Carnet',
    'Vigente',
    '40000011',
    'vigente@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Carnet vencido
(
    14,
    'Carnet',
    'Vencido',
    '40000012',
    'vencido@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
),

-- Carnet sin vencimiento
(
    15,
    'Carnet',
    'Sin Vencimiento',
    '40000013',
    'sin-vencimiento@test.com',
    '$2y$12$qiDR.WhRXgxKIoHUGusZh.Go5OkO3aQHzfQUUTqoaB/S88RzScIR2',
    NULL,
    NULL,
    1
);

-- ============================================================
-- 7. ROLES DE USUARIOS
-- ============================================================

INSERT INTO usuario_roles
(id, usuario_id, rol_id)
VALUES
(1, 1, 2),
(2, 2, 3),

(3, 3, 1),
(4, 4, 1),
(5, 5, 1),
(6, 6, 1),
(7, 7, 1),
(8, 8, 1),
(9, 9, 1),
(10, 10, 1),
(11, 11, 1),
(12, 12, 1),
(13, 13, 1),
(14, 14, 1),
(15, 15, 1);

-- ============================================================
-- 8. CURSOS
-- ============================================================

INSERT INTO cursos
(
    id,
    nombre,
    modalidad,
    descripcion,
    activo,
    fecha_inicio,
    hora_inicio,
    ubicacion,
    cupos
)
VALUES
(
    1,
    'Curso Manipulación de Alimentos - Octubre',
    'presencial',
    'Curso oficial de manipulación segura de alimentos.',
    1,
    '2026-10-20',
    '08:30:00',
    'Delegación Norte',
    30
),
(
    2,
    'Curso Manipulación de Alimentos - Noviembre',
    'presencial',
    'Curso oficial de manipulación segura de alimentos.',
    1,
    '2026-11-10',
    '08:30:00',
    'Delegación Centro',
    30
);

-- ============================================================
-- 9. EXÁMENES
-- ============================================================

INSERT INTO examenes
(
    id,
    fecha,
    hora,
    ubicacion,
    aula,
    cupos,
    activo
)
VALUES
(
    1,
    '2026-10-24',
    '09:00:00',
    'Delegación Norte',
    'Aula 1',
    20,
    1
),
(
    2,
    '2026-11-14',
    '09:00:00',
    'Delegación Centro',
    'Aula 1',
    20,
    1
),
(
    3,
    '2026-12-12',
    '09:00:00',
    'Delegación Centro',
    'Aula 2',
    20,
    1
);

-- ============================================================
-- 10. DOCUMENTACIÓN
-- ============================================================

-- Solo DNI
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(
    1,
    4,
    'dni',
    'dni-solo-dni.pdf',
    'seed/dni-solo-dni.pdf',
    'aprobado',
    NULL
);

-- DNI + foto
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(2, 5, 'dni', 'dni-dni-foto.pdf', 'seed/dni-dni-foto.pdf', 'aprobado', NULL),
(3, 5, 'foto_carnet', 'foto-dni-foto.jpg', 'seed/foto-dni-foto.jpg', 'aprobado', NULL);

-- Asistencia aprobada
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(4, 6, 'dni', 'dni-asistencia.pdf', 'seed/dni-asistencia.pdf', 'aprobado', NULL),
(5, 6, 'foto_carnet', 'foto-asistencia.jpg', 'seed/foto-asistencia.jpg', 'aprobado', NULL),
(6, 6, 'asistencia', 'Asistencia', '', 'aprobado', 'Asistencia validada por administración');

-- Moodle aprobado
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(7, 7, 'dni', 'dni-moodle.pdf', 'seed/dni-moodle.pdf', 'aprobado', NULL),
(8, 7, 'foto_carnet', 'foto-moodle.jpg', 'seed/foto-moodle.jpg', 'aprobado', NULL),
(9, 7, 'moodle', 'certificado-moodle.pdf', 'seed/certificado-moodle.pdf', 'aprobado', 'Certificado Moodle validado');

-- Curso aprobado
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(10, 8, 'dni', 'dni-curso.pdf', 'seed/dni-curso.pdf', 'aprobado', NULL),
(11, 8, 'foto_carnet', 'foto-curso.jpg', 'seed/foto-curso.jpg', 'aprobado', NULL);

-- Curso desaprobado
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(12, 9, 'dni', 'dni-desaprobado.pdf', 'seed/dni-desaprobado.pdf', 'aprobado', NULL),
(13, 9, 'foto_carnet', 'foto-desaprobado.jpg', 'seed/foto-desaprobado.jpg', 'aprobado', NULL);

-- Examen aprobado
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(14, 10, 'dni', 'dni-aprobado.pdf', 'seed/dni-aprobado.pdf', 'aprobado', NULL),
(15, 10, 'foto_carnet', 'foto-aprobado.jpg', 'seed/foto-aprobado.jpg', 'aprobado', NULL),
(16, 10, 'moodle', 'certificado-aprobado.pdf', 'seed/certificado-aprobado.pdf', 'aprobado', NULL);

-- Examen desaprobado
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(17, 11, 'dni', 'dni-examen-desaprobado.pdf', 'seed/dni-examen-desaprobado.pdf', 'aprobado', NULL),
(18, 11, 'foto_carnet', 'foto-examen-desaprobado.jpg', 'seed/foto-examen-desaprobado.jpg', 'aprobado', NULL),
(19, 11, 'moodle', 'certificado-examen-desaprobado.pdf', 'seed/certificado-examen-desaprobado.pdf', 'aprobado', NULL);

-- Recursante
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(20, 12, 'dni', 'dni-recursante.pdf', 'seed/dni-recursante.pdf', 'aprobado', NULL),
(21, 12, 'foto_carnet', 'foto-recursante.jpg', 'seed/foto-recursante.jpg', 'aprobado', NULL),
(22, 12, 'moodle', 'certificado-recursante.pdf', 'seed/certificado-recursante.pdf', 'aprobado', NULL);

-- Carnet vigente
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(23, 13, 'dni', 'dni-vigente.pdf', 'seed/dni-vigente.pdf', 'aprobado', NULL),
(24, 13, 'foto_carnet', 'foto-vigente.jpg', 'seed/foto-vigente.jpg', 'aprobado', NULL),
(25, 13, 'moodle', 'certificado-vigente.pdf', 'seed/certificado-vigente.pdf', 'aprobado', NULL);

-- Carnet vencido
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(26, 14, 'dni', 'dni-vencido.pdf', 'seed/dni-vencido.pdf', 'aprobado', NULL),
(27, 14, 'foto_carnet', 'foto-vencido.jpg', 'seed/foto-vencido.jpg', 'aprobado', NULL),
(28, 14, 'moodle', 'certificado-vencido.pdf', 'seed/certificado-vencido.pdf', 'aprobado', NULL);

-- Carnet sin vencimiento
INSERT INTO documentos
(
    id,
    usuario_id,
    tipo_documento,
    nombre_original,
    ruta_archivo,
    estado,
    observaciones
)
VALUES
(29, 15, 'dni', 'dni-sin-vencimiento.pdf', 'seed/dni-sin-vencimiento.pdf', 'aprobado', NULL),
(30, 15, 'foto_carnet', 'foto-sin-vencimiento.jpg', 'seed/foto-sin-vencimiento.jpg', 'aprobado', NULL),
(31, 15, 'moodle', 'certificado-sin-vencimiento.pdf', 'seed/certificado-sin-vencimiento.pdf', 'aprobado', NULL);

-- ============================================================
-- 11. INSCRIPCIONES
-- ============================================================

-- Asistencia aprobada -> examen
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    1,
    6,
    NULL,
    1,
    2,
    4,
    'Habilitado por asistencia validada.',
    NULL
);

-- Moodle -> examen
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    2,
    7,
    NULL,
    1,
    2,
    4,
    'Habilitado mediante certificado Moodle.',
    NULL
);

-- Curso aprobado
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    3,
    8,
    1,
    NULL,
    1,
    5,
    'Curso aprobado.',
    '2026-10-22'
);

-- Curso desaprobado
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    4,
    9,
    1,
    NULL,
    1,
    9,
    'Curso desaprobado.',
    '2026-10-22'
);

-- Examen aprobado
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    5,
    10,
    NULL,
    1,
    2,
    5,
    'Examen aprobado.',
    NULL
);

-- Examen desaprobado
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    6,
    11,
    NULL,
    1,
    2,
    6,
    'Examen desaprobado.',
    NULL
);

-- Recursante
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    7,
    12,
    NULL,
    2,
    2,
    4,
    'Recursante dentro del plazo permitido.',
    NULL
);

-- Carnet vigente
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    8,
    13,
    NULL,
    1,
    2,
    8,
    'Carnet vigente.',
    NULL
);

-- Carnet vencido
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    9,
    14,
    NULL,
    1,
    2,
    8,
    'Carnet vencido.',
    NULL
);

-- Carnet sin vencimiento
INSERT INTO inscripciones
(
    id,
    usuario_id,
    curso_id,
    examen_id,
    tipo_inscripcion_id,
    estado_tramite_id,
    observaciones,
    fecha_fin_curso
)
VALUES
(
    10,
    15,
    NULL,
    1,
    2,
    8,
    'Carnet sin fecha de vencimiento.',
    NULL
);

-- ============================================================
-- 12. RESULTADOS DE EXAMEN
-- ============================================================

INSERT INTO resultado_examen
(
    id,
    inscripcion_id,
    examen_id,
    nota,
    aprobado,
    fecha_resultado,
    observaciones
)
VALUES
(
    1,
    5,
    1,
    9.00,
    1,
    '2026-10-01 10:00:00',
    'Examen aprobado correctamente.'
),
(
    2,
    6,
    1,
    4.00,
    0,
    '2026-10-01 10:30:00',
    'Examen desaprobado.'
);

-- ============================================================
-- 13. ASISTENCIAS
-- ============================================================

INSERT INTO asistencias
(
    id,
    inscripcion_id,
    fecha,
    presente,
    observaciones
)
VALUES
(1, 1, '2026-10-06', 1, 'Presente'),
(2, 1, '2026-10-07', 1, 'Presente'),
(3, 1, '2026-10-08', 1, 'Presente');

-- ============================================================
-- 14. CARNETS
-- ============================================================

-- Carnet vigente
INSERT INTO carnets
(
    id,
    inscripcion_id,
    numero_carnet,
    fecha_emision,
    fecha_vencimiento,
    ruta_pdf,
    vigente,
    activo
)
VALUES
(
    1,
    8,
    'CAR-2026-0001',
    '2026-09-01',
    '2027-09-01',
    'seed/carnet-vigente.pdf',
    1,
    1
);

-- Carnet vencido
INSERT INTO carnets
(
    id,
    inscripcion_id,
    numero_carnet,
    fecha_emision,
    fecha_vencimiento,
    ruta_pdf,
    vigente,
    activo
)
VALUES
(
    2,
    9,
    'CAR-2025-0002',
    '2025-09-01',
    '2026-09-01',
    'seed/carnet-vencido.pdf',
    0,
    1
);

-- Carnet sin vencimiento
INSERT INTO carnets
(
    id,
    inscripcion_id,
    numero_carnet,
    fecha_emision,
    fecha_vencimiento,
    ruta_pdf,
    vigente,
    activo
)
VALUES
(
    3,
    10,
    'CAR-2026-0003',
    '2026-09-01',
    NULL,
    'seed/carnet-sin-vencimiento.pdf',
    1,
    1
);

-- ============================================================
-- 15. TABLAS HISTÓRICAS / AUXILIARES
-- ============================================================

-- Se dejan vacías deliberadamente:
-- fecha_cursos
-- habilitaciones_examen
-- comprobantes_tramite
-- historial_tramite
-- notificaciones
-- recovery_tokens
-- auditoria_acciones

COMMIT;