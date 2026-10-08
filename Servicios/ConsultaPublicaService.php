<?php

require_once __DIR__ . '/../Repository/CarnetRepository.php';
require_once __DIR__ . '/../Repository/DocumentoRepository.php';

class ConsultaPublicaService
{
    private CarnetRepository $carnetRepository;
    private DocumentoRepository $documentoRepository;

    public function __construct()
    {
        $this->carnetRepository = new CarnetRepository();
        $this->documentoRepository = new DocumentoRepository();
    }




    /**
     * Consulta un carnet por CUIL/CUIT.
     *
     * @param string $cuilCuit
     * @return array
     */
    public function consultarPorCuilCuit(string $cuilCuit): array
    {
        $data = [
            'page_title' => 'Consulta Pública de Carnets',

            'formulario' => [
                'cuil_cuit' => $cuilCuit
            ],

            'resultado' => [
                'consultado' => false,
                'encontrado' => false
            ]
        ];

        $cuilCuit = trim($cuilCuit);

        if ($cuilCuit === '') {
            return $data;
        }

        if (!preg_match('/^\d{11}$/', $cuilCuit)) {
            return $data;
        }

        $data['resultado']['consultado'] = true;

        $carnet =
            $this->carnetRepository
                ->obtenerCarnetPublicoPorCuilCuit($cuilCuit);

        if (!$carnet) {
            return $data;
        }

        $foto =
            $this->documentoRepository
                ->obtenerFotoCarnet(
                    $carnet['usuario_id']
                );

        $data['resultado'] = [
            'consultado' => true,
            'encontrado' => true,

            'id_carnet' =>
                $carnet['id_carnet'],

            'nombre' =>
                $carnet['nombre'],

            'apellido' =>
                $carnet['apellido'],

            'cuil_cuit' =>
                $carnet['cuil_cuit'],

            'numero_carnet' =>
                $carnet['numero_carnet'],

            'fecha_emision' =>
                $carnet['fecha_emision'],

            'fecha_vencimiento' =>
                $carnet['fecha_vencimiento'],

            'vigente' =>
                $carnet['vigente'],

            'ruta_pdf' =>
                $carnet['ruta_pdf'],

            'foto_carnet' =>
                $foto['ruta_archivo'] ?? null
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