<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(),
        ];
    }

    public function withConversation(Conversation $conversation)
    {
        $convCreated = $conversation->created_at;
        $createdAt = $this->faker->dateTimeBetween($convCreated, 'now');

        $senderId = $this->faker->randomElement([
            $conversation->user_one_id,
            $conversation->user_two_id,
        ]);

        return $this->state([
            'conversation_id' => $conversation->id,
            'sender_id'       => $senderId,
            'created_at'      => $createdAt,
            'read_at'         => $this->faker->boolean(70)
                ? $this->faker->dateTimeBetween($createdAt, 'now')
                : null,
        ]);
    }
}
