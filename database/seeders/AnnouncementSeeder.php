<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing announcements to avoid duplicates
        $this->command->info('Clearing existing announcements...');
        Announcement::truncate();

        // Get an admin user to assign as the creator of announcements
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'Super Admin');
        })->first() ?? User::whereHas('roles', function ($q) {
            $q->where('name', 'HR Admin');
        })->first() ?? User::first();

        $adminId = $admin ? $admin->id : null;

        if ($admin) {
            $this->command->info("Creating announcements created by user: {$admin->name} ({$admin->email})");
        } else {
            $this->command->warn('No user accounts found. Creating announcements with null creator.');
        }

        $now = Carbon::now();

        $announcements = [
            [
                'title' => 'Emergency Power Outage & System Downtime',
                'message' => 'Please be informed that the municipal hall will experience a scheduled power interruption this coming Saturday from 8:00 AM to 5:00 PM for substation maintenance. All local servers, including the e-Lingkod portal, will be offline during this window. Please save your work and shut down all office workstations before leaving on Friday.',
                'type' => 'danger',
                'is_important' => true,
                'starts_at' => $now->copy()->subHours(6),
                'ends_at' => $now->copy()->addDays(3),
                'created_by' => $adminId,
            ],
            [
                'title' => 'IPCR/OPCR Submission Deadline Approaching',
                'message' => 'The deadline for the submission of the Individual Performance Commitment and Review (IPCR) and Office Performance Commitment and Review (OPCR) for the current performance period is fast approaching. Late submissions will affect the processing of performance-based incentives. Please coordinate with your department heads for guidance.',
                'type' => 'warning',
                'is_important' => true,
                'starts_at' => $now->copy()->subDays(2),
                'ends_at' => $now->copy()->addDays(14),
                'created_by' => $adminId,
            ],
            [
                'title' => 'Dasol HRIS Portal v2.0 Successfully Deployed',
                'message' => 'We are pleased to announce the successful deployment of e-Lingkod Dasol v2.0! This update brings enhanced leave management tracking, automatic accruals, and an improved Personal Data Sheet (PDS) interface. For any issues or bugs, please file a ticket with the IT Helpdesk.',
                'type' => 'success',
                'is_important' => true,
                'starts_at' => $now->copy()->subDays(5),
                'ends_at' => $now->copy()->addDays(5),
                'created_by' => $adminId,
            ],
            [
                'title' => 'Civil Service Commission (CSC) Examination Applications',
                'message' => 'The Civil Service Commission is now accepting applications for the upcoming Career Service Examination-Pen and Paper Test (CSE-PPT). Interested employees who wish to take the exam may download the application forms from the CSC official website or visit the HR office for assistance.',
                'type' => 'info',
                'is_important' => false,
                'starts_at' => $now->copy()->subDays(10),
                'ends_at' => $now->copy()->addDays(30),
                'created_by' => $adminId,
            ],
            [
                'title' => 'LGU Dasol General Assembly 2024',
                'message' => 'All LGU employees are invited to attend the General Assembly in the Municipal Gymnasium. Agenda includes the presentation of the municipal roadmap, budget presentation, and department milestones.',
                'type' => 'info',
                'is_important' => false,
                'starts_at' => $now->copy()->subYears(2),
                'ends_at' => $now->copy()->subYears(2)->addDays(10),
                'created_by' => $adminId,
            ],
            [
                'title' => 'Scheduled Mid-Year Team Building Seminar',
                'message' => 'The Human Resource Management Office is organizing a two-day Capacity Building and Team Development Seminar. Attendance is mandatory for all permanent and casual employees. Stay tuned for registration and venue details.',
                'type' => 'info',
                'is_important' => false,
                'starts_at' => $now->copy()->addDays(15),
                'ends_at' => $now->copy()->addDays(30),
                'created_by' => $adminId,
            ],
        ];

        foreach ($announcements as $announcementData) {
            Announcement::create($announcementData);
        }

        $this->command->info('Announcements seeded successfully.');
    }
}
