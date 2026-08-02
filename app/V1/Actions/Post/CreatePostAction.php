<?php

namespace App\V1\Actions\Post;

use App\Models\Post;
use App\Models\User;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\DB;

/**
 * Handles the creation of a new post, including optional
 * image upload and eager loading of related data.
 */
class CreatePostAction
{
    /**
     * @param UploadFileService $service File upload handler used for post images.
     */
    public function __construct(
        private readonly UploadFileService $service
    ) {}

    /**
     * Create a new post for the given user.
     *
     * If an image is provided in the payload, it is uploaded first
     * (outside the DB transaction, since it's not a database operation).
     * The post record itself is then created inside a transaction to
     * ensure atomicity of the write.
     *
     * @param User  $user The authenticated user creating the post.
     * @param array $data Validated post data: 'content', 'visibility', and optional 'image'.
     *
     * @return Post The newly created post, with 'user', 'comments', and 'reactions' loaded.
     */
    public function handle(User $user, array $data): Post
    {
        $imagePath = null;

        if (isset($data['image'])) {
            $uploadResult = $this->service->upload($data['image'], (string) $user->id);
            $imagePath = $uploadResult['safe_filename'];
        }

        /** @var Post $post */
        $post = DB::transaction(function () use ($user, $data, $imagePath) {
            return Post::create([
                'user_id'    => $user->id,
                'content'    => $data['content'],
                'visibility' => $data['visibility'],
                'image'      => $imagePath,
            ]);
        });

        return $post->load(['user', 'comments', 'reactions']);
    }
}
