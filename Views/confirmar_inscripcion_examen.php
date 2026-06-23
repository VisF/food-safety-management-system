<?php
declare(strict_types=1);
/**
 * Vista: confirmar_inscripcion_examen.php
 * Propósito: Confirmación antes de completar la inscripción a un examen.
 * Entradas: GET 'data' (JSON) puede contener 'examName'.
 * Nota: la acción del formulario redirige a `/manipulacionDeAlimentos/inscripcion_exitosa`.
 */
class ConfirmarInscripcionExamenVista
{
    public static function mostrar(): void
    {
        $defaults = [
            'title' => 'Confirmar inscripción',
            'examName' => '',
            'examId' => 0
        ];
        $data = $defaults;
        // Decodifica `data` JSON pasado por GET para mostrar contextualización; el backend debe autorizar la acción.
        if (isset($_GET['data'])) {
            $incoming = json_decode($_GET['data'], true);
            if (is_array($incoming)) $data = array_replace_recursive($defaults, $incoming);
        }
        include __DIR__ . '/header.php';
        ?>
        <main class="contenido-principal contenido-principal--ancho">
            <div class="w-full max-w-2xl mx-auto">
                <section class="app-vista-card text-center">
                    <div
                        style="
                            width:72px;
                            height:72px;
                            margin:0 auto 1.5rem auto;
                            border-radius:50%;
                            background:linear-gradient(
                                135deg,
                                #005596 0%,
                                #3a5f94 100%
                            );
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            color:white;
                            font-size:2rem;
                            font-weight:bold;
                        "
                    >
                        !
                    </div>
                    <h1 class="font-headline-lg text-primary">
                        Confirmar inscripción
                    </h1>
                    <p
                        style="
                            margin-top:1rem;
                            margin-bottom:2rem;
                        "
                    >
                        Está a punto de inscribirse al examen
                        <strong>
                            <?php echo htmlspecialchars($data['examName'] ?: 'seleccionado'); ?>
                        </strong>
                    </p>
                    <div
                        style="
                            display:flex;
                            flex-direction:column;
                            gap:12px;
                        "
                    >
                        <form
                            action="/manipulacionDeAlimentos/inscripcion/confirmar"
                            method="post"
                        >
                        <input
                            type="hidden"
                            name="id_examen"
                            value="<?= (int)$data['examId'] ?>"
                        >
                            <button
                                type="submit"
                                class="app-vista-button app-vista-button--primary w-full"
                            >
                                Confirmar inscripción
                            </button>
                        </form>
                        <a
                            href="/manipulacionDeAlimentos/detalle_examen"
                            class="app-vista-button app-vista-button--secondary w-full text-center"
                        >
                            Volver
                        </a>
                    </div>
                </section>
            </div>
        </main>
        <?php
        include __DIR__ . '/footer.php';
    }
}


