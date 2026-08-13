<?php

namespace App\V1\Actions\Post;

use App\Events\PostDeleted;
use App\Models\Post;
use App\Services\UploadFileService;

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
     * The post's ID and author ID are captured BEFORE deletion and
     * passed as primitives to the PostDeleted event — the event must
     * never carry the Post model itself, since it's already gone from
     * the database by the time a queued listener tries to use it.
     *
     * @param Post $post The post to delete.
     *
     * @return void
     */
    public function handle(Post $post): void
    {
        $imagePath = $post->image;
        $postId = $post->id;
        $authorId = $post->user_id;

        $post->delete();

        if ($imagePath) {
            $this->service->deleteFile($imagePath, string($authorId));
        }

        event(new PostDeleted($postId, $authorId));
    }
}
