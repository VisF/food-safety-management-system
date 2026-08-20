# Food Safety Management System

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
food-safety-management-system/
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
└── Router.php           # Application router
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
xampp/htdocs/food-safety-management-system
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
