<?php

namespace App\Contracts\AttachmentSecurity;

/**
 * Defines the contract for securely storing and retrieving uploaded files.
 * Ensures that files are stored in isolated, user-specific directories and
 * strict ownership is enforced during retrieval.
 */
interface SecureFileStorageInterface
{
    /**
     * Stores a validated temporary file into a permanent, secure, per-user directory structure.
     *
     * @param string $tempPath     The current absolute path of the temporary file.
     * @param string $safeFilename The generated secure and collision-resistant filename.
     * @param string $userId       The identifier of the user who owns the file.
     *
     * @return string The absolute path where the file was permanently stored.
     *
     * @throws \RuntimeException If the file cannot be moved to the storage location.
     */
    public function storeFile(string $tempPath, string $safeFilename, string $userId): string;

    /**
     * Resolves the absolute real path of a stored file while enforcing user ownership and filename format.
     *
     * @param string $filename The stored secure filename to look up.
     * @param string $userId   The identifier of the user requesting access to the file.
     *
     * @return string|null The absolute path to the file, or null if the file does not exist,
     *                     the filename is malformed, or the user does not own it.
     */
    public function getSecurePath(string $filename, string $userId): ?string;
}
