-- Seed data for Sistema TUDAI - Manipulacion de Alimentos
-- Ejecutar despues de importar db/schema.sql

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM notificaciones;
DELETE FROM comprobantes_tramite;
DELETE FROM auditoria_acciones;
DELETE FROM historial_tramite;
DELETE FROM carnets;
DELETE FROM resultado_examen;
DELETE FROM asistencias;
DELETE FROM documentos;
DELETE FROM inscripciones;
DELETE FROM fecha_cursos;
DELETE FROM examenes;
DELETE FROM cursos;
DELETE FROM usuario_roles;
DELETE FROM usuarios;
DELETE FROM roles;
DELETE FROM tipo_inscripcion;
DELETE FROM estado_tramite;

INSERT INTO roles (id, nombre, descripcion) VALUES
(1, 'inscripto', 'Usuario inscripto'),
(2, 'admin', 'Administrador del sistema'),
(3, 'inspector', 'Inspector municipal');

INSERT INTO estado_tramite (id, nombre, descripcion) VALUES
(1, 'pendiente', 'Pendiente de validacion'),
(2, 'documentacion_completa', 'Documentacion completa'),
(3, 'documentacion_rechazada', 'Documentacion rechazada'),
(4, 'apto_examen', 'Apto para examen'),
(5, 'examen_rendido', 'Examen rendido'),
(6, 'aprobado', 'Aprobado'),
(7, 'rechazado', 'Rechazado'),
(8, 'carnet_emitido', 'Carnet emitido');

INSERT INTO tipo_inscripcion (id, nombre, descripcion) VALUES
(1, 'curso_presencial', 'Inscripcion a curso presencial'),
(2, 'examen', 'Inscripcion a examen'),
(3, 'recursante', 'Reinscripcion por recursado'),
(4, 'renovacion', 'Renovacion de carnet');

INSERT INTO usuarios (id, nombre, apellido, dni, email, password, telefono, domicilio, activo, fecha_creacion) VALUES
(1, 'Juan', 'Perez', '35849201', 'juan.perez@example.com', '$2y$10$aP8cCRtR9..269TVbTLBEetee7GMiYmAxyff7vUVrD/Tp0iWS.dR.', '2604-111111', 'Calle 12 345', 1, '2026-05-01 08:00:00'),
(2, 'Maria', 'Gomez', '27482910', 'maria.gomez@example.com', '$2y$10$H6T/J9tfjPRHqoN3ZY.uH.urv9x.y5Un7ZF81Lclho69a2YLcUbKi', '2604-222222', 'Av. San Martin 456', 1, '2026-05-02 09:15:00'),
(3, 'Carlos', 'Rodriguez', '31902115', 'carlos.rodriguez@example.com', '$2y$10$G4Ppa2agTSJIg76FNlkoZ.S9POh/PM1HnKi0CzwKcxQBdsWJMTVuS', '2604-333333', 'Pasaje Sur 78', 1, '2026-05-03 10:20:00'),
(4, 'Admin', 'Municipal', '20111222', 'admin@ManipulacionDeAlimentos.local', '$2y$10$pufBBzoEwWKzy9KyGXcTr.s3TsDolHdCCIqj.SxOhjCFMChuMCcc2', '2604-999999', 'Palacio Municipal', 1, '2026-05-01 07:30:00');

INSERT INTO usuario_roles (id, usuario_id, rol_id, fecha_asignacion) VALUES
(1, 1, 1, '2026-05-01 08:05:00'),
(2, 2, 1, '2026-05-02 09:20:00'),
(3, 3, 1, '2026-05-03 10:25:00'),
(4, 4, 2, '2026-05-01 07:35:00'),
(5, 4, 3, '2026-05-01 07:36:00');

INSERT INTO cursos (id, nombre, modalidad, descripcion, activo, fecha_creacion) VALUES
(1, 'Manipulacion de Alimentos Basica', 'presencial', 'Curso base para manipuladores', 1, '2026-05-01 08:30:00'),
(2, 'Buenas Practicas de Higiene', 'virtual', 'Capacitacion virtual complementaria', 1, '2026-05-01 08:35:00');

