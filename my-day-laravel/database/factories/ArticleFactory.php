<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['A quiet morning in the garden', 'A new idea for a neighborhood gathering', 'Finding joy in a familiar recipe', 'A walk can brighten the day', 'Making time for a favorite hobby']),
            'summary' => 'A fictional sample story to demonstrate the My Day news section.',
            'body' => 'This is an example article created for the My Day demo. It is fictional and does not describe a real event. An administrator can replace it with a verified story.',
            'category' => fake()->randomElement(['Community', 'Lifestyle', 'Wellbeing']),
            'published' => true, 'is_sample' => true,
        ];
    }
}
