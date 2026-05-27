<?php
declare(strict_types=1);

class CrearExamenVista
{
    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function getRoute(string $route): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if (preg_match('#/vistas$#', $basePath) === 1) {
            $basePath = (string) preg_replace('#/vistas$#', '', $basePath);
        }

        return $basePath . '/Router.php?r=' . rawurlencode($route);
    }

    private function getIncomingData(): array
    {
        if (!isset($_GET['data'])) {
            return [];
        }

        $decodedData = json_decode((string) $_GET['data'], true);
        return is_array($decodedData) ? $decodedData : [];
    }

    public function mostrar(): void
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
        ], $this->getIncomingData());

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
                    <form class="crear-examen-vista__form" action="<?php echo $this->getRoute('crear_examen_guardar'); ?>" method="post">
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
        <script>
        (function () {
            const displayInput = document.getElementById('fecha_display');
            const hiddenInput = document.getElementById('fecha');
            const form = displayInput ? displayInput.form : null;

            const parseDisplayDate = (value) => {
                const match = /^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/.exec((value || '').trim());
                if (!match) {
                    return '';
                }

                return `${match[3]}-${match[2]}-${match[1]}`;
            };

            const syncHiddenDate = () => {
                if (!displayInput || !hiddenInput) {
                    return;
                }

                hiddenInput.value = parseDisplayDate(displayInput.value);
            };

            if (displayInput && hiddenInput) {
                syncHiddenDate();
                displayInput.addEventListener('input', syncHiddenDate);
                displayInput.addEventListener('blur', syncHiddenDate);

                if (form) {
                    form.addEventListener('submit', function (event) {
                        syncHiddenDate();
                        if (!hiddenInput.value) {
                            event.preventDefault();
                            displayInput.setCustomValidity('Usá el formato dd/mm/aaaa.');
                            displayInput.reportValidity();
                        } else {
                            displayInput.setCustomValidity('');
                        }
                    });
                }
            }
        })();
        </script>
        <?php
        include __DIR__ . '/footer.php';
    }
}

(new CrearExamenVista())->mostrar();
