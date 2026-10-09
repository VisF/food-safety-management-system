# Auditoría de Portabilidad y Normalización de Rutas

**Proyecto:** Manipulación de Alimentos  
**Fecha:** Octubre de 2026  
**Propósito:** Documento de referencia para futuros desarrolladores

---

## 1. Objetivo

Documentar la auditoría realizada sobre el proyecto para detectar problemas de portabilidad entre Windows/Linux, referencias de rutas potencialmente incorrectas y falsos positivos producidos por código PHP, JavaScript, expresiones regulares, URLs y documentación.

El objetivo **no es reemplazar indiscriminadamente `\` por `/`**.

La auditoría busca primero identificar el contexto de cada coincidencia y evitar que una normalización automática rompa código válido.

---

## 2. Entorno y alcance

- **Proyecto:** `C:\xampp\htdocs\manipulacionDeAlimentos`
- **Entorno utilizado:** Windows + XAMPP.
- **Herramienta auxiliar de auditoría:** Node.js.
- Node.js se utiliza **únicamente como herramienta de desarrollo/auditoría**.
- Node.js **no es una dependencia de ejecución de la aplicación**.
- **Directorio de herramientas:** `tools/path-audit`

### Directorios excluidos de la auditoría

- `.git`
- `.venv`
- `node_modules`
- `vendor`
- `target`
- `build`
- `dist`
- `coverage`
- `.idea`
- `.vscode`
- `path-audit`

El directorio `.venv` no debe modificarse como parte de esta tarea.

---

## 3. Criterio fundamental

No debe realizarse una sustitución global de `\` por `/` ni de `/` por `\`.

En PHP y JavaScript estos caracteres pueden formar parte de:

- namespaces;
- caracteres de escape;
- expresiones regulares;
- URLs;
- URIs;
- cadenas de conexión;
- expresiones de código;
- rutas válidas;
- documentación;
- configuraciones de servicios.

Antes de modificar un archivo se debe:

1. Auditar.
2. Identificar el contexto.
3. Clasificar el hallazgo.
4. Determinar si realmente representa un problema.
5. Modificar solamente lo necesario.
6. Ejecutar una auditoría/verificación posterior.

---

# 4. Evolución de la auditoría

## V2

Resultado:

- 37 directorios.
- 271 archivos.
- 190 archivos de texto.
- 25 posibles separadores Windows.
- 13 referencias `../`.
- 9 referencias `./`.

Conclusión:

La mayoría de los resultados fueron falsos positivos provocados por:

- expresiones regulares;
- namespaces PHP;
- caracteres de escape;
- enlaces relativos de Markdown;
- sintaxis válida del código.

No se modificaron archivos del proyecto.

---

## V3

Resultado:

- 36 directorios.
- 271 archivos.
- 191 archivos de texto.
- 0 críticos.
- 48 altos.
- 15 medios.
- 19 informativos.

Se detectaron numerosos falsos positivos debido a URLs como:

```text
https://...
```

que fueron interpretadas incorrectamente como posibles rutas Windows.

También apareció un caso potencialmente real:

```text
unix:///var/run/clamav/clamd.ctl
```

correspondiente a la configuración de ClamAV.

---

## V4

Resultado:

- 36 directorios.
- 271 archivos.
- 191 archivos de texto.
- 48 `REAL`.
- 3 `REVIEW`.
- 464 `SAFE`.

Los resultados `REAL` continuaron incluyendo principalmente falsos positivos relacionados con URLs.

Los tres casos `REVIEW` fueron posteriormente identificados como falsos positivos:

### JavaScript

```javascript
RegExp.exec()
```

### PHP / PDO

```php
$this->db->exec($sql);
```

Estos usos son válidos y no representan rutas.

---

## V5

Resultado:

- 36 directorios.
- 271 archivos.
- 191 archivos de texto.
- **0 HIGH**
- **3 MEDIUM**
- **464 SAFE**

Los tres MEDIUM fueron:

```text
js\crear-examen.js
Repository\AlertaRepository.php
Repository\PlazoRecursanteRepository.php
```

Todos fueron confirmados como falsos positivos.

### Importante

V5 no detectó el endpoint:

```text
unix:///var/run/clamav/clamd.ctl
```

Esto no significa que sea seguro.

La razón es que la regla de V5 buscaba determinados patrones de rutas Unix y no contemplaba correctamente este formato de URI/socket:

```text
unix:///...
```

Por lo tanto, el caso de ClamAV fue investigado manualmente.

---

# 5. Falsos positivos confirmados

## 5.1 `RegExp.exec()`

Archivo:

```text
js\crear-examen.js
```

El auditor interpretó:

```javascript
.exec()
```

como una posible referencia de ruta.

Es sintaxis válida de JavaScript y **no debe modificarse**.

---

## 5.2 `PDO::exec()`

Archivos:

```text
Repository\AlertaRepository.php
Repository\PlazoRecursanteRepository.php
```

El auditor interpretó:

```php
$this->db->exec($sql);
```

como una posible referencia sospechosa.

Es una llamada válida de PDO y **no debe modificarse**.

---

## 5.3 Namespaces PHP

Ejemplos encontrados:

```php
\Exception
\PDO::FETCH_ASSOC
App\Config\Database
```

Las barras invertidas forman parte de la sintaxis de namespaces de PHP.

**No son rutas de Windows y no deben normalizarse.**

---

## 5.4 Expresiones regulares

Ejemplo:

```php
rtrim(..., '/\\')
```

La presencia de `\` dentro de una expresión regular o cadena de caracteres no significa necesariamente que exista una ruta Windows.

**No debe modificarse automáticamente.**

---

## 5.5 URLs y URIs

Ejemplos:

```text
https://...
http://...
```

Las barras forman parte de la sintaxis de una URL.

**No deben modificarse.**

---

## 5.6 Documentación

Referencias como:

```text
../archivo.md
./documentacion.md
```

pueden ser enlaces relativos perfectamente válidos.

No deben modificarse simplemente por contener `../` o `./`.

---

# 6. Hallazgo real: ClamAV

Se encontró una dependencia real de infraestructura Unix/Linux en:

```text
Servicios\UploadService.php
```

La configuración es:

```php
private const AV_SOCKET =
    'unix:///var/run/clamav/clamd.ctl';
