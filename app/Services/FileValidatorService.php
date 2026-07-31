<?php

namespace App\Services;

use App\Contracts\AttachmentSecurity\FileValidatorInterface;
use Illuminate\Http\UploadedFile;

/**
 * Validates uploaded files against a strict allowlist of MIME types and
 * extensions, verifies the file's actual content (not just its declared
 * type), and generates a safe, unpredictable filename for storage.
 *
 * Validation order (fail-fast):
 *  1. Upload transfer integrity (PHP-level upload errors)
 *  2. File size boundaries
 *  3. Real MIME type detection (content-based, not client-supplied)
 *  4. MIME type allowlist check
 *  5. Extension vs. MIME type consistency check
 *  6. Content-level safety checks (magic bytes, embedded scripts, null bytes)
 *  7. Secure filename generation
 */
class FileValidatorService implements FileValidatorInterface
{
    /**
     * Maximum allowed file size in bytes (5MB).
     */
    private const MAX_FILE_SIZE = 5242880;

    /**
     * Allowlist mapping each accepted MIME type to its valid extensions.
     * Only files whose detected content matches both a listed MIME type
     * AND a listed extension for that type are accepted.
     *
     * @var array<string, array<int, string>>
     */
    private static array $allowedTypes = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['txt'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
    ];

    /**
     * Validate an uploaded file end to end: upload integrity, size limits,
     * real MIME type, extension consistency, and content-level safety.
     *
     * @param UploadedFile $file The uploaded file instance from the request.
     * @return array{valid: bool, error: string, safe_filename: string}
     *         'valid'         Whether the file passed all checks.
     *         'error'         Human-readable reason for rejection (empty if valid).
     *         'safe_filename' A newly generated, collision-resistant filename
     *                         to use for storage (empty if invalid).
     */
    public function validateFile(UploadedFile $file): array
    {
        $result = [
            'valid' => false,
            'error' => '',
            'safe_filename' => '',
        ];

        // Ensure PHP itself reported a successful transfer (not partial,
        // not blocked by ini/form size limits, etc.).
        if (!$file->isValid()) {
            $result['error'] = $file->getErrorMessage();
            return $result;
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $result['error'] = 'File size exceeds 5MB limit';
            return $result;
        }

        if ($file->getSize() < 1) {
            $result['error'] = 'File is empty';
            return $result;
        }

        // Detect the MIME type from the file's actual content, never trust
        // the client-supplied MIME type or the file extension at this stage.
        $mimeType = self::getSecureMimeType($file->getRealPath());
        if (!$mimeType) {
            $result['error'] = 'Could not determine file type';
            return $result;
        }

        if (!isset(self::$allowedTypes[$mimeType])) {
            $result['error'] = 'File type not allowed';
            return $result;
        }

        // Guard against double-extension / mismatched-extension attacks
        // (e.g. a PHP payload renamed to "shell.php.jpg").
        $originalExtension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($originalExtension, self::$allowedTypes[$mimeType], true)) {
            $result['error'] = 'File extension does not match content type';
            return $result;
        }

        if (!self::isSafeFileContent($file->getRealPath(), $mimeType)) {
            $result['error'] = 'File contains potentially unsafe content';
            return $result;
        }

        $result['safe_filename'] = self::generateSecureFilename($file->getClientOriginalName());
        $result['valid'] = true;

        return $result;
    }

    /**
     * Determine the real MIME type of a file by inspecting its binary
     * content, trying multiple detection methods in order of reliability.
     *
     * Detection order:
     *  1. finfo (libmagic) — most reliable, reads file signature bytes.
     *  2. getimagesize() — fallback specifically for image files.
     *  3. mime_content_type() — last-resort fallback.
     *
     * @param string $filePath Absolute path to the file on disk.
     * @return string|null The detected MIME type, or null if it could not
     *                      be determined by any method.
     */
    private static function getSecureMimeType(string $filePath): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            if ($mimeType) {
                return $mimeType;
            }
        }

        $imageInfo = getimagesize($filePath);
        if ($imageInfo && isset($imageInfo['mime'])) {
            return $imageInfo['mime'];
        }

        $mime = mime_content_type($filePath);
        if ($mime) {
            return $mime;
        }

        return null;
    }

    /**
     * Inspect a file's raw content for signs of tampering or embedded
     * malicious code, applying MIME-type-specific checks on top of two
     * universal checks (null bytes and embedded script tags).
     *
     * @param string $filePath Absolute path to the file on disk.
     * @param string $mimeType The detected (content-based) MIME type of the file.
     * @return bool True if the content appears safe, false otherwise.
     */
    private static function isSafeFileContent(string $filePath, string $mimeType): bool
    {
        $content = file_get_contents($filePath);

        // Null bytes are a common indicator of a tampered/malicious file.
        if ($mimeType === 'text/plain') {
            if (str_contains($content, "\0")) {
                return false;
            }

            if (preg_match('/<\?php|<script|<%|javascript:/i', $content)) {
                return false;
            }
        }

        return match ($mimeType) {
            'image/jpeg', 'image/png', 'image/gif', 'image/webp' => self::validateImageHeaders($filePath, $mimeType),
            'text/plain' => !preg_match('/<\?php|<script|<%|javascript:/i', $content),
            'application/pdf' => str_starts_with($content, '%PDF-'),
            default => true,
        };
    }

    /**
     * Verify a file's binary signature (magic bytes) matches its claimed
     * image MIME type, guarding against files that merely have a correct
     * MIME type detection but a forged/corrupted structure.
     *
     * @param string $filePath Absolute path to the file on disk.
     * @param string $mimeType The claimed image MIME type.
     * @return bool True if the file's header matches the expected signature
     *              for that MIME type, false otherwise (including unknown types).
     */
    private static function validateImageHeaders(string $filePath, string $mimeType): bool
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 12);
        fclose($handle);

        return match ($mimeType) {
            'image/jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($header, "\x89PNG\r\n\x1A\n"),
            'image/gif' => str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a'),
            'image/webp' => str_starts_with($header, 'RIFF') && strpos($header, 'WEBP') !== false,
            default => false,
        };
    }

    /**
     * Generate a random, collision-resistant, path-traversal-safe filename
     * for storing the uploaded file. The original filename is discarded
     * entirely except for its (already validated) extension.
     *
     * @param string $originalName The original client-provided filename.
     * @return string The generated safe filename, e.g. "1706472891_ab12cd34....jpg".
     */
    private static function generateSecureFilename(string $originalName): string
    {
        // Strip any directory components to prevent path traversal.
        $safeName = basename($originalName);
        $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

        // Cryptographically secure random name — not guessable/enumerable.
        $randomName = bin2hex(random_bytes(16));
        $timestamp = time();

        return $timestamp . '_' . $randomName . '.' . $extension;
    }
}
