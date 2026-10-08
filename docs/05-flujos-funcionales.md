# Flujos funcionales

## 1. Alta y acceso

1. La persona completa nombre, apellido, DNI, correo y contraseña.
2. El sistema valida campos y unicidad de DNI/correo.
3. Se crea el usuario con contraseña cifrada y rol ciudadano.
4. La persona inicia sesión.
5. Se crea la sesión con su identificador y roles.

## 2. Curso presencial

1. El ciudadano consulta cursos activos y cupos.
2. Se inscribe al curso.
3. Carga DNI y foto carnet.
4. Administración revisa la documentación.
5. Se registra la asistencia o aprobación del curso.
6. El ciudadano queda habilitado para el examen según las reglas del trámite.
7. Se registra el resultado del examen.
8. Si aprueba, administración continúa la gestión en DIPA y carga el carnet emitido.

## 3. Curso virtual

1. El ciudadano completa el curso en Moodle.
2. Se inscribe a una mesa de examen.
3. Carga DNI, foto y certificado Moodle.
4. Administración valida los documentos.
5. Rinde el examen presencial.
6. Se registra el resultado y, si corresponde, se carga el carnet.

## 4. Recursante

1. Se identifica un resultado de examen desaprobado.
2. Se verifica que la nueva inscripción se encuentre dentro del plazo configurado.
3. El ciudadano elige una nueva mesa.
4. Se registra la nueva inscripción sin repetir el curso.
5. Se registra el resultado del reexamen.

El valor inicial de referencia se encuentra en `configuracion_sistema` como `plazo_recursante_dias`; debe confirmarse con la regla vigente del área antes de producción.

## 5. Renovación

1. El ciudadano se inscribe a una instancia de examen.
2. Administración verifica antecedentes y documentación.
3. Se registra el resultado.
4. Si aprueba, se consulta la situación anterior en DIPA.
5. Se carga el nuevo carnet con fechas de emisión y vencimiento.

## 6. Revisión documental

Cada archivo comienza en estado `pendiente`. Administración puede:

- aprobarlo y registrar fecha de revisión;
- rechazarlo con observaciones;
- descargarlo para verificarlo o utilizarlo en el proceso externo.

El ciudadano visualiza el estado y debe volver a cargar el archivo cuando sea necesario.

## 7. Emisión y consulta de carnet

1. El administrador abre el trámite aprobado.
2. Carga el número de carnet, fechas y PDF recibido desde DIPA.
3. El sistema registra el carnet vinculado a la inscripción.
4. El ciudadano descarga su carnet desde su sesión.
5. Un inspector o una persona autorizada puede consultar el estado por DNI desde la consulta pública.

## 8. Roles

| Rol | Responsabilidades |
|---|---|
| Usuario | Registro, perfil, inscripciones, documentación y descarga de su carnet |
| Administrador | Gestión integral de cursos, exámenes, usuarios, documentos, trámites y carnets |
| Inspector | Verificación de carnets según los permisos habilitados |
| Público | Consulta limitada de vigencia/estado |

