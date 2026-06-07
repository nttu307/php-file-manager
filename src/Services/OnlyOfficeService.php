<?php

namespace Src\Services;

use Src\Core\Helpers;

class OnlyOfficeService
{
    private const OFFICE_EXTENSIONS = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'];

    public static function isOfficeFile(array $file): bool
    {
        return in_array(strtolower((string) ($file['extension'] ?? '')), self::OFFICE_EXTENSIONS, true);
    }

    public static function documentServerUrl(): string
    {
        global $config;
        return rtrim((string) ($config['onlyoffice']['document_server_url'] ?? ''), '/');
    }

    public static function isConfigured(): bool
    {
        return self::documentServerUrl() !== '';
    }

    public static function sourceUrl(array $file): string
    {
        global $config;

        $expiresAt = time() + max(60, (int) ($config['onlyoffice']['token_ttl'] ?? 900));
        $id = (int) $file['id'];
        $signature = self::signature($id, $expiresAt);

        return Helpers::appUrl('/onlyoffice_file.php?' . http_build_query([
            'id' => $id,
            'expires' => $expiresAt,
            'signature' => $signature,
        ]));
    }

    public static function validateSourceRequest(int $id, int $expiresAt, string $signature): bool
    {
        if ($id <= 0 || $expiresAt < time()) {
            return false;
        }

        return hash_equals(self::signature($id, $expiresAt), $signature);
    }

    public static function editorConfig(array $file, array $user = null): array
    {
        $extension = strtolower((string) ($file['extension'] ?? ''));
        $config = [
            'type' => 'desktop',
            'documentType' => self::documentType($extension),
            'width' => '100%',
            'height' => '100%',
            'document' => [
                'fileType' => $extension,
                'key' => self::documentKey($file),
                'title' => StorageService::safeDownloadName((string) ($file['original_name'] ?? 'document'), 'document.' . $extension),
                'url' => self::sourceUrl($file),
                'permissions' => [
                    'edit' => false,
                    'download' => true,
                    'print' => true,
                    'copy' => true,
                ],
            ],
            'editorConfig' => [
                'mode' => 'view',
                'lang' => 'en',
                'callbackUrl' => Helpers::appUrl('/onlyoffice_callback.php?id=' . (int) $file['id']),
                'user' => [
                    'id' => (string) ($user['id'] ?? 'guest'),
                    'name' => (string) ($user['name'] ?? 'Guest'),
                ],
                'customization' => [
                    'autosave' => false,
                    'forcesave' => false,
                ],
            ],
        ];

        $token = self::jwt($config);
        if ($token !== null) {
            $config['token'] = $token;
        }

        return $config;
    }

    private static function documentType(string $extension): string
    {
        return match ($extension) {
            'xls', 'xlsx', 'ods' => 'cell',
            'ppt', 'pptx', 'odp' => 'slide',
            default => 'word',
        };
    }

    private static function documentKey(array $file): string
    {
        return substr(hash('sha256', implode('|', [
            $file['id'] ?? '',
            $file['stored_name'] ?? '',
            $file['size'] ?? '',
            $file['created_at'] ?? '',
        ])), 0, 32);
    }

    private static function signature(int $id, int $expiresAt): string
    {
        return hash_hmac('sha256', $id . '|' . $expiresAt, self::secret());
    }

    private static function jwt(array $payload): ?string
    {
        $secret = self::jwtSecret();
        if ($secret === '') {
            return null;
        }

        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $header . '.' . $body, $secret, true));

        return $header . '.' . $body . '.' . $signature;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function secret(): string
    {
        global $config;
        $secret = (string) ($config['onlyoffice']['file_token_secret'] ?? '');

        return $secret !== '' ? $secret : (string) ($config['app_key'] ?? $config['app_name'] ?? 'php-file-manager');
    }

    private static function jwtSecret(): string
    {
        global $config;
        return (string) ($config['onlyoffice']['jwt_secret'] ?? '');
    }
}
