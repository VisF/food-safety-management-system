<?php

declare(strict_types=1);

/**
 * Vista: admin_usuarios.php
 *
 * Gestión administrativa de usuarios.
 *
 * Responsabilidades:
 * - Mostrar estadísticas de usuarios.
 * - Listar usuarios.
 * - Buscar usuarios.
 * - Acceder a las acciones administrativas.
 */

require_once __DIR__ . '/BaseVista.php';

class AdminUsuariosVista extends BaseVista
{
    /**
     * Obtiene los datos por defecto de la vista.
     */
    private function getDefaultData(): array
    {
        return [
            'page_title' => 'Gestión de Usuarios',
            'success' => true,
            'usuarios' => [],
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0
        ];
    }

    /**
     * Renderiza la vista.
     */
    public function mostrar(array $data = []): void
    {
        $data = array_merge(
            $this->getDefaultData(),
            $data
        );

        $pageTitle = (string)$data['page_title'];
        $usuarios = is_array($data['usuarios'])
            ? $data['usuarios']
            : [];

        $total = (int)$data['total'];
        $activos = (int)$data['activos'];
        $inactivos = (int)$data['inactivos'];

        include __DIR__ . '/header.php';

        ?>
        <link
            rel="stylesheet"
            href="<?= $this->baseURL ?>css/Views/admin-usuarios.css"
        >
        <main class="admin-usuarios">

            <section class="admin-usuarios__hero">

                <div class="admin-usuarios__hero-content">

                    <div class="admin-usuarios__hero-icon">
                        <span class="material-symbols-outlined">
                            group
                        </span>
                    </div>

                    <div>
                        <p class="admin-usuarios__eyebrow">
                            Administración
                        </p>

                        <h1 class="admin-usuarios__title">
                            <?= $this->e($pageTitle); ?>
                        </h1>

                        <p class="admin-usuarios__description">
                            Administrá los usuarios registrados
                            en el sistema.
                        </p>
                    </div>

                </div>

                <div class="admin-usuarios__hero-actions">

                    <button
                        type="button"
                        class="app-vista-button app-vista-button--primary"
                        id="btn-nuevo-usuario"
                    >
                        <span class="material-symbols-outlined">
                            person_add
                        </span>

                        <span>
                            Nuevo usuario
                        </span>
                    </button>

                </div>

            </section>


            <?php if (
                isset($data['success'])
                && $data['success'] === false
            ): ?>

                <div
                    class="admin-usuarios__alert admin-usuarios__alert--error"
                    role="alert"
                >
                    <span class="material-symbols-outlined">
                        error
                    </span>

                    <span>
                        No se pudieron cargar los usuarios.
                    </span>
                </div>

            <?php endif; ?>


            <!-- ==========================================
                 ESTADÍSTICAS
                 ========================================== -->

            <section
                class="admin-usuarios__estadisticas"
                aria-label="Resumen de usuarios"
            >

                <article
                    class="admin-usuarios__stat"
                >

                    <div class="admin-usuarios__stat-icon">
                        <span class="material-symbols-outlined">
                            groups
                        </span>
                    </div>

                    <div class="admin-usuarios__stat-content">

                        <span class="admin-usuarios__stat-label">
                            Total
                        </span>

                        <strong class="admin-usuarios__stat-value">
                            <?= $total; ?>
                        </strong>

                    </div>

                </article>


                <article
                    class="admin-usuarios__stat admin-usuarios__stat--activo"
                >

                    <div class="admin-usuarios__stat-icon">
                        <span class="material-symbols-outlined">
                            person_check
                        </span>
                    </div>

                    <div class="admin-usuarios__stat-content">

                        <span class="admin-usuarios__stat-label">
                            Activos
                        </span>

                        <strong class="admin-usuarios__stat-value">
                            <?= $activos; ?>
                        </strong>

                    </div>

                </article>


                <article
                    class="admin-usuarios__stat admin-usuarios__stat--inactivo"
                >

                    <div class="admin-usuarios__stat-icon">
                        <span class="material-symbols-outlined">
                            person_off
                        </span>
                    </div>

                    <div class="admin-usuarios__stat-content">

                        <span class="admin-usuarios__stat-label">
                            Inactivos
                        </span>

                        <strong class="admin-usuarios__stat-value">
                            <?= $inactivos; ?>
                        </strong>

                    </div>

                </article>

            </section>


            <!-- ==========================================
                 LISTADO
                 ========================================== -->

            <section class="admin-usuarios__panel">

                <div class="admin-usuarios__panel-header">

                    <div>

                        <h2 class="admin-usuarios__panel-title">
                            Usuarios registrados
                        </h2>

                        <p class="admin-usuarios__panel-description">
                            Consultá y administrá los usuarios
                            del sistema.
                        </p>

                    </div>

                </div>


                <!-- BUSCADOR -->

                <div class="admin-usuarios__busqueda">

                    <label
                        for="buscar-usuarios"
                        class="admin-usuarios__search-label"
                    >
                        Buscar usuario
                    </label>

                    <div class="admin-usuarios__search">

                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >
                            search
                        </span>

                        <input
                            type="search"
                            id="buscar-usuarios"
                            class="admin-usuarios__search-input"
                            placeholder="Nombre, apellido, DNI o email..."
                            autocomplete="off"
                        >

                        <button
                            type="button"
                            id="limpiar-busqueda"
                            class="admin-usuarios__search-clear"
                            aria-label="Limpiar búsqueda"
                            hidden
                        >
                            <span class="material-symbols-outlined">
                                close
                            </span>
                        </button>

                    </div>

                </div>


                <!-- TABLA -->

                <div class="admin-usuarios__tabla-wrapper">

                    <table
                        class="admin-usuarios__tabla"
                        id="tabla-usuarios"
                    >

                        <thead>

                            <tr>

                                <th scope="col">
                                    Usuario
                                </th>

                                <th scope="col">
                                    DNI
                                </th>

                                <th scope="col">
                                    Email
                                </th>

                                <th scope="col">
                                    Rol
                                </th>

                                <th scope="col">
                                    Estado
                                </th>

                                <th scope="col">
                                    Registro
                                </th>

                                <th
                                    scope="col"
                                    class="admin-usuarios__th-acciones"
                                >
                                    Acciones
                                </th>

                            </tr>

                        </thead>

                        <tbody id="usuarios-tbody">

                        <?php if (empty($usuarios)): ?>

                            <tr>
                                <td
                                    colspan="7"
                                    class="admin-usuarios__empty"
                                >

                                    <span class="material-symbols-outlined">
                                        group_off
                                    </span>

                                    <strong>
                                        No hay usuarios registrados
                                    </strong>

                                    <span>
                                        Todavía no existen usuarios
                                        para mostrar.
                                    </span>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($usuarios as $usuario): ?>

                                <?php

                                /*
                                 * El repositorio puede devolver
                                 * arrays. También soportamos DTOs
                                 * para mantener la vista flexible.
                                 */

                                if (
                                    is_object($usuario)
                                    && method_exists(
                                        $usuario,
                                        'toArray'
                                    )
                                ) {
                                    $usuario = $usuario->toArray();
                                }

                                $id = (int)(
                                    $usuario['id']
                                    ?? $usuario['id_usuario']
                                    ?? 0
                                );

                                $nombre = (string)(
                                    $usuario['nombre']
                                    ?? ''
                                );

                                $apellido = (string)(
                                    $usuario['apellido']
                                    ?? ''
                                );

                                $dni = (string)(
                                    $usuario['dni']
                                    ?? ''
                                );

                                $email = (string)(
                                    $usuario['email']
                                    ?? ''
                                );

                                $activo = (bool)(
                                    $usuario['activo']
                                    ?? true
                                );

                                $roles = $usuario['roles']
                                    ?? [];

                                if (!is_array($roles)) {
                                    $roles = [$roles];
                                }

                                $rolPrincipal = !empty($roles)
                                    ? (string)$roles[0]
                                    : 'usuario';

                                $fechaCreacion = (string)(
                                    $usuario['fecha_creacion']
                                    ?? ''
                                );

                                $nombreCompleto = trim(
                                    $nombre . ' ' . $apellido
                                );

                                ?>

                                <tr
                                    class="admin-usuarios__fila"
                                    data-usuario="<?= $id; ?>"
                                    data-busqueda="<?= $this->e(
                                        strtolower(
                                            $nombreCompleto
                                            . ' '
                                            . $dni
                                            . ' '
                                            . $email
                                        )
                                    ); ?>"
                                >

