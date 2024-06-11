<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TicketStatus::insert([
            ['id' => TicketStatus::OPEN, 'name' => 'Open'],
            ['id' => TicketStatus::IN_PROGRESS, 'name' => 'In Progress'],
            ['id' => TicketStatus::CANCEL, 'name' => 'Cancel'],
            ['id' => TicketStatus::CLOSED, 'name' => 'Closed'],
        ]);
    }
}
