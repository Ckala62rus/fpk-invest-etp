<?php

namespace Database\Factories;

use App\Enums\NotificationEventType;
use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->slug(2, '_');

        return [
            'code' => $code,
            'name' => fake()->sentence(3),
            'subject' => 'Тема: {{procedure.number}}',
            'body_html' => '<p>Здравствуйте, {{user.email}}!</p>',
            'event_type' => NotificationEventType::Event,
            'is_active' => true,
        ];
    }

    /**
     * @param string $code Код шаблона
     * @return static
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
        ]);
    }
}