INSERT INTO fecha_cursos (id, curso_id, fecha_inicio, fecha_fin, cupos, activo) VALUES
(1, 1, '2026-06-05', '2026-06-20', 12, 1),
(2, 1, '2026-07-01', '2026-07-16', 8, 1),
(3, 2, '2026-06-10', '2026-06-24', 20, 1);

INSERT INTO examenes (id, fecha, hora, ubicacion, cupos, activo, fecha_creacion) VALUES
(1, '2026-06-15', '09:00:00', 'Centro Municipal Norte', 12, 1, '2026-05-04 11:00:00'),
(2, '2026-06-22', '14:30:00', 'Polideportivo Norte', 8, 1, '2026-05-04 11:05:00'),
(3, '2026-07-05', '10:00:00', 'Delegacion Sur', 0, 1, '2026-05-04 11:10:00');

INSERT INTO inscripciones (id, usuario_id, curso_id, examen_id, tipo_inscripcion_id, fecha_inscripcion, estado_tramite_id, observaciones) VALUES
(1, 1, NULL, 1, 2, '2026-05-10 09:00:00', 8, 'Documentacion completa y carnet emitido'),
(2, 2, NULL, 2, 2, '2026-05-12 10:15:00', 4, 'Pendiente de rendir examen'),
(3, 3, NULL, 3, 2, '2026-05-14 12:20:00', 7, 'Rechazado por cupos agotados'),
(4, 2, 1, NULL, 1, '2026-05-08 08:45:00', 2, 'Curso presencial en proceso');

INSERT INTO documentos (id, inscripcion_id, tipo_documento, ruta_archivo, validado, fecha_subida, observaciones) VALUES
(1, 1, 'DNI', '/uploads/documentos/dni_35849201.pdf', 1, '2026-05-10 09:10:00', 'Documento validado'),
(2, 1, 'Foto Carnet', '/uploads/documentos/foto_35849201.jpg', 1, '2026-05-10 09:12:00', 'Foto valida'),
(3, 2, 'DNI', '/uploads/documentos/dni_27482910.pdf', 1, '2026-05-12 10:25:00', 'Documento validado'),
(4, 2, 'Comprobante de pago', '/uploads/documentos/pago_27482910.pdf', 0, '2026-05-12 10:30:00', 'En revision'),
(5, 3, 'DNI', '/uploads/documentos/dni_31902115.pdf', -1, '2026-05-14 12:30:00', 'Archivo corrupto'),
(6, 4, 'DNI', '/uploads/documentos/dni_27482910_curso.pdf', 1, '2026-05-08 09:00:00', 'Documento validado');

INSERT INTO asistencias (id, inscripcion_id, fecha, presente, observaciones) VALUES
(1, 4, '2026-06-05', 1, 'Asistio a la primera clase'),
(2, 4, '2026-06-12', 1, 'Asistio con puntualidad'),
(3, 4, '2026-06-19', 0, 'Falta justificada');

INSERT INTO resultado_examen (id, inscripcion_id, examen_id, nota, aprobado, fecha_resultado, observaciones) VALUES
(1, 1, 1, 9.50, 1, '2026-06-16 13:00:00', 'Aprobado con excelente desempeno'),
(2, 3, 3, 4.00, 0, '2026-07-06 15:00:00', 'No aprobado');

INSERT INTO carnets (id, inscripcion_id, numero_carnet, fecha_emision, fecha_vencimiento, ruta_pdf, vigente) VALUES
(1, 1, 'CAR-2026-0001', '2026-06-20', '2029-06-20', '/uploads/carnets/carnet_2026_0001.pdf', 1);

INSERT INTO comprobantes_tramite (id, inscripcion_id, codigo_comprobante, fecha_emision, ruta_pdf, hash_verificacion, vigente) VALUES
(1, 1, 'COM-2026-0001', '2026-05-10 09:30:00', '/uploads/comprobantes/comprobante_1.pdf', 'b4f2f2f3a9c01d13d27f9c0b4f0d8e5a', 1),
(2, 2, 'COM-2026-0002', '2026-05-12 10:40:00', '/uploads/comprobantes/comprobante_2.pdf', 'd7a6d2bcab9f1a2f44a4fbcf6e9cb7bb', 1),
(3, 3, 'COM-2026-0003', '2026-05-14 12:40:00', '/uploads/comprobantes/comprobante_3.pdf', 'f2c3a8120ad8cbb9f0d4f31a9d8a120a', 0);

