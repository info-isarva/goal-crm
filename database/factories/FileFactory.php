<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition()
    {
        return [
            'file_type' => $this->faker->randomElement(['file upload', 'file links']),
            'file_path' => $this->faker->filePath(),
            'file_name' => $this->faker->word() . '.txt',
            'related_type' => $this->faker->word(),
            'related_id' => $this->faker->randomNumber(),
            'user_restored_id' => $this->faker->randomNumber(),
            'user_owner_id' => $this->faker->randomNumber(),
            'user_assigned_id' => $this->faker->randomNumber(),
            'uploaded_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }
}
