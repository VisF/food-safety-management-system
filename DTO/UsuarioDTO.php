<?php

declare(strict_types=1);

class UsuarioDTO
{
    public function __construct(
        private int $id,
        private string $nombre,
        private string $apellido,
        private string $dni,
        private string $email,
        private ?string $telefono,
        private ?string $domicilio,
        private array $roles,
        private bool $activo,
        private string $fechaCreacion
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['nombre'] ?? ''),
            (string)($data['apellido'] ?? ''),
            (string)($data['dni'] ?? ''),
            (string)($data['email'] ?? ''),
            $data['telefono'] ?? null,
            $data['domicilio'] ?? null,
            (array)($data['roles'] ?? ['usuario']),
            (bool)($data['activo'] ?? true),
            (string)($data['fecha_creacion'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'dni' => $this->dni,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'domicilio' => $this->domicilio,
            'roles' => $this->roles,
            'activo' => $this->activo,
            'fecha_creacion' => $this->fechaCreacion,
        ];
    }

    public function nombreCompleto(): string
    {
        return trim(
            $this->nombre . ' ' . $this->apellido
        );
    }

    public function estaActivo(): bool
    {
        return $this->activo;
    }

    public function tieneRol(string $rol): bool
    {
        return in_array(
            strtolower($rol),
            array_map('strtolower', $this->roles),
            true
        );
    }
    public function esAdmin(): bool
    {
        return $this->tieneRol('admin');
    }

    public function esUsuario(): bool
    {
        return $this->tieneRol('usuario');
    }

    public function esInspector(): bool
    {
        return $this->tieneRol('inspector');
    }


    // Getters
    public function getActivo(): bool
    {
        return $this->activo;
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function getDomicilio(): ?string
    {
        return $this->domicilio;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getFechaCreacion(): string
    {
        return $this->fechaCreacion;
    }
}