<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: MessageFactory
 *
 * Generates realistic message records for testing and seeding within
 * conversation threads between two users.
 *
 * Purpose:
 * - Simulates chat messages exchanged inside a Conversation.
 * - Supports generating standalone messages or messages tied to a specific conversation.
 *
 * Behavior:
 * - Default state generates only the message content.
 * - The `withConversation()` state attaches the message to a given Conversation:
 *      - Ensures the message timestamp occurs after the conversation start.
 *      - Randomly selects the sender from the two participants.
 *      - Optionally sets a read_at timestamp (70% chance).
 *
 * Usage:
 * - Message::factory()->create();
 * - Message::factory()->count(10)->create();
 * - Message::factory()->withConversation($conversation)->create();
 *
 * Related Models:
 * - App\Models\Message
 * - App\Models\Conversation
 */
class MessageFactory extends Factory
{
    /**
     * The model associated with this factory.
     *
     * @var class-string<Message>
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(),
        ];
    }

    /**
     * Attach the message to a specific conversation.
     *
     * Behavior:
     * - Sets conversation_id to the provided conversation.
     * - Ensures created_at is between the conversation start and now.
     * - Randomly selects sender_id from the conversation's two participants.
     * - Optionally sets read_at (70% probability).
     *
     * @param Conversation $conversation The conversation to attach the message to.
     *
     * @return static
     */
    public function withConversation(Conversation $conversation)
    {
        $convCreated = $conversation->created_at;
        $createdAt   = $this->faker->dateTimeBetween($convCreated, 'now');

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
