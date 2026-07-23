/*==========================================================
    BASE DEMO
    PARTE 1
==========================================================*/

SET FOREIGN_KEY_CHECKS=0;

/*==========================================================
    LIMPIEZA
==========================================================*/

DELETE FROM carnets;
DELETE FROM resultado_examen;
DELETE FROM habilitaciones_examen;
DELETE FROM documentos;
DELETE FROM inscripciones;
DELETE FROM examenes;
DELETE FROM cursos;

ALTER TABLE cursos AUTO_INCREMENT=1;
ALTER TABLE examenes AUTO_INCREMENT=1;
ALTER TABLE inscripciones AUTO_INCREMENT=1;
ALTER TABLE documentos AUTO_INCREMENT=1;
ALTER TABLE habilitaciones_examen AUTO_INCREMENT=1;
ALTER TABLE resultado_examen AUTO_INCREMENT=1;
ALTER TABLE carnets AUTO_INCREMENT=1;

SET FOREIGN_KEY_CHECKS=1;


/*==========================================================
    CURSOS
==========================================================*/

INSERT INTO cursos
(nombre,modalidad,descripcion,activo,fecha_inicio,hora_inicio,ubicacion,cupos)
VALUES
(
'Curso Manipulación de Alimentos - Julio',
'presencial',
'Curso oficial de manipulación segura de alimentos.',
1,
'2026-07-20',
'08:30:00',
'Delegación Norte',
30
);

INSERT INTO cursos
(nombre,modalidad,descripcion,activo,fecha_inicio,hora_inicio,ubicacion,cupos)
VALUES
(
'Curso Manipulación de Alimentos - Agosto',
'presencial',
'Curso oficial de manipulación segura de alimentos.',
1,
'2026-08-10',
'08:30:00',
'Delegación Centro',
30
);


/*==========================================================
    EXÁMENES
==========================================================*/

INSERT INTO examenes
(fecha,hora,ubicacion,aula,cupos,activo)
VALUES
('2026-08-20','10:00:00','Delegación Norte','Sala A',30,1);

INSERT INTO examenes
(fecha,hora,ubicacion,aula,cupos,activo)
VALUES
('2026-08-27','10:00:00','Delegación Norte','Sala B',30,1);

INSERT INTO examenes
(fecha,hora,ubicacion,aula,cupos,activo)
VALUES
('2026-09-17','09:30:00','Delegación Centro','Sala 1',40,1);

INSERT INTO examenes
(fecha,hora,ubicacion,aula,cupos,activo)
VALUES
('2026-09-24','09:30:00','Delegación Centro','Sala 2',40,1);

/*==========================================================
    INSCRIPCIONES
==========================================================*/

/* Alumno2 - Curso pendiente */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,observaciones)
VALUES
(3,1,1,1,'Inscripción pendiente');

/* Alumno3 - Documentación pendiente */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,observaciones)
VALUES
(4,1,1,2,'Falta completar documentación');

/* Alumno4 - Curso aprobado */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,fecha_fin_curso,observaciones)
VALUES
(5,1,1,5,'2026-08-15','Curso aprobado');

/* Alumno5 - Curso aprobado */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,fecha_fin_curso)
VALUES
(6,1,1,5,'2026-08-10');

/* Alumno5 - Inscripto al examen */

INSERT INTO inscripciones
(usuario_id,examen_id,tipo_inscripcion_id,estado_tramite_id)
VALUES
(6,1,2,4);

/* Alumno10 - Curso aprobado */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,fecha_fin_curso)
VALUES
(7,1,1,5,'2026-08-10');

/* Alumno10 - Examen aprobado */

INSERT INTO inscripciones
(usuario_id,examen_id,tipo_inscripcion_id,estado_tramite_id)
VALUES
(7,2,2,5);

/* Alumno11 - Curso aprobado */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,fecha_fin_curso)
VALUES
(8,1,1,5,'2026-08-10');

/* Alumno11 - Carnet emitido */

INSERT INTO inscripciones
(usuario_id,examen_id,tipo_inscripcion_id,estado_tramite_id)
VALUES
(8,2,2,8);

/* Alumno10Tres - Rechazado */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,observaciones)
VALUES
(9,1,1,6,'Documentación rechazada');

/* Alumno12 - Curso aprobado */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,fecha_fin_curso)
VALUES
(10,1,1,5,'2025-01-01');

/* Integral 1 */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id,fecha_fin_curso)
VALUES
(12,2,1,5,'2026-08-20');

/* Integral 2 */

INSERT INTO inscripciones
(usuario_id,curso_id,tipo_inscripcion_id,estado_tramite_id)
VALUES
(13,2,1,1);

