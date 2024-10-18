<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Notifications\CommentNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Comment.
 *
 * @property int $id
 * @property int $tiket_id
 * @property int $user_id
 * @property string $comment
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 * @property null|string $deleted_at
 * @property User $user
 * @property Ticket $ticket
 */
class Comment extends Model
{
    use SoftDeletes;
    protected $table = 'comments';

    protected $casts = [
        'tiket_id' => 'int',
        'user_id' => 'int',
    ];

    protected $fillable = [
        'tiket_id',
        'user_id',
        'comment',
        'attachments',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'tiket_id');
    }

    protected static function booted()
    {
        static::created(function ($comment) {
            $ticket = $comment->ticket;

            // Tentukan penerima notifikasi
            $receivers = [];

            if ($ticket->responsible_id) {
                // Jika ada responsible_id, kirim ke user tersebut
                $receiver = \App\Models\User::find($ticket->responsible_id);
                if ($receiver && $receiver->is_active) {
                    $receivers[] = $receiver;
                }
            } else {
                // Jika responsible_id tidak ada, kirim ke semua user dengan unit terkait
                $receivers = \App\Models\User::where('is_active', 1)
                    ->whereHas('roles', function ($query) use ($ticket) {
                        $query->whereIn('name', ['Super Admin', 'Admin Unit', 'Staf Unit'])
                            ->when($ticket->unit_id, function ($q) use ($ticket) {
                                $q->where('unit_id', $ticket->unit_id);
                            });
                    })
                    ->get();
            }

            // Kirim notifikasi ke semua penerima yang valid
            foreach ($receivers as $user) {
                $user->notify(new CommentNotification($comment));
            }
        });
    }
}
