<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class RelatorioConcorrentesMail extends Mailable
{
    use Queueable, SerializesModels;

    public $csvContent;
    public $dataRelatorio;

    /**
     * Recebe o conteúdo do CSV (string) e a data de referência.
     */
    public function __construct($csvContent, $dataRelatorio)
    {
        $this->csvContent = $csvContent;
        $this->dataRelatorio = $dataRelatorio;
    }

    /**
     * Define o Assunto do E-mail.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Relatório Diário de Concorrentes - ' . $this->dataRelatorio,
        );
    }

    /**
     * Define o Corpo do E-mail.
     */
    public function content(): Content
    {
        // Aqui usamos htmlString para um corpo simples, sem precisar criar um arquivo .blade.php
        return new Content(
            htmlString: '
                <p>Olá,</p>
                <p>O robô finalizou a coleta de preços de hoje.</p>
                <p>Segue em anexo o relatório detalhado com os preços encontrados.</p>
                <br>
                <p>Atenciosamente,<br><strong></strong></p>
            '
        );
    }

    /**
     * Anexa o arquivo CSV gerado em memória.
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->csvContent, "Relatorio_Precos_{$this->dataRelatorio}.csv")
                ->withMime('text/csv'),
        ];
    }
}
