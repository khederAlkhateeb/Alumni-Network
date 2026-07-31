<?php

namespace App\Services;

use App\Contracts\AttachmentSecurity\SecureFileStorageInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Stores previously validated files on disk in a per-user, date-partitioned
 * directory structure, and resolves stored files back to their real path
 * with strict ownership and filename-format enforcement.
 *
 * Files are stored outside the public webroot by default, so they are never
 * directly reachable by URL — access must always go through application
 * code that performs its own authorization checks.
 */
class SecureFileStorageService implements SecureFileStorageInterface
{
    /**
     * Absolute base directory under which all user upload directories live.
     * Always normalized to end with exactly one trailing slash.
     */
    private string $baseDir;

    /**
     * @param string $baseDir Absolute path to the root storage directory.
     *                        Should live outside the public webroot.
     */
    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = rtrim($baseDir ?? storage_path('app/secure-uploads'), '/\\') . DIRECTORY_SEPARATOR;

        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0755, true);
        }

        chmod($this->baseDir, 0755);
    }

    /**
     * Move an already-validated temporary file into permanent, per-user,
     * date-partitioned storage and lock down its file permissions.
     *
     * Resulting layout: {baseDir}/user_{userId}/{Y}/{m}/{d}/{safeFilename}
     *
     * @param string $tempPath     Path to the temporary uploaded file
     *                             (e.g. from UploadedFile::getRealPath()).
     * @param string $safeFilename The already-generated secure filename
     *                             (see FileValidatorService::generateSecureFilename()).
     * @param string $userId       ID of the user who owns this file, used to
     *                             isolate their files from other users' files.
     * @return string The absolute path where the file was stored.
     *
     * @throws RuntimeException If the file could not be moved into storage.
     */
    public function storeFile(string $tempPath, string $safeFilename, string $userId): string
    {
        $userDir = $this->baseDir . 'user_' . $userId . '/';
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        $dateDir = $userDir . date('Y/m/d') . '/';
        if (!is_dir($dateDir)) {
            mkdir($dateDir, 0755, true);
        }

        $finalPath = $dateDir . $safeFilename;

        if (move_uploaded_file($tempPath, $finalPath)) {
            // Owner/group read-write, no execute permission for anyone —
            // prevents the stored file from ever being run as a script,
            // even if malicious content somehow slipped past validation.
            chmod($finalPath, 0640);

            return $finalPath;
        }

        throw new RuntimeException('Failed to store file');
    }

    /**
     * Resolve a stored file's real path for a given user, enforcing both
     * the expected secure-filename format and ownership (the file must
     * live inside that user's own directory).
     *
     * Returns null (rather than throwing) whenever the filename is
     * malformed, does not belong to the given user, or does not exist —
     * this prevents leaking information about which case occurred and
     * closes off IDOR-style enumeration attempts.
     *
     * @param string $filename The stored filename to look up
     *                         (must match the generateSecureFilename() format).
     * @param string $userId   ID of the user requesting the file.
     * @return string|null The absolute path to the file, or null if not found
     *                      or not owned by this user.
     */
    public function getSecurePath(string $filename, string $userId): ?string
    {
        // Reject anything that doesn't match our own generated filename
        // format (timestamp_hex.ext) before touching the filesystem at all.
        if (!preg_match('/^\d+_[a-f0-9]+\.[a-zA-Z0-9]+$/', $filename)) {
            return null;
        }

        $userDir = $this->baseDir . 'user_' . $userId . '/';
        if (!is_dir($userDir)) {
            return null;
        }

        // Search only within this user's own directory tree — a file
        // belonging to another user can never be resolved this way.
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($userDir));

        foreach ($iterator as $file) {
            if ($file->isFile() && basename($file->getFilename()) === $filename) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
