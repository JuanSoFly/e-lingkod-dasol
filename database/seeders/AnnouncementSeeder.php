<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Announcement::create([
            'title' => 'System Maintenance Notice',
            'message' => 'The HRIS system will undergo maintenance this weekend.',
            'type' => 'warning',
            'is_important' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(2),
        ]);

        Announcement::create([
            'title' => 'Performance Review Period',
            'message' => 'Annual performance review period has started. Please complete your self-assessments.',
            'type' => 'info',
            'is_important' => true,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(10),
        ]);

        Announcement::create([
            'title' => 'Holiday Party 2025',
            'message' => 'Join us for the annual holiday party on December 20th!',
            'type' => 'success',
            'is_important' => false,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}
