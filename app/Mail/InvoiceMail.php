<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
  use Queueable, SerializesModels;


  public $data;
  public $pdf;

  /**
   * Create a new message instance.
   *
   * @param array $data  Datos de la factura (company, resolution, customer, sale, items)
   * @param string $pdf  Contenido PDF generado
   */
  public function __construct($data, $pdf)
  {
    $this->data = $data;
    $this->pdf = $pdf;
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      subject: 'Factura de compra ' . $this->data['resolution']->prefix . '-' . $this->data['sale']->invoiceNumber,
    );
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(
      markdown: 'emails.invoice',
    );
  }

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
    return [
      Attachment::fromData(fn() => $this->pdf, 'Factura-' . $this->data['resolution']->prefix . '-' . $this->data['sale']->invoiceNumber . '.pdf')
        ->withMime('application/pdf'),
    ];
  }
}
