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
     * Deletes a previously stored file from secure storage.
     *
     * @param string $filePath The path of the file to delete (as returned by upload()).
     *
     * @return bool True if the file was deleted successfully, false otherwise.
     *
     * @throws Exception If the storage service encounters an error while deleting.
     */
    public function deleteFile(string $filePath): bool
    {
        if (!$filePath) {
            return false;
        }

        try {
            return $this->secureFileStorageService->deleteFile($filePath);
        } catch (RuntimeException $e) {
            throw new Exception('An error occurred while deleting the file: '
                . $e->getMessage());
        }
    }
}
