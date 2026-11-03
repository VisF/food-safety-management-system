<?php

declare(strict_types=1);

/**
 * EmailService
 *
 * Transporte de correo para desarrollo local.
 *
 * IMPORTANTE:
 * Este servicio NO envía correos reales.
 * Guarda los mensajes generados para poder inspeccionarlos
 * durante las pruebas del sistema.
 */
class EmailService
{
    private string $directorioCorreos;

    public function __construct()
    {
        $this->directorioCorreos =
            __DIR__ . '/../storage/mail-testing';
    }

    /**
     * Genera un correo de recuperación y lo guarda localmente.
     *
     * @return bool Indica si el mensaje se guardó correctamente.
     */
    public function enviarRecuperacion(
        string $destinatario,
        string $urlRecuperacion,
        string $nombre = ''
    ): bool {
        $destinatario = trim($destinatario);
        $nombre = trim($nombre);

        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'La dirección de correo no es válida.'
            );
        }

        if (
            !filter_var($urlRecuperacion, FILTER_VALIDATE_URL)
            || !in_array(
                strtolower((string) parse_url($urlRecuperacion, PHP_URL_SCHEME)),
                ['http', 'https'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'La URL de recuperación no es válida.'
            );
        }

        $this->crearDirectorioSiNoExiste();

        $saludo = $nombre !== ''
            ? 'Hola ' . $nombre . ','
            : 'Hola,';

        $asunto = 'Recuperación de contraseña';

        $cuerpoTexto = implode("\n", [
            $saludo,
            '',
            'Recibimos una solicitud para restablecer tu contraseña.',
            '',
            'Abrí el siguiente enlace para continuar:',
            $urlRecuperacion,
            '',
            'El enlace vence en una hora y solo puede utilizarse una vez.',
            '',
            'Si no solicitaste este cambio, podés ignorar este mensaje.',
            '',
            'Sistema de Manipulación de Alimentos',
        ]);

        $cuerpoHtml = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>' . $this->escaparHtml($asunto) . '</title>
            </head>
            <body style="font-family: Arial, sans-serif; color: #243447; line-height: 1.6;">
                <main style="max-width: 600px; margin: 0 auto; padding: 24px;">
                    <h1 style="color: #0a4e93; font-size: 24px;">
                        Recuperación de contraseña
                    </h1>

                    <p>' . $this->escaparHtml($saludo) . '</p>

                    <p>
                        Recibimos una solicitud para restablecer tu contraseña.
                    </p>

                    <p>
                        Utilizá el siguiente enlace para continuar:
                    </p>

                    <p>
                        <a
                            href="' . $this->escaparHtml($urlRecuperacion) . '"
                            style="
                                display: inline-block;
                                padding: 12px 20px;
                                background-color: #0a4e93;
                                color: #ffffff;
                                text-decoration: none;
                                border-radius: 8px;
                            "
                        >
                            Restablecer contraseña
                        </a>
                    </p>

                    <p>
                        El enlace vence en una hora y solo puede utilizarse una vez.
                    </p>

                    <p>
                        Si no solicitaste este cambio, podés ignorar este mensaje.
                    </p>

                    <hr>

                    <small>Sistema de Manipulación de Alimentos</small>
                </main>
            </body>
            </html>
        ';

        $contenido = implode("\r\n", [
            'To: ' . $destinatario,
            'Subject: ' . $this->codificarAsunto($asunto),
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="RECOVERY_EMAIL_BOUNDARY"',
            '',
            '--RECOVERY_EMAIL_BOUNDARY',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $cuerpoTexto,
            '',
            '--RECOVERY_EMAIL_BOUNDARY',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $cuerpoHtml,
            '',
            '--RECOVERY_EMAIL_BOUNDARY--',
            '',
        ]);

        $nombreArchivo = sprintf(
            'recuperacion_%s_%s.eml',
            date('Ymd_His'),
            bin2hex(random_bytes(8))
        );

        $rutaArchivo = $this->directorioCorreos
            . DIRECTORY_SEPARATOR
            . $nombreArchivo;

        $resultado = file_put_contents(
            $rutaArchivo,
            $contenido,
            LOCK_EX
        );

        if ($resultado === false) {
            throw new RuntimeException(
                'No se pudo guardar el correo de prueba.'
            );
        }

        return true;
    }

    /**
     * Crea el directorio local si todavía no existe.
     */
    private function crearDirectorioSiNoExiste(): void
    {
        if (is_dir($this->directorioCorreos)) {
            return;
        }

        if (
            !mkdir($this->directorioCorreos, 0700, true)
            && !is_dir($this->directorioCorreos)
        ) {
            throw new RuntimeException(
                'No se pudo crear el directorio de correos de prueba.'
            );
        }
    }

    /**
     * Escapa texto para incluirlo en HTML.
     */
    private function escaparHtml(string $texto): string
    {
        return htmlspecialchars(
            $texto,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    /**
     * Codifica el asunto para UTF-8.
     */
    private function codificarAsunto(string $asunto): string
    {
        return '=?UTF-8?B?'
            . base64_encode($asunto)
            . '?=';
    }
}