INSERT INTO historial_tramite (id, inscripcion_id, estado_anterior_id, estado_nuevo_id, fecha_cambio, usuario_admin_id, observaciones) VALUES
(1, 1, 1, 2, '2026-05-10 10:00:00', 4, 'Documentacion recibida y validada'),
(2, 1, 2, 4, '2026-05-12 08:00:00', 4, 'Apto para examen'),
(3, 1, 4, 5, '2026-06-15 11:15:00', 4, 'Examen rendido'),
(4, 1, 5, 6, '2026-06-16 13:05:00', 4, 'Examen aprobado'),
(5, 1, 6, 8, '2026-06-20 09:00:00', 4, 'Carnet emitido'),
(6, 2, 1, 2, '2026-05-12 11:00:00', 4, 'Documentacion validada'),
(7, 2, 2, 4, '2026-05-20 09:00:00', 4, 'Autorizado para examen'),
(8, 3, 1, 7, '2026-05-14 12:45:00', 4, 'Inscripcion rechazada por cupos'),
(9, 4, 1, 2, '2026-05-08 09:15:00', 4, 'Inscripcion a curso en proceso');

INSERT INTO auditoria_acciones (id, usuario_id, tabla_afectada, accion, datos_anteriores, datos_nuevos, fecha, ip, user_agent) VALUES
(1, 4, 'documentos', 'UPDATE', '{"validado":0}', '{"validado":1}', '2026-05-10 10:01:00', '127.0.0.1', 'Mozilla/5.0'),
(2, 4, 'inscripciones', 'UPDATE', '{"estado_tramite_id":2}', '{"estado_tramite_id":4}', '2026-05-12 08:01:00', '127.0.0.1', 'Mozilla/5.0'),
(3, 4, 'inscripciones', 'UPDATE', '{"estado_tramite_id":5}', '{"estado_tramite_id":6}', '2026-06-16 13:06:00', '127.0.0.1', 'Mozilla/5.0'),
(4, 4, 'carnets', 'INSERT', 'null', '{"numero_carnet":"CAR-2026-0001"}', '2026-06-20 09:01:00', '127.0.0.1', 'Mozilla/5.0'),
(5, 4, 'comprobantes_tramite', 'INSERT', 'null', '{"codigo_comprobante":"COM-2026-0001"}', '2026-05-10 09:30:01', '127.0.0.1', 'Mozilla/5.0'),
(6, 2, 'inscripciones', 'INSERT', 'null', '{"id":2,"estado_tramite_id":4}', '2026-05-12 10:15:00', '127.0.0.1', 'Mozilla/5.0'),
(7, 3, 'inscripciones', 'INSERT', 'null', '{"id":3,"estado_tramite_id":7}', '2026-05-14 12:20:00', '127.0.0.1', 'Mozilla/5.0');

INSERT INTO notificaciones (id, usuario_id, tipo, asunto, mensaje, enviado, fecha_creacion, fecha_envio) VALUES
(1, 1, 'carnet_emitido', 'Tu carnet esta listo', 'Tu carnet de manipulador fue emitido correctamente.', 1, '2026-06-20 09:05:00', '2026-06-20 09:06:00'),
(2, 2, 'documentacion', 'Revisar documentos', 'Falta revisar comprobante de pago.', 0, '2026-05-12 10:35:00', NULL),
(3, 3, 'rechazo', 'Inscripcion rechazada', 'Tu inscripcion no pudo continuar por falta de cupos.', 1, '2026-05-14 12:50:00', '2026-05-14 12:51:00'),
(4, 4, 'sistema', 'Carga inicial', 'Base de datos de prueba generada correctamente.', 1, '2026-05-01 07:40:00', '2026-05-01 07:40:10');

SET FOREIGN_KEY_CHECKS = 1;
