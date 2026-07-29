<?php

require_once __DIR__ . '/../Repositories/CarnetRepository.php';
require_once __DIR__ . '/../Repositories/DocumentoRepository.php';

class ConsultaPublicaService
{
    private CarnetRepository $carnetRepository;
    private DocumentoRepository $documentoRepository;

    public function __construct()
    {
        $this->carnetRepository = new CarnetRepository();
        $this->documentoRepository = new DocumentoRepository();
    }

    public function consultarPorDni(string $dni): array
    {
        $data = [
            'page_title' => 'Consulta Pública de Carnets',

            'formulario' => [
                'dni' => $dni
            ],

            'resultado' => [
                'encontrado' => false
            ]
        ];

        $dni = trim($dni);

        if ($dni === '') {
            return $data;
        }

        if (!preg_match('/^\d{7,8}$/', $dni)) {
            return $data;
        }

        $carnet = $this->carnetRepository->obtenerCarnetPublicoPorDni($dni);

        if (!$carnet) {
            return $data;
        }

        $foto = $this->documentoRepository->obtenerFotoCarnet($carnet['usuario_id']);

        $data['resultado'] = [
                        'encontrado'         => true,
                        'id_carnet'          => $carnet['id_carnet'],
                        'nombre'             => $carnet['nombre'],
                        'apellido'           => $carnet['apellido'],
                        'dni'                => $carnet['dni'],
                        'numero_carnet'      => $carnet['numero_carnet'],
                        'fecha_emision'      => $carnet['fecha_emision'],
                        'fecha_vencimiento'  => $carnet['fecha_vencimiento'],
                        'vigente'            => $carnet['vigente'],
                        'ruta_pdf'           => $carnet['ruta_pdf'],
                        'foto_carnet'        => $foto['ruta_archivo'] ?? null
                    ];

        return $data;
    }

    public function descargarFotoPorCarnet(int $idCarnet): ?array
    {
        if ($idCarnet <= 0) {
            return null;
        }

        $carnet = $this->carnetRepository->obtenerPorIdConsultaPublica($idCarnet);

        if (!$carnet) {
            return null;
        }

        return $this->documentoRepository->obtenerFotoCarnet($carnet['usuario_id']);
    }

    public function descargarCarnet(int $idCarnet): ?array
    {
        if ($idCarnet <= 0) {
            return null;
        }

        $carnet = $this->carnetRepository->obtenerPorIdConsultaPublica($idCarnet);

        if (!$carnet) {
            return null;
        }

        return [
            'ruta_pdf' => $carnet['ruta_pdf'],
            'nombre_archivo' => 'carnet_' . $carnet['numero_carnet'] . '.pdf'
        ];
    }
}