/*==========================================================
    DOCUMENTOS
==========================================================*/

/* Alumno3 - Solo DNI */

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(4,'dni','dni.pdf','uploads/dni4.pdf','aprobado');

/* Alumno4 - Todo aprobado */

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(5,'dni','dni.pdf','uploads/dni5.pdf','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(5,'foto_carnet','foto.jpg','uploads/foto5.jpg','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(5,'asistencia','asistencia.pdf','uploads/asistencia5.pdf','aprobado');


/* Alumno5 - Todo aprobado */

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(6,'dni','dni.pdf','uploads/dni6.pdf','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(6,'foto_carnet','foto.jpg','uploads/foto6.jpg','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(6,'asistencia','asistencia.pdf','uploads/asistencia6.pdf','aprobado');


/* Alumno10 - Moodle */

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(7,'dni','dni.pdf','uploads/dni7.pdf','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(7,'foto_carnet','foto.jpg','uploads/foto7.jpg','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(7,'moodle','moodle.pdf','uploads/moodle7.pdf','aprobado');


/* Alumno11 */

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(8,'dni','dni.pdf','uploads/dni8.pdf','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(8,'foto_carnet','foto.jpg','uploads/foto8.jpg','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(8,'moodle','moodle.pdf','uploads/moodle8.pdf','aprobado');


/* Alumno10Tres - Rechazado */

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado,observaciones)
VALUES
(9,'dni','dni.pdf','uploads/dni9.pdf','rechazado','Documento ilegible');


/* Alumno12 */

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(10,'dni','dni.pdf','uploads/dni10.pdf','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(10,'foto_carnet','foto.jpg','uploads/foto10.jpg','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(10,'asistencia','asistencia.pdf','uploads/asistencia10.pdf','aprobado');


/* Integral 1 */

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(12,'dni','dni.pdf','uploads/dni12.pdf','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(12,'foto_carnet','foto.jpg','uploads/foto12.jpg','aprobado');

INSERT INTO documentos
(usuario_id,tipo_documento,nombre_original,ruta_archivo,estado)
VALUES
(12,'asistencia','asistencia.pdf','uploads/asistencia12.pdf','aprobado');

/*==========================================================
    HABILITACIONES PARA EXAMEN
==========================================================*/

/* Alumno4 */

INSERT INTO habilitaciones_examen
(usuario_id,curso_id,fecha_habilitacion,fecha_vencimiento,activa)
VALUES
(5,1,'2026-08-11','2027-02-11',1);

/* Alumno5 */

INSERT INTO habilitaciones_examen
(usuario_id,curso_id,fecha_habilitacion,fecha_vencimiento,activa)
VALUES
(6,1,'2026-08-11','2027-02-11',1);

/* Alumno10 */

INSERT INTO habilitaciones_examen
(usuario_id,curso_id,fecha_habilitacion,fecha_vencimiento,activa)
VALUES
(7,1,'2026-08-11','2027-02-11',1);

/* Alumno11 */

INSERT INTO habilitaciones_examen
(usuario_id,curso_id,fecha_habilitacion,fecha_vencimiento,activa)
VALUES
(8,1,'2026-08-11','2027-02-11',1);

/* Alumno12 - vencida */

INSERT INTO habilitaciones_examen
(usuario_id,curso_id,fecha_habilitacion,fecha_vencimiento,activa)
VALUES
(10,1,'2025-01-10','2025-07-11',0);

/* Integral */

INSERT INTO habilitaciones_examen
(usuario_id,curso_id,fecha_habilitacion,fecha_vencimiento,activa)
VALUES
(12,2,'2026-09-01','2027-03-01',1);



/*==========================================================
    RESULTADOS DE EXAMEN
==========================================================*/

/* Inscripción 7 = Alumno10 */

INSERT INTO resultado_examen
(inscripcion_id,examen_id,nota,aprobado,fecha_resultado)
VALUES
(7,2,9.50,1,NOW());

/* Inscripción 9 = Alumno11 */

INSERT INTO resultado_examen
(inscripcion_id,examen_id,nota,aprobado,fecha_resultado)
VALUES
(9,2,8.75,1,NOW());



/*==========================================================
    CARNETS
==========================================================*/

/* Alumno11 */

INSERT INTO carnets
(
inscripcion_id,
numero_carnet,
fecha_emision,
fecha_vencimiento,
ruta_pdf,
vigente
)
VALUES
(
9,
'MA-2026-000001',
CURDATE(),
DATE_ADD(CURDATE(),INTERVAL 3 YEAR),
'carnets/MA-2026-000001.pdf',
1
);