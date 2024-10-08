<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TicketStatus.
 *
 * @property int $id
 * @property string $name
 * @property Collection|Ticket[] $tickets
 */
class TicketStatus extends Model
{
    use SoftDeletes;

    public const OPEN = 1;
    public const IN_PROGRESS = 2;
    public const CANCEL = 3;
    public const CLOSED = 4;
    public $timestamps = false;
    protected $table = 'ticket_statuses';

    protected $fillable = [
        'name',
    ];

    /**
     * Get all of the tickets for the TicketStatus.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'ticket_statuses_id');
    }
}
