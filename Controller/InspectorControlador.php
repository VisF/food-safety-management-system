<?php
declare(strict_types=1);

class InspectorControlador
{
    private const LOG_FILE =
        __DIR__ . '/../logs/inspector_controller.log';

    private UsuarioService $usuarioService;
    private CarnetService $carnetService;
    private InscripcionService $inscripcionService;

    public function __construct()
    {
        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->usuarioService = new UsuarioService();
        $this->carnetService = new CarnetService();
        $this->inscripcionService = new InscripcionService();
    }

    /**
     * Registrar eventos.
     */
    private function log(
        string $evento,
        string $nivel = 'INFO',
        array $contexto = []
    ): void
    {
        $mensaje =
            sprintf(
                "[%s] [%s] %s | %s\n",
                date('Y-m-d H:i:s'),
                $nivel,
                $evento,
                json_encode(
                    $contexto,
                    JSON_UNESCAPED_UNICODE
                )
            );

        error_log(
            $mensaje,
            3,
            self::LOG_FILE
        );
    }

    // =====================================================
    // BÚSQUEDAS
    // =====================================================

    /**
     * Buscar usuario por DNI.
     */
    public function buscarPorDNI(
        string $dni
    ): array
    {
        try {

            $dni = preg_replace(
                '/[^0-9]/',
                '',
                $dni
            );

            if (
                strlen($dni) < 7
                ||
                strlen($dni) > 8
            ) {
                return [
                    'success' => false,
                    'usuario' => null,
                    'message' =>
                        'Formato de DNI inválido.'
                ];
            }

            $usuario =
                $this->usuarioService
                    ->obtenerDatosPublicos($dni);

            if ($usuario === null) {

                $this->log(
                    'DNI no encontrado',
                    'INFO',
                    ['dni' => $dni]
                );

                return [
                    'success' => true,
                    'usuario' => null,
                    'message' =>
                        'Usuario no encontrado'
                ];
            }

            $usuario['carnet'] =
                $this->carnetService
                    ->obtenerEstadoPorDni($dni);

            $this->log(
                'Usuario encontrado',
                'INFO',
                ['dni' => $dni]
            );

            return [
                'success' => true,
                'usuario' => $usuario,
                'message' =>
                    'Usuario encontrado'
            ];

        } catch (\Throwable $e) {

            $this->log(
                'Error buscarPorDNI',
                'ERROR',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'usuario' => null,
                'message' =>
                    $e->getMessage()
            ];
        }
    }
        /**
     * Obtiene el estado del carnet de un usuario.
     */
    public function obtenerEstadoCarnet(
        string $dni
    ): ?array
    {
        try {

            $dni = preg_replace(
                '/[^0-9]/',
                '',
                $dni
            );

            if (
                strlen($dni) < 7
                ||
                strlen($dni) > 8
            ) {
                $this->log(
                    'DNI inválido',
                    'WARNING',
                    ['dni' => $dni]
                );

                return null;
            }

            return $this->carnetService
                ->obtenerEstadoPorDni(
                    $dni
                );

        } catch (\Throwable $e) {

            $this->log(
                'Error obtenerEstadoCarnet',
                'ERROR',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * Verifica la vigencia de un carnet.
     */
    public function verificarVigencia(
        string $dni
    ): array
    {
        try {

            $dni = preg_replace(
                '/[^0-9]/',
                '',
                $dni
            );

            return $this->carnetService
                ->verificarVigenciaPorDni(
                    $dni
                );

        } catch (\Throwable $e) {

            $this->log(
                'Error verificarVigencia',
                'ERROR',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'vigente' => false,
                'mensaje' =>
                    $e->getMessage(),
                'carnet' => null
            ];
        }
    }

    /**
     * Obtiene la ubicación del PDF del carnet.
     */
    public function obtenerCarnetPDF(
        string $dni
    ): array
    {
        try {

            $dni = preg_replace(
                '/[^0-9]/',
                '',
                $dni
            );

            if (
                strlen($dni) < 7
                ||
                strlen($dni) > 8
            ) {
                return [
                    'success' => false,
                    'pdf_url' => null,
                    'mensaje' => 'DNI inválido',
                    'archivo' => null
                ];
            }

            $ruta =
                $this->carnetService
                    ->obtenerPdfPorDni(
                        $dni
                    );

            if (
                $ruta === null
                ||
                !file_exists($ruta)
            ) {
                return [
                    'success' => false,
                    'pdf_url' => null,
                    'mensaje' =>
                        'Carnet no disponible',
                    'archivo' => null
                ];
            }

            return [
                'success' => true,
                'pdf_url' =>
                    ltrim($ruta, '/'),
                'mensaje' =>
                    'PDF disponible',
                'archivo' =>
                    basename($ruta)
            ];

        } catch (\Throwable $e) {

            $this->log(
                'Error obtenerCarnetPDF',
                'ERROR',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'pdf_url' => null,
                'mensaje' =>
                    $e->getMessage(),
                'archivo' => null
            ];
        }
    }
        /**
     * Buscar usuarios por apellido.
     */
    public function buscarPorApellido(
        string $apellido
    ): array
    {
        try {

            if (trim($apellido) === '') {
                return [
                    'success' => false,
                    'usuarios' => [],
                    'total' => 0
                ];
            }

            $usuarios =
                $this->usuarioService
                    ->buscarPorApellido(
                        $apellido
                    );

            foreach ($usuarios as &$usuario) {

                $usuario['carnet'] =
                    $this->carnetService
                        ->obtenerEstadoPorDni(
                            $usuario['dni']
                        );
            }

            $this->log(
                'Búsqueda por apellido',
                'INFO',
                [
                    'apellido' => $apellido,
                    'resultados' => count($usuarios)
                ]
            );

            return [
                'success' => true,
                'usuarios' => $usuarios,
                'total' => count($usuarios)
            ];

        } catch (\Throwable $e) {

            $this->log(
                'Error buscarPorApellido',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'usuarios' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Obtiene únicamente la información pública de un usuario.
     */
    public function obtenerDatosPublicos(
        string $dni
    ): array
    {
        try {

            $dni = preg_replace(
                '/[^0-9]/',
                '',
                $dni
            );

            if (
                strlen($dni) < 7
                ||
                strlen($dni) > 8
            ) {
                return [
                    'success' => false,
                    'datos' => null
                ];
            }

            $datos =
                $this->usuarioService
                    ->obtenerDatosPublicos(
                        $dni
                    );

            if ($datos === null) {

                return [
                    'success' => false,
                    'datos' => null
                ];
            }

            $carnet =
                $this->carnetService
                    ->obtenerEstadoPorDni(
                        $dni
                    );

            return [
                'success' => true,
                'datos' => [
                    'nombre' => $datos['nombre'],
                    'apellido' => $datos['apellido'],
                    'carnet_vigente' =>
                        $carnet['vigente'] ?? false,
                    'numero_carnet' =>
                        $carnet['numero_carnet'] ?? null,
                    'fecha_vencimiento' =>
                        $carnet['fecha_vencimiento'] ?? null
                ]
            ];

        } catch (\Throwable $e) {

            $this->log(
                'Error obtenerDatosPublicos',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'datos' => null
            ];
        }
    }
        // =====================================================
    // GESTIÓN DE INSPECCIONES
    // =====================================================

    public function registrarDeteccion(
        string $dni,
        array $datos
    ): array
    {
        try {

            $this->log(
                'Detección registrada',
                'INFO',
                [
                    'dni' => $dni
                ]
            );

            // Pendiente cuando exista InspeccionService.

            return [
                'success' => true,
                'message' => 'Inspección registrada correctamente',
                'id_inspeccion' => null
            ];

        } catch (\Throwable $e) {

            $this->log(
                'Error registrarDeteccion',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'id_inspeccion' => null
            ];
        }
    }

    public function obtenerInspeccionesRecientes(): array
    {
        return [
            'success' => true,
            'inspecciones' => [],
            'total' => 0
        ];
    }

    public function listarCarnetesVencidos(): array
    {
        try {

            $carnets =
                $this->carnetService
                    ->obtenerCarnetsVencidos();

            return [
                'success' => true,
                'carnets' => $carnets,
                'total' => count($carnets)
            ];

        } catch (\Throwable $e) {

            $this->log(
                'Error listarCarnetesVencidos',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'carnets' => [],
                'total' => 0
            ];
        }
    }

    public function renovarCarnet(
        int $idCarnet
    ): array
    {
        try {

            $resultado =
                $this->carnetService
                    ->renovarCarnet(
                        $idCarnet
                    );

            return [
                'success' => true,
                'message' => 'Proceso iniciado correctamente.',
                'carnet' => $resultado
            ];

        } catch (\Throwable $e) {

            $this->log(
                'Error renovarCarnet',
                'ERROR',
                [
                    'id' => $idCarnet,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'carnet' => []
            ];
        }
    }

    public function registrarAlerta(
        string $dni,
        string $motivo
    ): array
    {
        try {

            $this->log(
                'Alerta registrada',
                'WARNING',
                [
                    'dni' => $dni,
                    'motivo' => $motivo
                ]
            );

            // Pendiente cuando exista AlertaService.

            return [
                'success' => true,
                'message' => 'Alerta registrada.',
                'id_alerta' => null
            ];

        } catch (\Throwable $e) {

            $this->log(
                'Error registrarAlerta',
                'ERROR',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'id_alerta' => null
            ];
        }
    }

    public function obtenerHistorialBusquedas(): array
    {
        return [
            'success' => true,
            'historial' => [],
            'total' => 0
        ];
    }
}