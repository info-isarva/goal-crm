<?php

namespace App\Mail;

use App\Models\QuotationEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class QuotationSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $htmlBody,
        public string $pdfBinary,
        public string $pdfFilename,
        public ?string $fromAddress = null,
        public ?string $fromName = null,
        public ?string $replyToAddress = null,
        public ?string $logoPath = null,
        public ?string $logoMime = null,
    ) {
        if ($this->logoPath && is_file($this->logoPath)) {
            $path = $this->logoPath;
            $mime = $this->logoMime ?? 'image/jpeg';
            $cid = QuotationEmailTemplate::EMAIL_LOGO_CID;

            $this->withSymfonyMessage(function (Email $message) use ($path, $mime, $cid) {
                $message->embedFromPath($path, $cid, $mime);
            });
        }
    }

    public function envelope(): Envelope
    {
        $envelope = [
            'subject' => $this->emailSubject,
        ];

        if (!empty($this->fromAddress)) {
            $envelope['from'] = new Address($this->fromAddress, $this->fromName ?: '');
        }

        if (!empty($this->replyToAddress)) {
            $envelope['replyTo'] = [new Address($this->replyToAddress, $this->fromName ?: '')];
        }

        return new Envelope(...$envelope);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlBody,
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
