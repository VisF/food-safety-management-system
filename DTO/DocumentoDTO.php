<?php

declare(strict_types=1);

class DocumentoDTO
{
    public function __construct(
        private int $id,
        private int $usuarioId,
        private string $tipoDocumento,
        private string $nombreOriginal,
        private string $rutaArchivo,
        private string $estado,
        private string $fechaSubida,
        private ?string $fechaRevision,
        private ?string $observaciones

    ) {
    }

    public static function fromArray(array $data): self {
        return new self(
            (int)($data['id'] ?? 0),
            (int)($data['usuario_id'] ?? 0),
            (string)($data['tipo_documento'] ?? ''),
            (string)($data['nombre_original'] ?? ''),
            (string)($data['ruta_archivo'] ?? ''),
            match ($data['estado'] ?? 'pendiente') {
                'aprobado' => 'aprobado',
                'rechazado' => 'rechazado',
                default => 'pendiente'
            },
            (string)($data['fecha_subida'] ?? ''),
            $data['fecha_revision'] ?? null,
            $data['observaciones'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'tipo_documento' => $this->tipoDocumento,
            'ruta_archivo' => $this->rutaArchivo,
            'estado' => $this->estado,
            'fecha_subida' => $this->fechaSubida,
            'observaciones' => $this->observaciones
        ];
    }
    public function getNombreOriginal(): string
    {
        return $this->nombreOriginal;
    }



    public function validar(int $id, string $observaciones = ''): bool 
    {
        return
            $this->documentoModelo
                ->cambiarEstado(
                    $id,
                    $observaciones
                );
    }
    public function estaAprobado(): bool
    {
        return $this->estado === 'aprobado';
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function estaRechazado(): bool
    {
        return $this->estado === 'rechazado';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsuarioId(): int
    {
        return $this->usuarioId;
    }

    public function getTipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function getRutaArchivo(): string
    {
        return $this->rutaArchivo;
    }

    public function getEstado(): string
    {
        return $this->estado;
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