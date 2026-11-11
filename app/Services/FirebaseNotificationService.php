<?php
// app/Services/FirebaseNotificationService.php
namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/service-account.json'));

        $this->messaging = $factory->createMessaging();
    }

    // Send to a single device token
    public function sendToDevice(string $token, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $this->messaging->send($message);
    }

    // Send to multiple tokens at once
    public function sendToMultiple(array $tokens, string $title, string $body, array $data = []): void
    {
        $notification = Notification::create($title, $body);

        $messages = array_map(
            fn($token) => CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data),
            $tokens
        );

        $this->messaging->sendAll($messages);
    }

    // Send to a Firebase topic (e.g. "news", "alerts")
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $this->messaging->send($message);
    }

    public function sendNotification(string $token, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $this->messaging->send($message);
    }
}