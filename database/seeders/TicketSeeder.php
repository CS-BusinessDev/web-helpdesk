<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketHistory;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ticket::$isSeeding = true;

        Ticket::create([
            'priority_id' => 1,
            'unit_id'   => 1,
            'owner_id'  => 1,
            'problem_category_id' => 1,
            'title' => 'This is a sample ticket',
            'description' => 'This is a descriptions',
            'ticket_statuses_id' => '1',
        ]);

        Ticket::$isSeeding = false;

        TicketHistory::create([
            'ticket_id' => 1,
            'ticket_statuses_id' => 1,
            'user_id' => 1,
            'created_at' => now(),
        ]);
    }
}
