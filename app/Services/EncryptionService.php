<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EncryptionService
{
    private string $cipher = 'aes-256-gcm';
    private int $keyLength = 32;
    private int $ivLength = 16;
    private int $tagLength = 16;

    /**
     * Encrypt data using AES-256-GCM
     */
    public function encrypt(string $data, ?string $key = null): array
    {
        try {
            $key = $key ?? $this->getEncryptionKey();
            $iv = random_bytes($this->ivLength);
            $tag = '';

            $encrypted = openssl_encrypt(
                $data,
                $this->cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                $this->tagLength
            );

            if ($encrypted === false) {
                throw new \Exception('Encryption failed: ' . openssl_error_string());
            }

            return [
                'success' => true,
                'data' => base64_encode($encrypted . $tag),
                'iv' => base64_encode($iv),
                'cipher' => $this->cipher,
            ];
        } catch (\Exception $e) {
            Log::error('Encryption error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Decrypt data using AES-256-GCM
     */
    public function decrypt(string $encryptedData, string $iv, ?string $key = null): array
    {
        try {
            $key = $key ?? $this->getEncryptionKey();
            $decoded = base64_decode($encryptedData);
            $iv = base64_decode($iv);

            // Extract tag (last 16 bytes)
            $tag = substr($decoded, -$this->tagLength);
            $ciphertext = substr($decoded, 0, -$this->tagLength);

            $decrypted = openssl_decrypt(
                $ciphertext,
                $this->cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                throw new \Exception('Decryption failed: ' . openssl_error_string());
            }

            return ['success' => true, 'data' => $decrypted];
        } catch (\Exception $e) {
            Log::error('Decryption error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Encrypt file for storage
     */
    public function encryptFile(string $filePath, ?string $key = null): array
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            $content = file_get_contents($filePath);
            $result = $this->encrypt($content, $key);

            if (!$result['success']) {
                return $result;
            }

            // Write encrypted file
            $encryptedPath = $filePath . '.enc';
            $metadata = [
                'iv' => $result['iv'],
                'cipher' => $result['cipher'],
                'original_name' => basename($filePath),
                'encrypted_at' => now()->toIso8601String(),
            ];

            file_put_contents($encryptedPath, $result['data']);
            file_put_contents($encryptedPath . '.meta', json_encode($metadata));

            // Remove original file
            unlink($filePath);

            return [
                'success' => true,
                'path' => $encryptedPath,
                'metadata' => $metadata,
            ];
        } catch (\Exception $e) {
            Log::error('File encryption error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Decrypt file for retrieval
     */
    public function decryptFile(string $encryptedPath, ?string $key = null): array
    {
        try {
            $metaPath = $encryptedPath . '.meta';
            
            if (!file_exists($encryptedPath) || !file_exists($metaPath)) {
                throw new \Exception('Encrypted file or metadata not found');
            }

            $metadata = json_decode(file_get_contents($metaPath), true);
            $encryptedData = file_get_contents($encryptedPath);

            return $this->decrypt($encryptedData, $metadata['iv'], $key);
        } catch (\Exception $e) {
            Log::error('File decryption error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate a new encryption key
     */
    public function generateKey(): string
    {
        return base64_encode(random_bytes($this->keyLength));
    }

    /**
     * Hash data for integrity verification
     */
    public function hash(string $data): string
    {
        return hash_hmac('sha256', $data, $this->getEncryptionKey());
    }

    /**
     * Verify data integrity
     */
    public function verify(string $data, string $hash): bool
    {
        return hash_equals($hash, $this->hash($data));
    }

    /**
     * Get encryption key from environment
     */
    private function getEncryptionKey(): string
    {
        $key = config('app.encryption_key_256');
        
        if (empty($key)) {
            throw new \Exception('Encryption key not configured');
        }

        return base64_decode($key);
    }
}
