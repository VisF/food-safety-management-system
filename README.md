# Sistema Integral de Gestión de Carnets de Manipuladores de Alimentos

## Índice

1. [Descripción y alcance](#descripción-y-alcance)
2. [Funciones principales](#funciones-principales)
3. [Arquitectura y estructura](#arquitectura-y-estructura)
4. [Flujos principales](#flujos-principales)
5. [Configuración y ejecución](#configuración-y-ejecución)
6. [Documentación y estado](#documentación-y-estado)

## Descripción y alcance

El Sistema Integral de Gestión de Carnets de Manipuladores de Alimentos es una aplicación web PHP para centralizar la capacitación, inscripción, documentación, evaluación y certificación de personas manipuladoras de alimentos. El sistema organiza el circuito municipal y ofrece accesos diferenciados para ciudadanos, personal administrativo e inspectores.

La aplicación complementa a DIPA y no lo reemplaza. DIPA continúa siendo el sistema responsable de la emisión oficial del carnet. En este proyecto se registran las instancias previas, se valida la documentación, se siguen los estados del trámite y se carga el carnet oficial una vez emitido externamente.

## Funciones principales

### Portal ciudadano

- Registro, autenticación, cierre de sesión y gestión del perfil.
- Inscripción a cursos y mesas de examen.
- Carga y seguimiento de DNI, foto carnet y certificado Moodle cuando corresponde.
- Visualización de observaciones, estados, asistencia y resultados.
- Consulta y descarga del carnet cargado por administración.
- Avisos y notificaciones vinculados con el estado del trámite.

### Gestión administrativa

- Administración de usuarios, roles y estado de las cuentas.
- Alta, edición, activación y desactivación de cursos y exámenes.
- Gestión de fechas, modalidades, ubicaciones, cupos e inscriptos.
- Revisión, aprobación y rechazo de documentación con observaciones.
- Registro de asistencia, notas, resultados y estados del trámite.
- Carga, descarga, consulta y anulación lógica de carnets emitidos por DIPA.
- Consulta de actividad, reportes y datos necesarios para la operación.

### Consulta pública e inspección

La consulta pública permite verificar un carnet mediante CUIL/CUIT, revisar titularidad, vigencia, número y fechas, y acceder únicamente a los archivos que el sistema autorice. No debe mostrar domicilio, correo, documentación interna ni información ajena a la verificación necesaria.

### Seguridad y auditoría

La aplicación utiliza sesiones, roles, middleware, validación de entradas, protección CSRF, consultas PDO preparadas, validación de archivos, reglas de negocio, registros centralizados y auditoría de actividad administrativa. Las contraseñas se almacenan mediante funciones de hash y los archivos descargables deben comprobar la autorización antes de entregarse.

## Arquitectura y estructura

El proyecto sigue una arquitectura MVC por capas. El navegador envía la solicitud a `index.php`; las rutas y middleware determinan el acceso; los controladores coordinan casos de uso; los servicios concentran la lógica; los repositorios encapsulan la persistencia; y las vistas PHP generan HTML.

```text
Navegador
   |
   v
index.php -> routes -> middleware -> controllers
                                      |
                                      v
                                  servicios
                                      |
                                      v
                                repositorios -> MySQL/MariaDB
```

Las carpetas principales son `Controller/`, `Core/`, `DTO/`, `Helpers/`, `Jobs/`, `Middleware/`, `Policy/`, `Repository/`, `Servicios/`, `Validators/`, `Views/`, `config/`, `db/`, `css/`, `js/`, `docs/`, `logs/` y `uploads/`. El frontend utiliza HTML/PHP renderizado en servidor, CSS organizado por componentes y JavaScript nativo.

## Flujos principales

El flujo general es: registro, inicio de sesión, curso o acceso habilitado a examen, carga y revisión de documentos, asistencia, examen, resultado, emisión oficial en DIPA, carga del carnet y consulta posterior.

El curso presencial utiliza asistencia para habilitar el examen. El curso virtual requiere finalización en Moodle y certificado. La persona recursante puede rendir nuevamente dentro del plazo configurado sin repetir el curso cuando las reglas lo permiten. La renovación de un carnet vencido requiere examen y verificación del antecedente en DIPA.

## Configuración y ejecución

Copie `.env.example` como `.env` y configure host, nombre, usuario y contraseña de la base de datos. Ajuste `BASE_URL` cuando la instalación utilice otra carpeta. Mantenga `APP_DEBUG=true` solamente durante el desarrollo y proteja el archivo `.env`, los logs y los uploads en producción.

Ejecute primero `db/schema.sql` y revise que `db/seed.sql` corresponda exactamente al esquema. El seed actual contiene referencias a tablas auxiliares que no aparecen en el esquema reducido, por lo que ambos archivos deben sincronizarse antes de una instalación limpia. En XAMPP, coloque el proyecto dentro de `htdocs`, inicie Apache y MySQL/MariaDB y abra la URL base configurada.

## Documentación y estado

La documentación técnica y funcional bilingüe se encuentra en [`docs/`](./docs). Consulte [`docs/README.md`](./docs/README.md) como índice general, los documentos técnicos para arquitectura e instalación y `docs/documentacion-octubre/` para manuales operativos.

El proyecto se encuentra en desarrollo activo. La arquitectura central y los flujos principales están implementados, mientras continúan las tareas de integración, pruebas, refinamiento y preparación para despliegue. Antes de usarlo fuera de desarrollo, pruebe los flujos completos, cambie las credenciales de demostración y verifique las configuraciones de seguridad.

---# Food Safety Management System

A full-stack web application for managing the training, registration, examination, documentation, and certification process for food handlers.

The system was designed as a centralized platform to manage the complete lifecycle of food-handler certifications, providing dedicated workflows for citizens, administrators, and inspectors.

## Overview

The **Food Safety Management System** provides a centralized solution for managing food-handler training and certification processes.

The platform supports multiple user roles and business workflows, including course registration, document submission and validation, examination management, certification, administrative operations, and public certificate verification.

The project focuses on applying software architecture principles, separation of responsibilities, reusable components, database-driven business logic, and secure web development practices.

## Key Features

### 👤 Citizen Portal

* User registration and authentication
* Profile management
* Course registration
* Examination registration
* Document submission
* Documentation status tracking
* Examination results
* Certification status tracking
* Personal dashboard
* Email notifications

### 🛠️ Administrative Management

* User management
* Role and permission management
* Course management
* Examination management
* Registration management
* Document validation
* Examination result management
* Certificate management
* Administrative activity tracking
* Reports and statistics

### 🔎 Inspector Portal

* Search for certificates using identification numbers
* Certificate validity verification
* Access to relevant certification information
* Registration of inspection-related activity

### 📄 Documentation Management

The system manages the documentation required throughout the certification process.

Features include:

* Document uploads
* File validation
* Document status management
* Approval and rejection workflows
* Document previews and downloads
* User-specific documentation tracking

### 🎓 Courses and Examinations

* Course creation and management
* Available course dates
* Registration management
* Examination scheduling
* Attendance tracking
* Examination results
* Eligibility validation

### 🎫 Certificate Management

* Certificate issuance
* Certificate status tracking
* Certificate validity verification
* Public certificate consultation
* Administrative certificate management

### 🔐 Security and Auditing

* Authentication and session management
* Role-based access control
* Request validation
* File upload validation
* Business-rule validation
* Centralized logging
* Administrative activity auditing

## Architecture

The application follows an MVC-based layered architecture designed to separate responsibilities between different parts of the system.

```text
                    ┌──────────────────────┐
                    │      Web Browser     │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │      Routing Layer   │
                    │    AltoRouter /      │
                    │    Application       │
                    │       Router         │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │     Controllers      │
                    │  HTTP / Use Cases    │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │      Services        │
                    │   Business Logic     │
                    └──────────┬───────────┘
                               │
                    ┌──────────┴───────────┐
                    ▼                      ▼
          ┌──────────────────┐   ┌──────────────────┐
          │   Repositories   │   │      DTOs        │
          │ Data Persistence │   │ Data Transfer    │
          └────────┬─────────┘   └──────────────────┘
                   │
                   ▼
          ┌──────────────────────┐
          │   MySQL / MariaDB    │
          └──────────────────────┘
```

Additional components such as middleware, policies, validators, jobs, helpers, and centralized configuration support the application's business and infrastructure concerns.

## Project Structure

```text
manipulacionDeAlimentos/
│
├── Controller/          # HTTP request handling and application flows
├── Core/                # Core application components
├── DTO/                 # Data Transfer Objects
├── Helpers/             # Reusable helper components
├── Jobs/                # Background or scheduled tasks
├── Middleware/          # Request/session middleware
├── Policy/              # Authorization and access policies
├── Repository/          # Data access and persistence
├── Servicios/           # Business logic and application services
├── Validators/          # Input and business-rule validation
├── Views/               # Server-rendered web views
│
├── config/              # Application configuration
├── css/                 # Stylesheets
├── database/            # Database scripts and resources
├── db/                  # Database-related resources
├── docs/                # Technical documentation
├── js/                  # Frontend JavaScript
├── logs/                # Application logs
├── routes/              # Application routes
├── tools/               # Development utilities
├── uploads/             # Uploaded document storage
│
├── .env.example         # Environment configuration template
├── .htaccess            # Apache configuration
├── index.php            # Application entry point
├── AltoRouter.php       # Routing library
```

## Technology Stack

### Backend

* PHP
* Object-Oriented Programming
* MVC Architecture
* Layered Architecture
* AltoRouter
* REST-oriented application design
* Session-based authentication

### Database

* MySQL / MariaDB
* Relational database design
* SQL
* Repository-based data access

### Frontend

* HTML5
* CSS3
* JavaScript
* Responsive Web Design

### Development Practices

* DTOs
* Repository Pattern
* Service Layer
* Middleware
* Role-Based Access Control
* Input Validation
* Centralized Logging
* Exception Handling
* Separation of Concerns
* SOLID principles
* Clean Code practices

## Main Application Workflows

### Course Registration

```text
User
  ↓
Available Courses
  ↓
Course Registration
  ↓
Registration Validation
  ↓
Confirmation
```

### Documentation Submission

```text
User
  ↓
Document Upload
  ↓
File Validation
  ↓
Document Storage
  ↓
Administrative Review
  ↓
Approved / Rejected
```

### Examination Process

```text
Course / Eligibility
        ↓
Examination Registration
        ↓
Attendance
        ↓
Examination
        ↓
Result
        ↓
Certification Process
```

### Certificate Verification

```text
Identification Number
        ↓
Certificate Search
        ↓
Validity Verification
        ↓
Certificate Status
```

## Configuration

The application uses environment-based configuration.

Create a local `.env` file based on the provided template:

```bash
cp .env.example .env
```

Then configure the required database and application settings according to your local environment.

> Never commit real credentials or sensitive environment variables to the repository.

## Running Locally

### Requirements

* PHP 8.2+
* Apache
* MySQL or MariaDB
* Git
* XAMPP, WAMP, or another compatible PHP development environment

### Installation

Clone the repository:

```bash
git clone https://github.com/VisF/food-safety-management-system.git
```

Move the project into your local web server directory, for example:

```text
xampp/htdocs/manipulacionDeAlimentos
```

Configure the environment:

```text
.env.example → .env
```

Create and configure the database using the SQL resources provided in the `database/` directory.

Then start Apache and MySQL/MariaDB from your local development environment and access the application through your configured local URL.

## Documentation

Additional technical documentation is available in the [`docs`](./docs) directory.

The documentation includes information about:

* Application architecture
* View/controller mapping
* Project structure
* Database design
* Technical verification
* Development decisions

For the delivery-oriented documentation in Spanish, start with [`docs/README.md`](./docs/README.md). It provides separate documents for database, backend, frontend, routes, functional flows, installation and user operation.

## Project Goals

The project was developed with a strong focus on software engineering practices rather than only implementing individual features.

The main goals are:

* Build a maintainable MVC application
* Separate presentation, business logic, and persistence concerns
* Centralize validation and authorization rules
* Provide clear boundaries between application layers
* Implement reusable and testable components
* Manage complex business workflows
* Maintain an auditable record of important system operations
* Provide a responsive and accessible user interface

## Current Status

The project is under active development.

The core application architecture and main business workflows are implemented, while additional work continues on integration, testing, refinement, and deployment-related concerns.

## Future Improvements

Planned improvements include:

* Expanded automated testing
* Further UI/UX improvements
* Additional integration testing
* Production deployment configuration
* Further database and query optimization
* Expanded external-system integrations
* Additional monitoring and operational tooling

## Author

**Facundo Vis**

Full Stack Developer focused on Java, Spring Boot, software architecture, REST APIs, databases, and modern web development.

* GitHub: https://github.com/VisF
* LinkedIn: https://www.linkedin.com/in/facundo-vis/
