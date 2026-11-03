<?php

declare(strict_types=1);

require_once __DIR__ . '/../Repository/RecoveryTokenRepository.php';
require_once __DIR__ . '/../Repository/UsuarioRepository.php';
require_once __DIR__ . '/../db/Connection.php';

/**
 * RecoveryTokenService
 *
 * Gestiona la lógica de negocio para recuperar contraseñas.
 */
class RecoveryTokenService
{
    private RecoveryTokenRepository $recoveryTokenRepository;
    private UsuarioRepository $usuarioRepository;
    private PDO $conexion;

    private const DURACION_TOKEN_MINUTOS = 60;
    private const LONGITUD_TOKEN_BYTES = 32;

    public function __construct()
    {
        $this->recoveryTokenRepository = new RecoveryTokenRepository();
        $this->usuarioRepository = new UsuarioRepository();
        $this->conexion = Connection::getPDO();
    }

    /**
     * Genera un token de recuperación para un usuario.
     *
     * Devuelve el token original para incluirlo en el enlace enviado
     * por correo. En la base de datos solamente se guarda su hash.
     *
     * @return array{token: string, expiracion: string}
     */
    public function crearToken(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del usuario no es válido.'
            );
        }

        $token = bin2hex(random_bytes(self::LONGITUD_TOKEN_BYTES));
        $tokenHash = hash('sha256', $token);

        $ahora = new DateTimeImmutable();
        $expiracion = $ahora->modify(
            '+' . self::DURACION_TOKEN_MINUTOS . ' minutes'
        );

        $fechaExpiracion = $expiracion->format('Y-m-d H:i:s');

        $this->conexion->beginTransaction();

        try {
            // Una solicitud nueva invalida las anteriores del usuario.
            $this->recoveryTokenRepository
                ->invalidarPorUsuario($usuarioId);

            $this->recoveryTokenRepository->crear(
                $usuarioId,
                $tokenHash,
                $fechaExpiracion
            );

            $this->conexion->commit();

            return [
                'token' => $token,
                'expiracion' => $fechaExpiracion,
            ];
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Comprueba si un token existe, no fue utilizado,
     * no venció y tiene menos de una hora de antigüedad.
     *
     * @return array|null Datos del token válido o null.
     */
    public function validarToken(string $token): ?array
    {
        if (
            $token === ''
            || preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1
        ) {
            return null;
        }

        $tokenHash = hash('sha256', $token);

        $registro = $this->recoveryTokenRepository
            ->obtenerValidoPorHash($tokenHash);

        if (!$registro || empty($registro['fecha_creacion'])) {
            return null;
        }

        try {
            $zonaUTC = new DateTimeZone('UTC');

            $creacion = new DateTimeImmutable(
                $registro['fecha_creacion'],
                $zonaUTC
            );

            $ahora = new DateTimeImmutable('now', $zonaUTC);

        } catch (Throwable $e) {
            return null;
        }

        $antiguedad = $ahora->getTimestamp()
            - $creacion->getTimestamp();

        if (
            $antiguedad < 0
            || $antiguedad >= self::DURACION_TOKEN_MINUTOS * 60
        ) {
            return null;
        }

        return $registro;
    }

    /**
     * Cambia la contraseña y consume el token en una transacción.
     *
     * La contraseña recibida debe ser texto plano; se almacena
     * únicamente su hash.
     */
    public function restablecerPassword(string $token, string $password): bool {

        if (strlen($password) < 8) {
            throw new InvalidArgumentException(
                'La contraseña debe tener al menos 8 caracteres.'
            );
        }

        if (strlen($password) > 72) {
            throw new InvalidArgumentException(
                'La contraseña no puede superar los 72 bytes.'
            );
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException(
                'La contraseña debe contener al menos una mayúscula.'
            );
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException(
                'La contraseña debe contener al menos una minúscula.'
            );
        }

        $registro = $this->validarToken($token);

        if (!$registro) {
            return false;
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_BCRYPT
        );

        if ($passwordHash === false) {
            throw new RuntimeException(
                'No se pudo procesar la nueva contraseña.'
            );
        }

        $this->conexion->beginTransaction();

        try {

            // Primero consumimos el token.
            $consumido = $this->recoveryTokenRepository
                ->marcarComoUsado((int) $registro['id']);

            if (!$consumido) {
                $this->conexion->rollBack();
                return false;
            }

            // Actualizamos la contraseña.
            $actualizado = $this->usuarioRepository->cambiarPassword(
                (int) $registro['usuario_id'],
                $passwordHash
            );

            if (!$actualizado) {
                throw new RuntimeException(
                    'No se pudo actualizar la contraseña.'
                );
            }

            // Invalidamos los demás enlaces pendientes.
            $this->recoveryTokenRepository
                ->invalidarPorUsuario((int) $registro['usuario_id']);

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }
}