```

Este valor representa un endpoint de ClamAV mediante un **socket Unix**.

No es un falso positivo.

---

## 6.1 Uso real

La constante se utiliza posteriormente mediante:

```php
$socket =
    @stream_socket_client(
        self::AV_SOCKET,
        $errno,
        $errstr,
        2
    );
```

Por lo tanto, `AV_SOCKET` forma parte del flujo real de escaneo de archivos.

No es una constante sin uso.

---

## 6.2 Comportamiento cuando ClamAV no está disponible

El código contempla que ClamAV no esté disponible.

Si:

```php
stream_socket_client(...)
```

no puede conectarse, se registra:

```text
CLAMAV_NO_DISPONIBLE
```

y el método continúa devolviendo:

```php
[
    'success' => true,
    'mensaje' => '',
    'amenaza' => null
]
```

Por lo tanto, actualmente la ausencia de ClamAV no provoca que la aplicación falle directamente.

---

# 7. Qué NO debe hacerse con `AV_SOCKET`

No se debe:

- reemplazar automáticamente `/` por `\`;
- convertir la ruta a una ruta Windows;
- modificarla mediante una regla genérica de normalización;
- interpretar `unix:///var/run/clamav/clamd.ctl` como una simple ruta de archivos;
- cambiar el endpoint sin verificar primero la configuración de ClamAV del entorno destino.

Por ejemplo, **NO hacer**:

```text
unix:///var/run/clamav/clamd.ctl
```

→

```text
unix:\\var\\run\\clamav\\clamd.ctl
```

Eso sería incorrecto.

El problema no es simplemente el separador de directorios: se trata de un **endpoint de comunicación de un servicio**.

---

# 8. Rutas consideradas portables

En `UploadService.php` existen rutas como:

```php
private const LOG_FILE =
    __DIR__ . '/../logs/upload_service.log';
```

```php
private const CARPETA_UPLOADS =
    __DIR__ . '/../uploads';
```

```php
public const CARPETA_DOCUMENTOS =
    __DIR__ . '/../uploads/documentos';
```

```php
private const CARPETA_CARNETS =
    __DIR__ . '/../uploads/carnets';
```

Estas rutas son consideradas correctas y portables.

El uso de:

```php
__DIR__
```

permite construir la ruta a partir de la ubicación real del archivo PHP.

No deben modificarse como parte de una supuesta corrección de compatibilidad Windows/Linux.

---

# 9. Estrategia recomendada para la normalización

La normalización debe trabajar por categorías.

No es lo mismo:

- normalizar el nombre de un archivo;
- normalizar el nombre de una carpeta;
- modificar una ruta dentro del código;
- modificar una URL;
- modificar una URI;
- modificar una expresión regular;
- modificar la configuración de un servicio.

Por lo tanto:

