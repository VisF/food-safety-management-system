<?php

declare(strict_types=1);

class InscripcionDTO
{
    public function __construct(
        private int $id,
        private int $usuarioId,
        private ?int $cursoId,
        private ?int $examenId,
        private int $tipoInscripcionId,
        private int $estadoId,
        private string $fechaInscripcion,
        private ?string $observaciones,

        private ?string $tipoInscripcion = null,
        private ?string $estadoNombre = null,
        private ?string $estadoDescripcion = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['id'] ?? 0),
            (int)($data['usuario_id'] ?? $data['id_usuario'] ?? 0),
            isset($data['curso_id']) ? (int)$data['curso_id'] : null,
            isset($data['examen_id']) ? (int)$data['examen_id'] : null,
            (int)($data['tipo_inscripcion_id'] ?? $data['id_tipo_inscripcion'] ?? 0),
            (int)($data['estado_tramite_id'] ?? $data['id_estado'] ?? 0),
            (string)($data['fecha_inscripcion'] ?? ''),
            $data['observaciones'] ?? null,

            $data['tipo_inscripcion'] ?? null,
            $data['estado_nombre'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'curso_id' => $this->cursoId,
            'examen_id' => $this->examenId,
            'tipo_inscripcion_id' => $this->tipoInscripcionId,
            'estado_id' => $this->estadoId,
            'fecha_inscripcion' => $this->fechaInscripcion,
            'observaciones' => $this->observaciones,
            'tipo_inscripcion' => $this->tipoInscripcion,
            'estado_nombre' => $this->estadoNombre
        ];
    }

    public function estaActiva(): bool
    {
        return in_array(
            $this->estadoId,
            [1, 2],
            true
        );
    }

    // getters...
    public function getId(): int { return $this->id; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getCursoId(): ?int { return $this->cursoId; }
    public function getExamenId(): ?int { return $this->examenId; }
    public function getTipoInscripcionId(): int { return $this->tipoInscripcionId; }
    public function getEstadoId(): int { return $this->estadoId; }
    public function getFechaInscripcion(): string { return $this->fechaInscripcion; }
    public function getObservaciones(): ?string { return $this->observaciones; }
    public function getTipoInscripcion(): ?string { return $this->tipoInscripcion; }
    public function getEstadoNombre(): ?string { return $this->estadoNombre; }
    public function getEstadoDescripcion(): ?string { return $this->estadoDescripcion; }
}