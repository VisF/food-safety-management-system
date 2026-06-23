<?php
declare(strict_types=1);
/**
 * Vista: detalle_examen.php
 * Propósito: Mostrar información de un examen y opciones para inscribirse.
 * Estructura esperada para `exam`:
 *  - nombre:string, fecha:string, lugar:string, id:int
 * Flujo técnico:
 *  - El enlace de inscripción apunta a `/manipulacionDeAlimentos/confirmar_inscripcion_examen&data=`; el controlador debe validar cupos y permisos.
 * Seguridad:
 *  - No confiar en datos de disponibilidad enviados por el cliente; siempre validar en servidor.
 */
class DetalleExamenVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Detalle del examen',
            'exam' => []
        ];
        $data = $defaults;
        // Decodifica y fusiona `data` (JSON) pasada por GET; no usar para decisiones de negocio sin verificación.
        if (isset($_GET['data'])) {
            $incoming = json_decode($_GET['data'], true);
            if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
        }
        include __DIR__ . '/header.php';
        ?>
        <main class="contenido-principal contenido-principal--ancho">

            <div class="w-full max-w-4xl mx-auto space-y-6">

                <section class="space-y-1">
                    <p class="text-sm text-on-surface-variant">
                        Próximo examen
                    </p>

                    <h1 class="font-headline-lg text-headline-lg text-primary">
                        <?php echo htmlspecialchars($data['exam']['nombre'] ?? 'Examen'); ?>
                    </h1>
                </section>

                <section
                    class="app-vista-card overflow-hidden relative"
                    style="
                        background: linear-gradient(
                            135deg,
                            #005596 0%,
                            #3a5f94 100%
                        );
                        background-size: cover;
                        background-position: center;
                        min-height: 128px;
                    "
                >

                    <div
                        style="
                            position:relative;
                            z-index:1;
                            padding:1rem 1.25rem;
                            min-height:130px;
                            color:white;
                        "
                    >

                        <div
                            style="
                                display:grid;
                                grid-template-columns:1fr auto;
                                gap:20px;
                                align-items:center;
                            "
                        >

                            <div>

                                <div
                                    style="
                                        display:inline-block;
                                        background:rgba(255,255,255,.15);
                                        border-radius:999px;
                                        padding:.4rem .8rem;
                                        font-size:.75rem;
                                        font-weight:600;
                                        margin-bottom:.75rem;
                                    "
                                >
                                    <?php echo htmlspecialchars($data['exam']['estado'] ?? ''); ?>
                                </div>

                                <h2
                                    style="
                                        margin:0;
                                        font-size:2rem;
                                        font-weight:700;
                                        line-height:1;
                                    "
                                >
                                    <?php echo htmlspecialchars($data['exam']['nombre'] ?? ''); ?>
                                </h2>

                                <div
                                    style="
                                        margin-top:.5rem;
                                        font-size:1rem;
                                    "
                                >
                                    <?php echo htmlspecialchars($data['exam']['lugar'] ?? ''); ?>
                                </div>

                            </div>

                            <div style="text-align:right; min-width:120px;">

                                <div
                                    style="
                                        font-size:1.5rem;
                                        font-weight:700;
                                        line-height:1.2;
                                    "
                                >
                                    <?php echo htmlspecialchars($data['exam']['fecha'] ?? ''); ?>
                                </div>

                                <div
                                    style="
                                        margin-top:.75rem;
                                        font-size:1.1rem;
                                        font-weight:600;
                                    "
                                >
                                    <?php echo htmlspecialchars($data['exam']['hora'] ?? ''); ?> hs
                                </div>

                                <div
                                    style="
                                        margin-top:.25rem;
                                        font-size:.95rem;
                                    "
                                >
                                    Cupos: <?php echo htmlspecialchars((string)($data['exam']['cupos'] ?? '0')); ?>
                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <section class="app-vista-card">

                    <h3 class="font-semibold text-lg mb-4">
                        Información importante
                    </h3>

                    <ul class="space-y-2 text-on-surface-variant text-sm">
                        <li>• Presentarse 15 minutos antes del horario indicado.</li>
                        <li>• Llevar DNI físico.</li>
                        <li>• La inscripción será validada por el sistema.</li>
                        <li>• Los cupos son limitados.</li>
                    </ul>

                </section>

                <div class="space-y-3">

                    <a
                        href="/manipulacionDeAlimentos/confirmar_inscripcion_examen?id=<?= (int)$data['exam']['id'] ?>"
                        class="app-vista-button app-vista-button--primary w-full text-center"
                    >
                        Inscribirme al examen
                    </a>

                    <a
                        href="/manipulacionDeAlimentos/"
                        class="app-vista-button app-vista-button--secondary w-full text-center"
                    >
                        Volver
                    </a>

                </div>

            </div>

        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}


