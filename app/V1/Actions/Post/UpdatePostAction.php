<?php

namespace App\V1\Actions\Post;

use App\Models\Post;
use App\Models\User;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\DB;

/**
 * Handles updating an existing post, including optional
 * image replacement and eager loading of related data.
 */
class UpdatePostAction
{
    /**
     * @param UploadFileService $service File upload handler used for post images.
     */
    public function __construct(
        private readonly UploadFileService $service
    ) {}

    /**
     * Update an existing post.
     *
     * If a new image is provided, it is uploaded first (outside the DB
     * transaction, since it's not a database operation) and the old
     * image, if any, is removed afterward. The post record itself is
     * updated inside a transaction to ensure atomicity of the write.
     *
     * Note: the post owner (user_id) is never modified here — ownership
     * is fixed at creation time and is not part of an update payload.
     *
     * @param User  $user The authenticated user performing the update (used for the upload path/ownership check upstream).
     * @param Post  $post The post being updated.
     * @param array $data Validated post data: optional 'content', 'visibility', and optional 'image'.
     *
     * @return Post The updated post, with 'user', 'comments', and 'reactions' loaded.
     */
    public function handle(User $user, Post $post, array $data): Post
    {
        $imagePath = $post->image;
        $oldImagePath = null;

        if (isset($data['image'])) {
            $uploadResult = $this->service->upload($data['image'], $user);
            $imagePath = $uploadResult['safe_filename'];
            $oldImagePath = $post->image;
        }

        DB::transaction(function () use ($post, $data, $imagePath) {
            $post->update([
                'content'    => $data['content'] ?? $post->content,
                'visibility' => $data['visibility'] ?? $post->visibility,
                'image'      => $imagePath,
            ]);
        });

        if ($oldImagePath) {
            $this->service->deleteFile($oldImagePath);
        }

        return $post->fresh()->load(['user', 'comments', 'reactions']);
    }
}
