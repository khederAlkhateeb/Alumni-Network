<?php

namespace App\Contracts\AttachmentSecurity;

use Illuminate\Http\UploadedFile;

/**
 * Defines the contract for validating uploaded files against strict security rules,
 * including MIME type verification, extension consistency, and content safety checks.
 */
interface FileValidatorInterface
{
    /**
     * Validates an uploaded file's integrity, size, real MIME type, and binary content.
     *
     * @param UploadedFile $file The uploaded file instance from the HTTP request.
     *
     * @return array{
     *     valid: bool,
     *     error: string,
     *     safe_filename: string
     * } An array containing the boolean validation status, an error message (if invalid),
     *   and a newly generated secure filename (if valid).
     */
    public function validateFile(UploadedFile $file): array;
}
