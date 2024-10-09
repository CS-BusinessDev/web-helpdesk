<?php

namespace App\Notifications;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketNotification extends Notification
{
    use Queueable;

    protected $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Kirim email notifikasi
        $mailMessage = (new MailMessage)
            ->subject('Terdapat tiket baru')
            ->line('Terdapat tiket baru yang perlu Anda periksa.')
            ->action('Lihat Tiket', url('/admin/tickets/' . $this->ticket->id));

        // Kirim pesan WhatsApp setelah email dikirim
        $this->toWhatsapp($notifiable);

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_statuses_id' => $this->ticket->ticket_statuses_id,
        ];
    }

    /**
     * Send the WhatsApp message notification using custom API.
     */
    public function toWhatsapp($notifiable)
{
    // Dapatkan nomor WhatsApp dari user yang akan dihubungi
    $phoneNumber = $notifiable->phone; // Asumsi bahwa nomor WhatsApp ada di field `phone`

    // Buat instance Guzzle Client
    $client = new Client();

    // Dapatkan endpoint dari .env
    $apiEndpoint = env('WHATSAPP_API_ENDPOINT');

    // Format pesan WhatsApp
    $message = "🔔 *Notifikasi Tiket Baru* 🔔\n\n";
    $message .= "Halo *" . $notifiable->name . "*, terdapat tiket baru yang perlu Anda periksa.\n\n";
    $message .= "📝 ID Tiket: *#" . $this->ticket->id . "*\n";
    $message .= "📌 Subjek: *" . $this->ticket->title . "*\n"; // Menampilkan subjek tiket
    $message .= "📅 Tanggal Dibuat: *" . $this->ticket->created_at->format('d M Y H:i') . "*\n";
    $message .= "🔗 Lihat Tiket: " . url('/admin/tickets/' . $this->ticket->id) . "\n\n";
    $message .= "Terima kasih, mohon segera ditindaklanjuti.\n\n";
    $message .= "— Bot";

    // Kirim request ke API custom Anda
    try {
        $response = $client->post($apiEndpoint, [
            'query' => [
                'apikey' => env('WHATSAPP_API_KEY'), // Ambil API key dari .env
                'sender' => env('WHATSAPP_SENDER_NUMBER'), // Ambil nomor pengirim dari .env
                'receiver' => $phoneNumber, // Nomor penerima
                'message' => $message // Gunakan pesan yang diformat
            ]
        ]);

        // Cek apakah request berhasil
        if ($response->getStatusCode() !== 200) {
            \Log::error('Gagal mengirim pesan WhatsApp: ' . $response->getBody());
        }
    } catch (\Exception $e) {
        \Log::error('Gagal mengirim pesan WhatsApp: ' . $e->getMessage());
    }
}

}