                                    <td
                                        data-label="Usuario"
                                        class="admin-usuarios__usuario"
                                    >

                                        <div
                                            class="admin-usuarios__usuario-info"
                                        >

                                            <div
                                                class="admin-usuarios__avatar"
                                            >
                                                <span class="material-symbols-outlined">
                                                    person
                                                </span>
                                            </div>

                                            <div>

                                                <strong>
                                                    <?= $this->e(
                                                        $nombreCompleto
                                                    ); ?>
                                                </strong>

                                                <span>
                                                    ID #<?= $id; ?>
                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <td data-label="DNI">
                                        <?= $this->e($dni); ?>
                                    </td>


                                    <td
                                        data-label="Email"
                                        class="admin-usuarios__email"
                                    >
                                        <?= $this->e($email); ?>
                                    </td>


                                    <td data-label="Rol">

                                        <span
                                            class="admin-usuarios__rol"
                                        >
                                            <?= $this->e(
                                                ucfirst(
                                                    strtolower(
                                                        $rolPrincipal
                                                    )
                                                )
                                            ); ?>
                                        </span>

                                    </td>


                                    <td data-label="Estado">

                                        <?php if ($activo): ?>

                                            <span
                                                class="admin-usuarios__estado admin-usuarios__estado--activo"
                                            >
                                                <span
                                                    class="admin-usuarios__estado-dot"
                                                    aria-hidden="true"
                                                ></span>

