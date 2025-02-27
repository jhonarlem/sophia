<x-mail::message>
  # Factura de tu compra en {{ $data['company']->name }}

  Estimado/a {{ $data['customer']->name }},

  Adjuntamos la factura correspondiente a tu compra realizada el {{ $data['sale']->saleDate }}. A continuación, algunos detalles importantes:

  - **Número de factura:** {{ $data['resolution']->prefix }}-{{ $data['sale']->invoiceNumber }}
  - **Total:** ${{ $data['sale']->totalAmount }}
  - **Método de pago:** {{ $data['sale']->paymentMethod }}
  - **Estado:** {{ $data['sale']->status }}

  Gracias por tu compra. Si tienes alguna pregunta, no dudes en contactarnos.

  Saludos,<br>
  {{ $data['company']->name }} - {{ config('app.name') }}

  ---
  *Este es un correo automático. Por favor, no respondas a esta dirección de correo electrónico ya que no está siendo monitoreada. Para cualquier solicitud o reclamo, dirígete a nuestro establecimiento o comunícate con nosotros a través de WhatsApp al número {{ $data['company']->cellphone }}.*
</x-mail::message>