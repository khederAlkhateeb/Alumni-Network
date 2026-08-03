<?php

namespace App\Services;

use App\Contracts\AttachmentSecurity\FileValidatorInterface;
use App\Contracts\AttachmentSecurity\SecureFileStorageInterface;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Exception;

/**
 * Orchestrates the secure file upload workflow by bridging the validation
 * and storage services.
 */
class UploadFileService
{
    /**
     * Injects the required interfaces for validation and storage to adhere
     * to the Dependency Inversion Principle.
     *
     * @param FileValidatorInterface     $fileValidator
     * @param SecureFileStorageInterface $secureFileStorageService
     */
    public function __construct(
        private readonly FileValidatorInterface $fileValidator,
        private readonly SecureFileStorageInterface $secureFileStorageService
    ) {}

    /**
     * Validates the uploaded file and stores it securely in a user-isolated directory.
     *
     * @param UploadedFile $file   The uploaded file instance from the HTTP request.
     * @param string       $userId The identifier of the user uploading the file.
     *
     * @return array{
     *     success: bool,
     *     file_path: string,
     *     safe_filename: string
     * } The result containing the status, absolute path, and the generated secure filename.
     *
     * @throws Exception If file validation fails or if the storage process encounters an error.
     */
    public function upload(UploadedFile $file, string $userId): array
    {
        // 1. Perform strict validation on the uploaded file
        $validationResult = $this->fileValidator->validateFile($file);

        // 2. Reject the file if validation fails
        if (!$validationResult['valid']) {
            throw new Exception('File validation failed: ' . $validationResult['error']);
        }

        // 3. Extract the temporary path and the generated safe filename
        $tempPath = $file->getRealPath();
        $safeFilename = $validationResult['safe_filename'];

        // 4. Attempt to move the file to secure storage
        try {
            $finalPath = $this->secureFileStorageService->storeFile($tempPath, $safeFilename, $userId);

            // 5. Return the file details upon successful storage
            return [
                'success'       => true,
                'file_path'     => $finalPath,
                'safe_filename' => $safeFilename,
            ];

        } catch (RuntimeException $e) {
            // Optional: Log the exact error for internal monitoring
            // Log::error('File storage failed: ' . $e->getMessage());

            throw new Exception('An error occurred while saving the file: ' . $e->getMessage());
        }
    }
/**
     * Deletes a previously stored file using its safe filename and user ID.
     *
     * @param string|null $safeFilename The generated secure filename from DB.
     * @param string      $userId       The owner user ID.
     *
     * @return bool True if deleted, false if file missing or filename is empty.
     *
     * @throws Exception If an error occurs during deletion.
     */
    public function deleteFile(?string $safeFilename, string $userId): bool
    {
        if (empty($safeFilename)) {
            return false;
        }
        try {
            $realPath = $this->secureFileStorageService->getSecurePath($safeFilename, $userId);

            if (!$realPath) {
                return false;
            }

            return $this->secureFileStorageService->deleteFile($realPath);

        } catch (RuntimeException $e) {
            throw new Exception('An error occurred while deleting the file: ' . $e->getMessage());
        }
    }
}