                                                Activo
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="admin-usuarios__estado admin-usuarios__estado--inactivo"
                                            >
                                                <span
                                                    class="admin-usuarios__estado-dot"
                                                    aria-hidden="true"
                                                ></span>

                                                Inactivo
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td data-label="Registro">

                                        <?= $this->e(
                                            $fechaCreacion !== ''
                                                ? date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $fechaCreacion
                                                    )
                                                )
                                                : '-'
                                        ); ?>

                                    </td>


                                    <td
                                        data-label="Acciones"
                                        class="admin-usuarios__acciones"
                                    >

                                        <button
                                            type="button"
                                            class="admin-usuarios__accion"
                                            data-accion="editar"
                                            data-id="<?= $id; ?>"
                                            title="Editar usuario"
                                        >
                                            <span class="material-symbols-outlined">
                                                edit
                                            </span>

                                            <span class="sr-only">
                                                Editar usuario
                                            </span>
                                        </button>


                                        <?php if ($activo): ?>

                                        <button
                                            type="button"
                                            class="admin-usuarios__accion admin-usuarios__accion--danger"
                                            data-accion="desactivar"
                                            data-id="<?= $id; ?>"
                                            title="Desactivar usuario"
                                        >
                                            <span class="material-symbols-outlined">
                                                person_off
                                            </span>

                                            <span class="sr-only">
                                                Desactivar usuario
                                            </span>
                                        </button>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="admin-usuarios__accion admin-usuarios__accion--success"
                                            data-accion="activar"
                                            data-id="<?= $id; ?>"
                                            title="Activar usuario"
                                        >
                                            <span class="material-symbols-outlined">
                                                person_check
                                            </span>

                                            <span class="sr-only">
                                                Activar usuario
                                            </span>
                                        </button>

                                    <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- SIN RESULTADOS DE BÚSQUEDA -->

                <div
                    id="sin-resultados"
                    class="admin-usuarios__sin-resultados"
                    hidden
                >

                    <span class="material-symbols-outlined">
                        search_off
                    </span>

                    <strong>
                        No se encontraron usuarios
                    </strong>

                    <span>
                        Probá con otro nombre, DNI o email.
                    </span>

                </div>

            </section>

        </main>


        <dialog
            id="modal-nuevo-usuario"
            class="admin-usuarios__modal"
        >

            <div class="admin-usuarios__modal-content">

                <div class="admin-usuarios__modal-header">

                    <div>

                        <p class="admin-usuarios__modal-eyebrow">
                            Administración
                        </p>

                        <h2 class="admin-usuarios__modal-title">
                            Nuevo usuario
                        </h2>

                    </div>

                    <button
                        type="button"
                        class="admin-usuarios__modal-close"
                        data-cerrar-modal="modal-nuevo-usuario"
                        aria-label="Cerrar"
                    >
                        <span class="material-symbols-outlined">
                            close
                        </span>
                    </button>

                </div>


                <form
                    method="POST"
                    action="<?= $this->e(
                        $this->getRoute('admin_usuarios')
                    ); ?>"
                    class="admin-usuarios__form"
                >

                    <?= $this->getCsrfInput(); ?>


                    <div class="admin-usuarios__form-grid">

                        <div class="admin-usuarios__campo">

                            <label for="nuevo-nombre">
                                Nombre
                            </label>

                            <input
                                type="text"
                                id="nuevo-nombre"
                                name="nombre"
                                maxlength="100"
                                required
                            >

                        </div>


                        <div class="admin-usuarios__campo">

                            <label for="nuevo-apellido">
                                Apellido
                            </label>

                            <input
                                type="text"
                                id="nuevo-apellido"
                                name="apellido"
                                maxlength="100"
                                required
                            >

                        </div>


                        <div class="admin-usuarios__campo">

                            <label for="nuevo-dni">
                                DNI
                            </label>

                            <input
                                type="text"
                                id="nuevo-dni"
                                name="dni"
                                maxlength="8"
                                inputmode="numeric"
                                required
                            >

                        </div>


                        <div class="admin-usuarios__campo">

                            <label for="nuevo-email">
                                Email
                            </label>

                            <input
                                type="email"
                                id="nuevo-email"
                                name="email"
                                maxlength="150"
                                required
                            >

                        </div>


                        <div class="admin-usuarios__campo">

                            <label for="nuevo-telefono">
                                Teléfono
                            </label>

                            <input
                                type="text"
                                id="nuevo-telefono"
                                name="telefono"
                                maxlength="30"
                            >

                        </div>


                        <div class="admin-usuarios__campo">

                            <label for="nuevo-password">
                                Contraseña
                            </label>

                            <input
                                type="password"
                                id="nuevo-password"
                                name="password"
                                minlength="8"
                                required
                            >

                        </div>


                        <div
                            class="admin-usuarios__campo admin-usuarios__campo--full"
                        >

                            <label for="nuevo-domicilio">
                                Domicilio
                            </label>

                            <input
                                type="text"
                                id="nuevo-domicilio"
                                name="domicilio"
                                maxlength="255"
                            >

                        </div>

                    </div>


                    <div class="admin-usuarios__form-actions">

                        <button
                            type="button"
                            class="app-vista-button app-vista-button--secondary"
                            data-cerrar-modal="modal-nuevo-usuario"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="app-vista-button app-vista-button--primary"
                        >
                            <span class="material-symbols-outlined">
                                person_add
                            </span>

                            Crear usuario
                        </button>

                    </div>

                </form>

            </div>

        </dialog>


        <!-- ==========================================
            MODAL: EDITAR USUARIO
            ========================================== -->

        <dialog
            id="modal-editar-usuario"
            class="admin-usuarios__modal"
        >

            <div class="admin-usuarios__modal-content">

                <div class="admin-usuarios__modal-header">

                    <div>

                        <p class="admin-usuarios__modal-eyebrow">
                            Administración
                        </p>

                        <h2 class="admin-usuarios__modal-title">
                            Editar usuario
                        </h2>

                    </div>

                    <button
                        type="button"
                        class="admin-usuarios__modal-close"
                        data-cerrar-modal="modal-editar-usuario"
                        aria-label="Cerrar"
                    >
                        <span class="material-symbols-outlined">
                            close
                        </span>
                    </button>

                </div>


                <form
                    method="POST"
                    id="form-editar-usuario"
                    class="admin-usuarios__form"
                >

                    <?= $this->getCsrfInput(); ?>


                    <!-- ==========================================
                        USUARIO
                        ========================================== -->

                    <div class="admin-usuarios__usuario-editando">

                        <div class="admin-usuarios__avatar">

                            <span class="material-symbols-outlined">
                                person
                            </span>

                        </div>

                        <div>

                            <strong id="editar-nombre-visible">
                                Usuario
                            </strong>

                            <span id="editar-dni-visible">
                                DNI
                            </span>

                        </div>

                    </div>


                    <input
                        type="hidden"
                        name="id"
                        id="editar-id"
                    >


                    <!-- ==========================================
                        DATOS PERSONALES
                        ========================================== -->

                    <div class="admin-usuarios__seccion">

                        <div class="admin-usuarios__seccion-header">

                            <span class="material-symbols-outlined">
                                person
                            </span>

                            <div>

                                <h3>
                                    Datos personales
                                </h3>

                                <p>
                                    Información básica del usuario.
                                </p>

                            </div>

                        </div>


                        <div class="admin-usuarios__form-grid">

                            <div class="admin-usuarios__campo">

                                <label for="editar-nombre">
                                    Nombre
                                </label>

                                <input
                                    type="text"
                                    id="editar-nombre"
                                    name="nombre"
                                    maxlength="100"
                                    required
                                >

                            </div>


                            <div class="admin-usuarios__campo">

                                <label for="editar-apellido">
                                    Apellido
                                </label>

                                <input
                                    type="text"
                                    id="editar-apellido"
                                    name="apellido"
                                    maxlength="100"
                                    required
                                >

                            </div>


                            <div class="admin-usuarios__campo">

                                <label for="editar-email">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    id="editar-email"
                                    name="email"
                                    maxlength="150"
                                    required
                                >

                            </div>


                            <div class="admin-usuarios__campo">

                                <label for="editar-telefono">
                                    Teléfono
                                </label>

                                <input
                                    type="text"
                                    id="editar-telefono"
                                    name="telefono"
                                    maxlength="30"
                                >

                            </div>


                            <div
                                class="admin-usuarios__campo admin-usuarios__campo--full"
                            >

                                <label for="editar-domicilio">
                                    Domicilio
                                </label>

                                <input
                                    type="text"
                                    id="editar-domicilio"
                                    name="domicilio"
                                    maxlength="255"
                                >

                            </div>


                            <div class="admin-usuarios__campo">

                                <label for="editar-rol">
                                    Rol
                                </label>

                                <select
                                    id="editar-rol"
                                    name="rol"
                                    required
                                >

                                    <option value="1">
                                        Usuario
                                    </option>

                                    <option value="3">
                                        Inspector
                                    </option>

                                    <option value="2">
                                        Administrador
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <!-- ==========================================
                        SEGURIDAD
                        ========================================== -->

                    <div class="admin-usuarios__seccion">

                        <div class="admin-usuarios__seccion-header">

                            <span class="material-symbols-outlined">
                                lock
                            </span>

                            <div>

                                <h3>
                                    Seguridad
                                </h3>

                                <p>
                                    Dejá estos campos vacíos si no querés cambiar
                                    la contraseña.
                                </p>

                            </div>

                        </div>


                        <div class="admin-usuarios__form-grid">

                            <div class="admin-usuarios__campo">

                                <label for="editar-password">
                                    Nueva contraseña
                                </label>

                                <input
                                    type="password"
                                    id="editar-password"
                                    name="password"
                                    minlength="8"
                                    maxlength="255"
                                    autocomplete="new-password"
                                >

                                <small>
                                    Mínimo 8 caracteres.
                                </small>

                            </div>


                            <div class="admin-usuarios__campo">

                                <label for="editar-password-confirmacion">
                                    Confirmar contraseña
                                </label>

                                <input
                                    type="password"
                                    id="editar-password-confirmacion"
                                    name="password_confirmacion"
                                    minlength="8"
                                    maxlength="255"
                                    autocomplete="new-password"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- ==========================================
                        ACCIONES
                        ========================================== -->

                    <div class="admin-usuarios__form-actions">

                        <button
                            type="button"
                            class="app-vista-button app-vista-button--secondary"
                            data-cerrar-modal="modal-editar-usuario"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="app-vista-button app-vista-button--primary"
                        >

                            <span class="material-symbols-outlined">
                                save
                            </span>

                            Guardar cambios

                        </button>

                    </div>

                </form>

            </div>

        </dialog>


        <!-- ==========================================
             MODAL: DESACTIVAR USUARIO
             ========================================== -->

        <dialog
            id="modal-desactivar-usuario"
            class="admin-usuarios__modal admin-usuarios__modal--small"
        >

            <div class="admin-usuarios__modal-content">

                <div class="admin-usuarios__confirmacion">

                    <div class="admin-usuarios__confirmacion-icon">

                        <span class="material-symbols-outlined">
                            person_off
                        </span>

                    </div>


                    <h2>
                        Desactivar usuario
                    </h2>


                    <p>
                        ¿Estás seguro de que querés desactivar
                        este usuario?
                    </p>


                    <strong
                        id="desactivar-nombre-visible"
                    >
                        Usuario
                    </strong>


                    <p class="admin-usuarios__confirmacion-ayuda">
                        El usuario no podrá utilizar el sistema
                        mientras permanezca inactivo.
                    </p>


                    <form
                        method="POST"
                        id="form-desactivar-usuario"
                        class="admin-usuarios__confirmacion-actions"
                    >

                        <?= $this->getCsrfInput(); ?>


                        <button
                            type="button"
                            class="app-vista-button app-vista-button--secondary"
                            data-cerrar-modal="modal-desactivar-usuario"
                        >
                            Cancelar
                        </button>


                        <button
                            type="submit"
                            class="app-vista-button app-vista-button--danger"
                        >
                            <span class="material-symbols-outlined">
                                person_off
                            </span>

                            Desactivar
                        </button>

                    </form>

                </div>

            </div>

        </dialog>
        <!-- ==========================================
            MODAL: ACTIVAR USUARIO
            ========================================== -->

        <dialog
            id="modal-activar-usuario"
            class="admin-usuarios__modal admin-usuarios__modal--small"
        >

            <div class="admin-usuarios__modal-content">

                <div class="admin-usuarios__confirmacion">

                    <div class="admin-usuarios__confirmacion-icon admin-usuarios__confirmacion-icon--success">

                        <span class="material-symbols-outlined">
                            person_check
                        </span>

                    </div>


                    <h2>
                        Activar usuario
                    </h2>


                    <p>
                        ¿Estás seguro de que querés activar
                        este usuario?
                    </p>


                    <strong
                        id="activar-nombre-visible"
                    >
                        Usuario
                    </strong>


                    <p class="admin-usuarios__confirmacion-ayuda">
                        El usuario podrá volver a utilizar el sistema
                        mientras permanezca activo.
                    </p>


                    <form
                        method="POST"
                        id="form-activar-usuario"
                        class="admin-usuarios__confirmacion-actions"
                    >

                        <?= $this->getCsrfInput(); ?>


                        <button
                            type="button"
                            class="app-vista-button app-vista-button--secondary"
                            data-cerrar-modal="modal-activar-usuario"
                        >
                            Cancelar
                        </button>


                        <button
                            type="submit"
                            class="app-vista-button app-vista-button--success"
                        >
                            <span class="material-symbols-outlined">
                                person_check
                            </span>

                            Activar
                        </button>

                    </form>

                </div>

            </div>

        </dialog>

        <!-- ==========================================
             DATOS PARA JAVASCRIPT
             ========================================== -->

        <script>
            window.adminUsuarios = <?= json_encode(
                array_map(
                    static function ($usuario): array {
                        if (
                            is_object($usuario)
                            && method_exists($usuario, 'toArray')
                        ) {
                            $usuario = $usuario->toArray();
                        }

                        return [
                            'id' => (int)(
                                $usuario['id']
                                ?? $usuario['id_usuario']
                                ?? 0
                            ),
                            'nombre' => (string)(
                                $usuario['nombre']
                                ?? ''
                            ),
                            'apellido' => (string)(
                                $usuario['apellido']
                                ?? ''
                            ),
                            'dni' => (string)(
                                $usuario['dni']
                                ?? ''
                            ),
                            'email' => (string)(
                                $usuario['email']
                                ?? ''
                            ),
                            'telefono' => (string)(
                                $usuario['telefono']
                                ?? ''
                            ),
                            'domicilio' => (string)(
                                $usuario['domicilio']
                                ?? ''
                            ),
                            'rol' => (string)(
                                $usuario['rol']
                                ?? ''
                            ),
                            'activo' => (bool)(
                                $usuario['activo']
                                ?? true
                            )
                        ];
                    },
                    $usuarios
                ),
                JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
            ); ?>;
        </script>


        <script src="<?= $this->baseURL ?>js/admin-usuarios.js"></script>
        
        <?php $this->getFooter(); 
        
    }
}