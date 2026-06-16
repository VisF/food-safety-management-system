<?php

declare(strict_types=1);

class DocumentoDTO
{
    public function __construct(
        private int $id,
        private int $idInscripcion,
        private string $tipoDocumento,
        private string $rutaArchivo,
        private int $validado,
        private string $fechaSubida,
        private ?string $observaciones
    ) {
    }

    public static function fromArray(
        array $data
    ): self {

        return new self(
            (int)($data['id'] ?? 0),
            (int)($data['id_inscripcion'] ?? 0),
            (string)($data['tipo_documento'] ?? ''),
            (string)($data['ruta_archivo'] ?? ''),
            match ($data['estado'] ?? 'pendiente') {
                        'aprobado' => 1,
                        'rechazado' => -1,
                        default => 0
                    },
            (string)($data['fecha_subida'] ?? ''),
            $data['observaciones'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'id_inscripcion' => $this->idInscripcion,
            'tipo_documento' => $this->tipoDocumento,
            'ruta_archivo' => $this->rutaArchivo,
            'validado' => $this->validado,
            'fecha_subida' => $this->fechaSubida,
            'observaciones' => $this->observaciones
        ];
    }
    public function validar(int $id, string $observaciones = ''): bool 
    {
        return
            $this->documentoModelo
                ->validar(
                    $id,
                    $observaciones
                );
    }

    public function estaValidado(): int
    {
        return $this->validado === 1;
    }
    public function estaRechazado(): int
    {
        return $this->validado === -1;
    }
    public function estaPendiente(): bool
    {
        return $this->validado === 0;
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function getIdInscripcion(): int
    {
        return $this->idInscripcion;
    }

    public function getTipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function getRutaArchivo(): string
    {
        return $this->rutaArchivo;
    }

    public function getValidado(): int
    {
        return $this->validado;
    }

    public function getFechaSubida(): string
    {
        return $this->fechaSubida;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }
}