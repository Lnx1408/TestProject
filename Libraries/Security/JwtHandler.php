<?php
namespace Libraries\Security;

class JwtHandler {
    private $secretKey;
    private $algorithm;
    private $issuer;
    private $tokenLifetime;

    public function __construct() {
        // Idealmente estos valores deberían venir de la configuración
        $this->secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key-change-in-production';
        $this->algorithm = 'HS256';
        $this->issuer = getenv('JWT_ISSUER') ?: 'reqscape-api';
        $this->tokenLifetime = getenv('JWT_LIFETIME') ?: 3600; // 1 hora por defecto
    }

    /**
     * Genera un token JWT
     * 
     * @param array $userData Datos del usuario a incluir en el token
     * @param string $sessionId ID de la sesión
     * @return string Token JWT
     */
    public function generateToken($userData, $sessionId) {
        $issuedAt = time();
        $expiration = $issuedAt + $this->tokenLifetime;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'iss' => $this->issuer,
            'data' => [
                'user_id' => $userData['id_jugador'],
                'user_type' => $userData['id_tipo'] ?? $userData['tipo_usuario'],
                'session_id' => $sessionId
            ]
        ];

        return $this->encode($payload);
    }

    /**
     * Valida y decodifica un token JWT
     * 
     * @param string $token Token JWT a validar
     * @return array|null Payload decodificado o null si no es válido
     */
    public function validateToken($token) {
        try {
            $decoded = $this->decode($token);
            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Codifica un array en un token JWT
     * 
     * @param array $payload Datos a codificar
     * @return string Token JWT
     */
    private function encode($payload) {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => $this->algorithm,
            'typ' => 'JWT'
        ]));
        
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->generateSignature($header, $payloadEncoded);
        
        return "$header.$payloadEncoded.$signature";
    }

    /**
     * Decodifica un token JWT y verifica su firma
     * 
     * @param string $token Token JWT
     * @return array Payload decodificado
     * @throws \Exception Si el token es inválido
     */
    private function decode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new \Exception('Token inválido');
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verificar firma
        $valid = $this->verifySignature($header, $payload, $signature);
        if (!$valid) {
            throw new \Exception('Firma inválida');
        }
        
        // Decodificar payload
        $payloadDecoded = json_decode($this->base64UrlDecode($payload), true);
        
        // Verificar expiración
        if (isset($payloadDecoded['exp']) && $payloadDecoded['exp'] < time()) {
            throw new \Exception('Token expirado');
        }
        
        return $payloadDecoded;
    }

    /**
     * Genera la firma para un token JWT
     * 
     * @param string $header Header codificado
     * @param string $payload Payload codificado
     * @return string Firma codificada
     */
    private function generateSignature($header, $payload) {
        $signature = hash_hmac('sha256', "$header.$payload", $this->secretKey, true);
        return $this->base64UrlEncode($signature);
    }

    /**
     * Verifica la firma de un token JWT
     * 
     * @param string $header Header codificado
     * @param string $payload Payload codificado
     * @param string $signature Firma a verificar
     * @return bool Resultado de la verificación
     */
    private function verifySignature($header, $payload, $signature) {
        $expectedSignature = $this->generateSignature($header, $payload);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Codifica en Base64Url
     * 
     * @param string $data Datos a codificar
     * @return string Datos codificados
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica Base64Url
     * 
     * @param string $data Datos a decodificar
     * @return string Datos decodificados
     */
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Extrae información básica del token sin validar firma
     * Útil solo para obtener metadatos básicos, no para autenticación
     * 
     * @param string $token Token JWT
     * @return array|null Datos decodificados o null si hay error
     */
    public function getTokenInfo($token) {
        try {
            list($headerB64, $payloadB64, $signature) = explode('.', $token);
            $payloadJson = $this->base64UrlDecode($payloadB64);
            $payload = json_decode($payloadJson, true);
            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }
}