<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmployeeDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentTypes = ['id_card', 'passport', 'birth_certificate', 'diploma', 'license', 'contract', 'other'];
        $mimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $extensions = ['pdf', 'jpg', 'png'];
        
        $selectedMimeType = $this->faker->randomElement($mimeTypes);
        $extension = $selectedMimeType === 'application/pdf' ? 'pdf' : 
                    ($selectedMimeType === 'image/jpeg' ? 'jpg' : 'png');

        return [
            'employee_id' => Employee::factory(),
            'document_type' => $this->faker->randomElement($documentTypes),
            'file_name' => $this->faker->lastName() . '_' . $this->faker->word() . '.' . $extension,
            'file_path' => 'private/employee_documents/' . $this->faker->numberBetween(1, 100) . '/' . $this->faker->uuid() . '.' . $extension,
            'uploaded_by' => User::factory(),
            'uploaded_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'file_size' => $this->faker->numberBetween(1024, 5 * 1024 * 1024), // 1KB to 5MB
            'mime_type' => $selectedMimeType,
            'extracted_content' => $this->faker->optional(0.7)->paragraph(),
            'content_indexed_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 year', 'now'),
            'content_hash' => $this->faker->optional(0.6)->sha256(),
            'search_metadata' => $this->faker->optional(0.5)->randomElement([
                [
                    'indexed_at' => now()->toISOString(),
                    'file_type' => $extension,
                    'indexing_version' => '1.0'
                ],
                [
                    'extraction_failed' => true,
                    'reason' => 'Unsupported file format'
                ]
            ]),
        ];
    }

    /**
     * Indicate that the document has been indexed.
     */
    public function indexed(): static
    {
        return $this->state(fn (array $attributes) => [
            'extracted_content' => $this->faker->paragraphs(3, true),
            'content_indexed_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'content_hash' => $this->faker->sha256(),
            'search_metadata' => [
                'indexed_at' => now()->toISOString(),
                'file_type' => pathinfo($attributes['file_name'], PATHINFO_EXTENSION),
                'mime_type' => $attributes['mime_type'],
                'indexing_version' => '1.0'
            ],
        ]);
    }

    /**
     * Indicate that the document indexing failed.
     */
    public function indexingFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'extracted_content' => null,
            'content_indexed_at' => null,
            'content_hash' => null,
            'search_metadata' => [
                'indexing_failed_permanently' => true,
                'final_error' => 'Content extraction failed',
                'failed_at' => now()->toISOString(),
                'total_attempts' => 3
            ],
        ]);
    }

    /**
     * Indicate that the document is of a specific type.
     */
    public function ofType(string $documentType): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => $documentType,
        ]);
    }

    /**
     * Indicate that the document is a PDF.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => $this->faker->lastName() . '_' . $this->faker->word() . '.pdf',
            'file_path' => 'private/employee_documents/' . $this->faker->numberBetween(1, 100) . '/' . $this->faker->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    /**
     * Indicate that the document is an image.
     */
    public function image(): static
    {
        $extension = $this->faker->randomElement(['jpg', 'png']);
        $mimeType = $extension === 'jpg' ? 'image/jpeg' : 'image/png';

        return $this->state(fn (array $attributes) => [
            'file_name' => $this->faker->lastName() . '_' . $this->faker->word() . '.' . $extension,
            'file_path' => 'private/employee_documents/' . $this->faker->numberBetween(1, 100) . '/' . $this->faker->uuid() . '.' . $extension,
            'mime_type' => $mimeType,
        ]);
    }

    /**
     * Indicate that the document is large.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $this->faker->numberBetween(10 * 1024 * 1024, 50 * 1024 * 1024), // 10MB to 50MB
        ]);
    }

    /**
     * Indicate that the document is small.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $this->faker->numberBetween(1024, 100 * 1024), // 1KB to 100KB
        ]);
    }

    /**
     * Indicate that the document was uploaded recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the document is old.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_at' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
        ]);
    }
}