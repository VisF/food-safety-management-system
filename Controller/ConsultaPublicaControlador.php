<?php

require_once __DIR__ . '/../Services/ConsultaPublicaService.php';
require_once __DIR__ . '/../Views/consulta_publica.php';

class ConsultaPublicaControlador
{
    private ConsultaPublicaService $consultaPublicaService;

    public function __construct()
    {
        $this->consultaPublicaService = new ConsultaPublicaService();
    }

    public function mostrar(): void
    {
        $dni = trim($_GET['dni'] ?? '');

        $data = $this->consultaPublicaService->consultarPorDni($dni);

        $_GET['data'] = json_encode($data);

        $vista = new ConsultaPublicaVista();
        $vista->mostrar();
    }



    public function descargarCarnet(): void
    {
        $idCarnet = (int)($_GET['id'] ?? 0);

        $archivo = $this->consultaPublicaService->descargarCarnet($idCarnet);

        if (!$archivo) {
            http_response_code(404);
            exit('Carnet no encontrado.');
        }
        //RUTA DEL ARCHIVO PDF A REVISAR
        //BUSCA ACÁ
        $ruta = __DIR__ . '/../../uploads/' . $archivo['ruta_pdf'];

        if (!file_exists($ruta)) {
            http_response_code(404);
            exit('El archivo no existe.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $archivo['nombre_archivo'] . '"');
        header('Content-Length: ' . filesize($ruta));

        readfile($ruta);
        exit;
    }

    public function descargarFoto(): void
    {
        $idCarnet = (int)($_GET['id'] ?? 0);

        $foto = $this->consultaPublicaService->descargarFotoPorCarnet($idCarnet);

        if (!$foto) {
            http_response_code(404);
            exit('Foto no encontrada.');
        }

        //RUTA DEL ARCHIVO FOTO CARNET A REVISAR
        //BUSCA ACÁ


        $ruta = __DIR__ . '/../../' . $foto['ruta_archivo'];

        if (!file_exists($ruta)) {
            http_response_code(404);
            exit('El archivo no existe.');
        }

        header('Content-Type: ' . $foto['tipo_mime']);
        header('Content-Disposition: attachment; filename="' . $foto['nombre_original'] . '"');
        header('Content-Length: ' . filesize($ruta));

        readfile($ruta);
        exit;
    }


}