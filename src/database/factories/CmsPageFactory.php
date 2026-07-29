<?php

namespace Database\Factories;

use App\Models\CmsPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Фабрика информационных страниц CMS ЭТП (электронной торговой площадки).
 *
 * @extends Factory<CmsPage>
 */
class CmsPageFactory extends Factory
{
    /**
     * Модель фабрики.
     *
     * @var class-string<CmsPage>
     */
    protected $model = CmsPage::class;

    /**
     * Черновик страницы CMS без публикации.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'title' => $title,
            'meta_title' => fake()->optional()->sentence(4),
            'meta_description' => fake()->optional()->sentence(10),
            'is_published' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Опубликованная страница (видна гостю).
     *
     * @return static
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }
}
