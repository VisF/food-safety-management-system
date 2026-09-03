<?php
declare(strict_types=1);
/**
 * Vista: crear_examen.php
 * Propósito: Formulario administrativo para planificar una fecha de examen.
 * Campos esperados (ejemplo POST): fecha, hora, ubicacion, capacidad
 * Requisitos técnicos:
 *  - Validar fecha/hora en backend (timezone-aware) y comprobar colisiones/recursos.
 *  - Escapar cualquier texto de salida; el formulario no debe hacer decisiones de negocio.
 */

require_once __DIR__ . '/BaseVista.php';

class CrearExamenVista extends BaseVista
{ 
    



    public function mostrar(array $datos = []): void
    {
        $data = array_replace_recursive([
                'page_title' => 'Crear fecha de examen - App Ciudadana',
                'error' => '',
                'success' => false,
                'message' => '',
                'fecha_display' => '',
                'hora' => '',
                'ubicacion' => '',
                'aula' => '',
                'cupos' => '',
            ], $datos);

        $page_title = $data['page_title'];
        include __DIR__ . '/header.php';
        ?>
        <main class="contenido-principal contenido-principal--ancho crear-examen-vista">
            <div class="crear-examen-vista__shell">
                <section class="crear-examen-vista__intro">
                    <p class="crear-examen-vista__eyebrow">
                        Gestión de exámenes
                    </p>
                    <h2 class="crear-examen-vista__title">
                        Crear fecha de examen
                    </h2>
                    <p class="crear-examen-vista__subtitle">
                        Definí cuándo, dónde y en qué aula se va a tomar el examen.
                    </p>
                </section>

                <?php if (!empty($data['error'])): ?>
                <section class="app-vista-card app-vista-card--surface crear-examen-vista__notice crear-examen-vista__notice--error">
                    <p class="crear-examen-vista__notice-text">
                        <?php echo $this->e($data['error']); ?>
                    </p>
                </section>
                <?php endif; ?>

                <?php if (!empty($data['success'])): ?>
                <section class="app-vista-card app-vista-card--surface crear-examen-vista__notice crear-examen-vista__notice--success">
                    <p class="crear-examen-vista__notice-text">
                        <?php echo $this->e($data['message'] ?: 'Examen creado correctamente.'); ?>
                    </p>
                </section>
                <?php endif; ?>

                <section class="app-vista-card crear-examen-vista__card">
                    <form
                            class="crear-examen-vista__form"
                            action="<?php echo $this->getRoute('guardar_examen_nuevo'); ?>"
                            method="post"
                        >
                        <div class="crear-examen-vista__field">
                            <label class="crear-examen-vista__label" for="fecha_display">Fecha</label>
                            <input class="crear-examen-vista__input" id="fecha_display" name="fecha_display" type="text" value="<?php echo $this->e($data['fecha_display']); ?>" inputmode="numeric" autocomplete="off" placeholder="dd/mm/aaaa" pattern="\d{2}/\d{2}/\d{4}" required />
                            <input id="fecha" name="fecha" type="hidden" value="" />
                            <p class="crear-examen-vista__help-inline">Formato visible: dd/mm/aaaa</p>
                        </div>

                        <div class="crear-examen-vista__field">
                            <label class="crear-examen-vista__label" for="hora">Hora</label>
                            <input class="crear-examen-vista__input" id="hora" name="hora" type="time" value="<?php echo $this->e($data['hora']); ?>" required />
                        </div>

                        <div class="crear-examen-vista__field">
                            <label class="crear-examen-vista__label" for="cupos">Cupos</label>
                            <input class="crear-examen-vista__input" id="cupos" name="cupos" type="number" min="1" step="1" value="<?php echo $this->e($data['cupos']); ?>" required placeholder="Ej: 30" />
                        </div>

                        <div class="crear-examen-vista__field">
                            <label class="crear-examen-vista__label" for="ubicacion">Lugar</label>
                            <input class="crear-examen-vista__input" id="ubicacion" name="ubicacion" type="text" value="<?php echo $this->e($data['ubicacion']); ?>" required placeholder="Ej: Polideportivo Municipal" />
                        </div>

                        <div class="crear-examen-vista__field">
                            <label class="crear-examen-vista__label" for="aula">Aula</label>
                            <input class="crear-examen-vista__input" id="aula" name="aula" type="text" value="<?php echo $this->e($data['aula']); ?>" required placeholder="Ej: Aula 4 - Planta Alta" />
                        </div>

                        <div class="crear-examen-vista__actions">
                            <button class="app-vista-button app-vista-button--primary crear-examen-vista__primary" type="submit">
                                Guardar examen
                            </button>
                            <a class="app-vista-button app-vista-button--secondary crear-examen-vista__secondary" href="<?php echo $this->getRoute('panel_admin'); ?>" role="button">
                                Volver al panel
                            </a>
                        </div>
                    </form>
                </section>

                <section class="app-vista-card app-vista-card--surface crear-examen-vista__hint">
                    <p class="crear-examen-vista__hint-text">
                        La ubicación se usa para el lugar general del examen. El aula se guarda como dato independiente para poder mostrarla y administrarla mejor.
                    </p>
                </section>
            </div>
        </main>
       <script
            src="<?= $this->baseURL ?>js/crear_examen.js"
            defer
        ></script>
        <?php
        include __DIR__ . '/footer.php';
    }
}


