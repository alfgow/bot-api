<?php
/**
 * ─── JWT Helper (Pure PHP, no Composer) ───────────────────
 * Implements HS256 JWT signing and verification.
 */

class JWT
{
    /**
     * Encode a payload into a JWT token.
     */
    public static function encode(array $payload, string $secret): string
    {
        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]));

        $payload = self::base64UrlEncode(json_encode($payload));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $secret, true)
        );

        return "$header.$payload.$signature";
    }

    /**
     * Decode and verify a JWT token.
     * Returns the payload array on success.
     * Throws an Exception on failure.
     */
    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Token inválido.');
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $secret, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Token inválido.');
        }

        $data = json_decode(self::base64UrlDecode($payload), true);

        if ($data === null) {
            throw new Exception('Token inválido.');
        }

        // Check expiration
        if (isset($data['exp']) && $data['exp'] < time()) {
            throw new Exception('Token expirado.');
        }

        return $data;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
