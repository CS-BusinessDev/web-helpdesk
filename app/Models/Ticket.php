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
        'business_entities_id' => 'int',
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
        'business_entities_id',
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

    /**
     * Get the businessEntity that owns the Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class, 'business_entities_id');
    }


    protected static function boot()
    {
        parent::boot();
    
        // Event listener untuk event 'saving'
        static::saving(function ($ticket) {
            if ($ticket->isDirty('ticket_statuses_id')) {
                $receiver = User::find($ticket->owner_id);
    
                if ($ticket->ticket_statuses_id != 1 && is_null($ticket->approved_at)) {
                    $ticket->approved_at = Carbon::now();
                }
    
                if ($ticket->ticket_statuses_id == 4) {
                    $ticket->solved_at = Carbon::now();
                    if ($receiver) {
                        $receiver->notify(new ClosedTicketNotification($ticket));
                    }
                }
            }
        });
    
        // Event listener untuk event 'created'
        static::created(function ($ticket) {
            if (self::$isSeeding) {
                return;
            }
    
            // Membuat riwayat tiket baru
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'ticket_statuses_id' => $ticket->ticket_statuses_id,
                'user_id' => Auth::id(),
                'created_at' => now(),
            ]);
    
            // Logika untuk mengirim notifikasi berdasarkan responsible_id
            if ($ticket->responsible_id) {
                // Jika responsible_id ada, kirim ke user tersebut
                $receiver = User::find($ticket->responsible_id);
                if ($receiver) {
                    Notification::make()
                        ->title('Terdapat tiket baru yang menjadi tanggung jawab Anda')
                        ->actions([
                            Action::make('Lihat')
                                ->url(TicketResource::getUrl('view', ['record' => $ticket->id])),
                        ])
                        ->sendToDatabase($receiver);
                    $receiver->notify(new NewTicketNotification($ticket));
                }
            } else {
                // Jika responsible_id tidak ada, kirim ke semua user dengan unit kerja terkait
                $receivers = User::whereHas('roles', function ($q) use ($ticket) {
                    $q->where(function ($query) use ($ticket) {
                        $query->where('name', 'Super Admin')
                              ->orWhere(function ($subQuery) use ($ticket) {
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
            }
        });
    
        // Event listener untuk event 'updated'
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
