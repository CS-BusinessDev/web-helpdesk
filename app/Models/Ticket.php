<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;
use App\Filament\Resources\TicketResource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Notifications\NewTicketNotification;
use App\Notifications\ClosedTicketNotification;

/**
 * Class Ticket.
 *
 * @property int $id
 * @property int $priority_id
 * @property int $unit_id
 * @property int $owner_id
 * @property int $problem_category_id
 * @property string $title
 * @property string $description
 * @property int $ticket_statuses_id
 * @property null|int $responsible_id
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 * @property null|Carbon $approved_at
 * @property null|Carbon $solved_at
 * @property null|string $deleted_at
 * @property Priority $priority
 * @property Unit $unit
 * @property null|User $user
 * @property ProblemCategory $problem_category
 * @property TicketStatus $ticket_status
 * @property Collection|Comment[] $comments
 */
class Ticket extends Model
{
    use SoftDeletes;
    protected $table = 'tickets';

    protected $casts = [
        'priority_id' => 'int',
        'unit_id' => 'int',
        'owner_id' => 'int',
        'problem_category_id' => 'int',
        'ticket_statuses_id' => 'int',
        'responsible_id' => 'int',
        'approved_at' => 'datetime',
        'solved_at' => 'datetime',
    ];

    protected $fillable = [
        'priority_id',
        'unit_id',
        'owner_id',
        'problem_category_id',
        'title',
        'description',
        'ticket_statuses_id',
        'responsible_id',
        'approved_at',
        'solved_at',
    ];

    // Preventif error in migration
    public static $isSeeding = false;

    /**
     * Get the priority that owns the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    /**
     * Get the unit that owns the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the owner that owns the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the responsible that owns the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * Get the problemCategory that owns the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function problemCategory()
    {
        return $this->belongsTo(ProblemCategory::class);
    }

    /**
     * Get the ticketStatus that owns the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticketStatus()
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_statuses_id');
    }

    /**
     * Get all of the comments for the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'tiket_id');
    }

        /**
     * Get the ticketHistories that owns the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticketHistories()
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id');
    }


    protected static function boot()
    {
        parent::boot();

        // Menambahkan event listener untuk event 'saving' pada model
        static::saving(function ($ticket) {
            // Memeriksa apakah 'ticket_statuses_id' berubah
            if ($ticket->isDirty('ticket_statuses_id')) {
                $receiver = User::find($ticket->owner_id);
                // Jika 'ticket_statuses_id' berubah dan tidak sama dengan 1, serta 'approved_at' masih null, atur 'approved_at' dengan waktu saat ini
                if ($ticket->ticket_statuses_id != 1 && is_null($ticket->approved_at)) {
                    $ticket->approved_at = Carbon::now();
                }

                // Jika 'ticket_statuses_id' 4, atur 'solved_at' dengan waktu saat ini
                if ($ticket->ticket_statuses_id == 4) {                    
                    $ticket->solved_at = Carbon::now();
                    if ($receiver) {
                        $receiver->notify(new ClosedTicketNotification($ticket));
                    }
                }
            }
        });

        // Menambahkan event listener untuk event 'created' pada model
        static::created(function ($ticket) {
            if (self::$isSeeding) {
                return;
            }

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'ticket_statuses_id' => $ticket->ticket_statuses_id,
                'user_id' => Auth::id(),
                'created_at' => now(),
            ]);


            $receivers = User::whereHas('roles', function ($q) use ($ticket) {
                $q->where(function($query) use ($ticket) {
                    $query->where('name', 'Super Admin')
                          ->orWhere(function($subQuery) use ($ticket) {
                              $subQuery->whereIn('name', ['Admin Unit', 'Staf Unit'])
                                       ->where('unit_id', $ticket->unit_id);
                          });
                });
            })->where('is_active', 1)->get();

            foreach ($receivers as $receiver) {
                Notification::make()
                    ->title('Terdapat tiket baru')
                    ->actions([
                        Action::make('Lihat')
                            ->url(TicketResource::getUrl('view', ['record' => $ticket->id])),
                    ])
                    ->sendToDatabase($receiver);
                    $receiver->notify(new NewTicketNotification($ticket));
            }

        });

        // Menambahkan event listener untuk event 'updated' pada model
        static::updated(function ($ticket) {
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'ticket_statuses_id' => $ticket->ticket_statuses_id,
                'user_id' => Auth::id(),
                'created_at' => now(),
            ]);
        });
    }
}
