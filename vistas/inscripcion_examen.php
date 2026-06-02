<?php
/**
 * Vista: inscripcion_examen.php
 * Propósito: Mostrar requisitos y próximas fechas; permitir iniciar inscripción al examen.
 * Estructura de `exams[]`:
 *  - id:int, month:string, day:string, title:string, capacity:int, time:string, room:string
 * Recomendaciones técnicas:
 *  - Llamar a endpoints server-side para reservar cupo real (ACID-friendly) antes de confirmar inscripción.
 *  - No confiar en `capacity` renderizado en cliente; usar siempre verificación en el controlador.
 */
class InscripcionExamenVista
{
    private string $baseURL = '/ManipulacionDeAlimentosAPI/';

    private function getDefaultData(): array
    {
        return [
            'page_title' => 'Inscripción a examen - App Ciudadana',
            'status_label' => 'Estado actual del trámite',
            'status_title' => 'Documentación Aprobada',
            'status_icon' => 'check_circle',
            'status_ok' => 1,
            'requirements_title' => 'Requisitos cumplidos',
            'requirements' => [
                [
                    'label' => 'DNI cargado',
                    'icon' => 'check',
                    'state' => 1,
                ],
                [
                    'label' => 'Foto cargada',
                    'icon' => 'check',
                    'state' => 1,
                ],
            ],
            'exams_title' => 'Próximas fechas de examen',
            'exams' => [
                [
                    'id' => 101,
                    'month' => 'OCT',
                    'day' => '24',
                    'title' => 'CRESTA',
                    'capacity' => 1,
                    'capacity_label' => 'CUPOS DISPONIBLES',
                    'time' => '09:00 AM',
                    'room' => 'Aula 4 - Planta Alta',
                    'route' => 'inscripcion_examen',
                ],
                [
                    'id' => 102,
                    'month' => 'OCT',
                    'day' => '28',
                    'title' => 'Polideportivo Norte',
                    'capacity' => 1,
                    'capacity_label' => 'CUPOS DISPONIBLES',
                    'time' => '14:30 PM',
                    'room' => 'Salón de Usos Múltiples',
                    'route' => 'inscripcion_examen',
                ],
                [
                    'id' => 103,
                    'month' => 'NOV',
                    'day' => '02',
                    'title' => 'Delegación Municipal',
                    'capacity' => 0,
                    'capacity_label' => 'SIN CUPOS',
                    'time' => '10:00 AM',
                    'room' => 'Aula 1 - Planta Baja',
                    'route' => 'inscripcion_examen',
                ],
            ],
            'cta_text' => 'Inscribirse',
            'footer_note' => 'Recuerde presentarse 15 minutos antes con su DNI físico.',
            'footer_note_enabled' => 1,
        ];
    }

    private function getHeader(array $inscripcionData): void
    {
        ?>
        <!DOCTYPE html>
        <html class="light" lang="es">
         <head>
          <meta charset="utf-8"/>
          <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
          <title><?php echo $this->e($inscripcionData['page_title']); ?></title>
         </head>
         <body class="bg-background text-on-surface min-h-screen pb-24 tema-ciudadano">
        <?php $page_title = 'Inscripción a Examen'; include __DIR__ . '/header.php'; ?>
        <?php
    }

    private function getFooter(): void
    {
        include __DIR__ . '/footer.php';
        ?>
        </body>
        </html>
        <?php
    }

    private function getIncomingData(): array
    {
        if (!isset($_GET['data'])) {
            return [];
        }

        $decodedData = json_decode((string) $_GET['data'], true);
        return is_array($decodedData) ? $decodedData : [];
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function statusStyle(int $state): string
    {
        return $state === 1
            ? 'bg-green-100 text-green-700 p-2 rounded-full flex items-center justify-center'
            : 'bg-amber-100 text-amber-700 p-2 rounded-full flex items-center justify-center';
    }

    private function requirementStyle(int $state): string
    {
        return $state === 1
            ? 'bg-[#e8f5e9] text-[#2e7d32] text-[10px] px-2 py-1 rounded-full font-bold border border-[#c8e6c9]'
            : 'bg-surface-container-low text-on-surface-variant text-[10px] px-2 py-1 rounded-full font-bold border border-outline-variant';
    }

    private function requirementIconStyle(int $state): string
    {
        return $state === 1
            ? 'material-symbols-outlined icono-relleno text-green-600 text-[18px]'
            : 'material-symbols-outlined icono-relleno text-outline text-[18px]';
    }

    private function examCardStatusStyle(int $capacity): string
    {
        return $capacity === 1
            ? 'bg-green-50 text-green-700 text-[10px] px-2 py-1 rounded-full font-bold border border-green-200'
            : 'bg-red-50 text-red-700 text-[10px] px-2 py-1 rounded-full font-bold border border-red-200';
    }

    private function examCardStatusText(string $label, int $capacity): string
    {
        return $capacity === 1 ? $label : 'SIN CUPOS';
    }

    private function examRoute(string $route): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if (preg_match('#/vistas$#', $basePath) === 1) {
            $basePath = (string) preg_replace('#/vistas$#', '', $basePath);
        }

        return $basePath . '/Router.php?r=' . rawurlencode($route);
    }

