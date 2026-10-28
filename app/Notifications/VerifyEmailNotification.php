<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Xác thực địa chỉ Email của bạn - Wanderly')
            ->greeting('Xin chào ' . ($notifiable->name ?? 'Bạn') . '!')
            ->line('Chào mừng bạn đến với Wanderly - Nền tảng lập kế hoạch và đồng hành du lịch thông minh.')
            ->line('Vui lòng bấm vào nút bên dưới để xác thực địa chỉ email và bắt đầu tạo các hành trình du lịch tuyệt vời cùng bạn bè:')
            ->action('Xác thực Email ngay', $verificationUrl)
            ->line('Link xác thực này sẽ hết hạn sau 60 phút.')
            ->line('Nếu bạn không đăng ký tài khoản Wanderly, bạn hoàn toàn có thể bỏ qua email này.')
            ->salutation('Trân trọng, Đội ngũ Wanderly');
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