| Elemento | Acción |
|---|---|
| Nombres de archivos | Normalizar según convención |
| Nombres de directorios | Normalizar según convención |
| `__DIR__` | No modificar |
| Rutas relativas PHP válidas | No modificar |
| Namespaces PHP | No modificar |
| Expresiones regulares | No modificar |
| URLs | No modificar |
| URIs | No modificar |
| `PDO::exec()` | No modificar |
| `RegExp.exec()` | No modificar |
| Endpoints de servicios | No modificar automáticamente |
| Configuración específica de plataforma | Proteger |
| `AV_SOCKET` | Proteger |

---

# 10. Exclusiones del normalizador

Si el normalizador modifica **contenido del código fuente**, debe tener mecanismos para proteger configuraciones especiales.

El caso identificado durante esta auditoría es:

```text
Servicios/UploadService.php
```

concretamente:

```php
AV_SOCKET
```

Sin embargo, **no es recomendable excluir todo `UploadService.php`** si el normalizador puede trabajar de manera más precisa.

La exclusión debería ser específica al contenido que no debe modificarse.

Si el normalizador solamente cambia nombres físicos de archivos y directorios, `AV_SOCKET` no necesita una exclusión porque el contenido PHP no será procesado.

---

# 11. Estado de la auditoría

| Aspecto | Estado |
|---|---|
| Auditoría V5 | Sin hallazgos HIGH |
| MEDIUM de V5 | 3 falsos positivos confirmados |
| Dependencia real encontrada | ClamAV |
| Endpoint de ClamAV | `unix:///var/run/clamav/clamd.ctl` |
| Rutas `__DIR__` | Correctas |
| Archivos del proyecto modificados durante la auditoría | Ninguno |
| Normalización automática | Pendiente |
| Verificación posterior | Pendiente |

---

# 12. Procedimiento para futuros desarrolladores

Antes de modificar rutas o nombres:

1. Ejecutar la auditoría.
2. Revisar los resultados.
3. Analizar los hallazgos en contexto.
4. Clasificar cada resultado.
5. Identificar falsos positivos.
6. Identificar dependencias específicas de plataforma.
7. Ejecutar el normalizador solamente sobre elementos seguros.
8. Ejecutar nuevamente la auditoría.
9. Comparar los resultados.
10. Realizar pruebas funcionales de la aplicación.

---

# 13. Reglas para futuros cambios

No introducir reglas globales como:

```text
replace "\" with "/"
```

o:

```text
replace "/" with "\"
```

sobre todo el código fuente.

Antes de crear una nueva regla para el normalizador, comprobar si el patrón puede aparecer legítimamente en:

- PHP;
- JavaScript;
- HTML;
- CSS;
- JSON;
- SQL;
- expresiones regulares;
- URLs;
- URIs;
- namespaces;
- documentación;
- configuración de servicios.

Si existe posibilidad de falso positivo, la regla debe analizar el contexto.

---

# 14. Dependencias específicas de plataforma

La auditoría encontró una dependencia concreta:

```text
ClamAV
└── unix:///var/run/clamav/clamd.ctl
```

Esto debe considerarse una **dependencia de infraestructura**, no un problema genérico de rutas.

Si el proyecto se despliega en otro sistema operativo, se debe revisar la configuración de ClamAV del nuevo entorno.

La solución futura recomendada es separar la configuración del endpoint del código de negocio, por ejemplo mediante configuración de entorno.

---

# 15. Node.js

Node.js fue utilizado para construir y ejecutar las herramientas de auditoría.

Esto **no convierte a Node.js en una dependencia del sistema de Manipulación de Alimentos**.

La aplicación principal sigue siendo PHP y debe poder ejecutarse sin Node.js.

Los scripts de:

```text
tools/path-audit
```

son herramientas auxiliares para los desarrolladores.

---

# 16. Decisión técnica

La auditoría demuestra que la estrategia correcta es una **normalización conservadora**.

Se pueden normalizar nombres de archivos y directorios según las convenciones definidas por el proyecto, pero no se debe modificar indiscriminadamente el contenido del código fuente.

Las configuraciones de infraestructura deben tratarse por separado.

En particular:

```php
private const AV_SOCKET =
    'unix:///var/run/clamav/clamd.ctl';
```

queda documentado como una dependencia específica de Unix/Linux y **no debe ser modificado por el normalizador de rutas**.

---

# 17. Flujo recomendado

El flujo de trabajo para futuras modificaciones será:

```text
┌─────────────────────┐
│       AUDITORÍA     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ CLASIFICAR HALLAZGOS│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│    NORMALIZACIÓN     │
│   SOLO SEGURA        │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│     VERIFICACIÓN     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   AUDITORÍA FINAL    │
└─────────────────────┘
```

La regla principal es:

> **Primero detectar, después clasificar, luego modificar y finalmente verificar.**

No realizar modificaciones masivas sin una auditoría previa.