    public function mostrar(): void
    {
        $inscripcionData = array_replace_recursive($this->getDefaultData(), $this->getIncomingData());

        $this->getHeader($inscripcionData);
        ?>

                    <main class="contenido-principal contenido-principal--estrecho" style="display: grid; gap: 14px;">
                        <section class="app-vista-card" style="padding: 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <div>
                                <p style="margin: 0; color: #5b6b80; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <?php echo $this->e($inscripcionData['status_label']); ?>
                                </p>
                                <h2 style="margin: 4px 0 0; color: #0a4e93; font-size: 1.2rem; font-weight: 800; line-height: 1.25;">
                                    <?php echo $this->e($inscripcionData['status_title']); ?>
                                </h2>
                            </div>
                            <div class="app-vista-chip app-vista-chip--vigente" aria-hidden="true" style="padding: 10px; border-radius: 14px;">
                                <span class="material-symbols-outlined icono-relleno" data-icon="<?php echo $this->e($inscripcionData['status_icon']); ?>" style="font-size: 22px;">
                                    <?php echo $this->e($inscripcionData['status_icon']); ?>
                                </span>
                            </div>
                        </section>

                        <section class="app-vista-card app-vista-card--surface" style="padding: 16px;">
                            <h3 style="margin: 0 0 10px; color: #1f2f46; font-size: 1.02rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase;">
                                <?php echo $this->e($inscripcionData['requirements_title']); ?>
                            </h3>
                            <div class="chips">
                        <?php // Requisitos: iterar 'requirements' (array pequeño). Escapar texto y no confiar en flags del cliente.
                        foreach ($inscripcionData['requirements'] as $requirement): ?>
                                <span class="chip <?php echo ((int) $requirement['state'] === 1) ? 'success' : ''; ?>">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: inherit;">
                                        <?php echo $this->e($requirement['icon']); ?>
                                    </span>
                                    <?php echo $this->e($requirement['label']); ?>
                                </span>
                        <?php endforeach; ?>
                            </div>
                        </section>

                        <section style="display: grid; gap: 12px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h3 style="margin: 0; color: #1f2f46; font-size: 1.15rem; font-weight: 800;">
                                    <?php echo $this->e($inscripcionData['exams_title']); ?>
                                </h3>
                                <span class="material-symbols-outlined" data-icon="filter_list" style="color: #4c5f77;">filter_list</span>
                            </div>

                            <?php // Itera próximas fechas de examen. Verificar disponibilidad en servidor antes de confirmar.
                            foreach ($inscripcionData['exams'] as $exam): ?>
                            <article class="app-vista-card" style="padding: 0; overflow: hidden;">
                                <div style="display: grid; grid-template-columns: 82px 1fr; align-items: stretch;">
                                    <div style="background: linear-gradient(160deg, #1462b5, #0a4e93); color: #ffffff; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;">
                                        <span style="font-size: 0.72rem; font-weight: 700; opacity: 0.92;">
                                            <?php echo $this->e($exam['month']); ?>
                                        </span>
                                        <span style="font-size: 1.6rem; font-weight: 800; line-height: 1;">
                                            <?php echo $this->e($exam['day']); ?>
                                        </span>
                                    </div>

                                    <div style="padding: 14px; display: grid; gap: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">
                                            <h4 style="margin: 0; color: #1f2f46; font-size: 1.05rem; font-weight: 700; line-height: 1.25;">
                                                <?php echo $this->e($exam['title']); ?>
                                            </h4>
                                            <span class="app-vista-chip <?php echo ((int) $exam['capacity'] === 1) ? 'app-vista-chip--vigente' : ''; ?>" style="font-size: 0.75rem;">
                                                <?php if ((int) $exam['capacity'] === 1): ?>
                                                <span style="width: 7px; height: 7px; border-radius: 50%; background: #2d6e43;"></span>
                                                <?php endif; ?>
                                                <?php echo $this->e($this->examCardStatusText((string) $exam['capacity_label'], (int) $exam['capacity'])); ?>
                                            </span>
                                        </div>

                                        <p style="margin: 0; display: flex; align-items: center; gap: 6px; color: #475a72; font-size: 0.95rem;">
                                            <span class="material-symbols-outlined" data-icon="schedule" style="font-size: 18px;">schedule</span>
                                            <?php echo $this->e($exam['time']); ?>
                                        </p>
                                        <p style="margin: 0; display: flex; align-items: center; gap: 6px; color: #475a72; font-size: 0.95rem;">
                                            <span class="material-symbols-outlined" data-icon="meeting_room" style="font-size: 18px;">meeting_room</span>
                                            <?php echo $this->e($exam['room']); ?>
                                        </p>

                                        <form action="<?php echo $this->examRoute('inscripcion_examen_inscribir'); ?>" method="post" style="margin-top: 2px;">
                                            <input type="hidden" name="id_examen" value="<?php echo (int) $exam['id']; ?>"/>
                                            <input type="hidden" name="id_tipo_inscripcion" value="1"/>
                                            <button class="app-vista-button app-vista-button--primary" type="submit" style="min-height: 44px; border-radius: 14px; font-size: 0.9rem;">
                                                <?php echo $this->e($inscripcionData['cta_text']); ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </section>

                        <?php if ((int) $inscripcionData['footer_note_enabled'] === 1): ?>
                        <section class="app-vista-card app-vista-card--surface" style="padding: 14px 16px;">
                            <p style="margin: 0; color: #5b6b80; font-size: 0.86rem; line-height: 1.45; text-align: center;">
                                <?php echo $this->e($inscripcionData['footer_note']); ?>
                            </p>
                        </section>
                        <?php endif; ?>
                    </main>
          <!-- BottomNavBar -->

        <?php
        $this->getFooter();
    }
}

(new InscripcionExamenVista())->mostrar();
