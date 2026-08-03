<?php

namespace App\V1\Actions\Post;

use App\Models\Post;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\Cache;

/**
 * Handles deleting a post along with its associated image file (if any).
 */
class DeletePostAction
{
    /**
     * @param UploadFileService $service File upload/storage handler used for post images.
     */
    public function __construct(
        private readonly UploadFileService $service
    ) {}

    /**
     * Delete a post and its associated image file.
     *
     * The image is removed after the post record is deleted, so that
     * a failed deletion does not leave the post pointing to a file
     * that no longer exists.
     *
     * @param Post $post The post to delete.
     *
     * @return void
     */
    public function handle(Post $post): void
    {
        $imagePath = $post->image;
        $userId = $post->user_id;
        $post->delete();

        if ($imagePath) {
            $this->service->deleteFile($imagePath);
        }
        Cache::forget("feed_user_{$userId}");
    }
}
