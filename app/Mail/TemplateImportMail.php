<?php

namespace App\Mail;

use App\Models\Projet;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplateImportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Projet $projet,
        public string $nomPromoteur,
        public ?string $messagePersonnalise,
        public string $fichierPath,
    ) {
        $this->messagePersonnalise = $this->messagePersonnalise ?? '';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Template d'import - Projet {$this->projet->nom} ({$this->projet->commune->nom})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.template-import',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->fichierPath)
                ->as("Template_Import_{$this->projet->nom}.xlsx")
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
