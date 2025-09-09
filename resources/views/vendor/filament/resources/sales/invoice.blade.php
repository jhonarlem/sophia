<!-- factura.blade.php -->
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Factura de venta {{$resolution->prefix}}-{{ $sale->invoiceNumber }}</title>

  <style>
    @font-face {
      font-family: 'Sora';
      src: url('fonts/Sora.ttf') format('truetype');
      font-weight: 400, 500, 600, 700;
      font-style: normal;
    }

    body {
      font-family: 'Sora';
    }

    /* Poner todos los h1 y h3 font-weight 700 */
    h1,
    h3 {
      font-weight: 600;
    }

    p,
    td {
      font-weight: 500;
    }
  </style>

</head>

<body style="margin: 0; padding: 0px;">
  <div style="border-radius: 10px; max-width: 210mm; margin: 0 auto; padding: 20px;">
    <!-- Encabezado -->
    <div style="border: 1px solid #bce9f5; border-radius: 10px; padding: 10px; padding-bottom: 10px; margin-bottom: 10px; background-color:rgb(255, 255, 255);">

      <table width="100%">
        <tr>
          <!-- Columna para el logo -->
          <td style="width: 10%; text-align: center; vertical-align: middle;">
            <table width="100%" height="100%">
              <tr>
                <td style="text-align: center; vertical-align: middle;">
                  <img src="{{ $company->logo }}" style="max-width: 100px; max-height: 80px; display: block; margin: auto;">
                </td>
              </tr>
            </table>
          </td>


          <!-- Columna para la información de la empresa -->
          <td style="vertical-align: top; width: 50%;">
            <h1 style="margin: 0; font-size: 20px; color: #0a85a0;">{{$company->name}}</h1>
            <p style="margin: 0.5px 0; font-size: 14px; font-weight: 600;"> {{$company->legal_representative}}</p>
            <p style="margin: 0.5px 0; font-size: 14px; font-weight: 600;">{{$company->document_type}}: {{$company->document_number}}-{{$company->dv}}</p>
            <p style="margin: 0.5px 0; font-size: 14px; font-weight: 600;"> {{$company->address}}</p>
            <p style="margin: 0.5px 0; font-size: 14px; font-weight: 600;">{{$company->cellphone}}</p>
            <p style="margin: 0.5px 0; font-size: 14px; font-weight: 600;"> {{$company->email}}</p>
          </td>

          <!-- Columna para la información de la factura -->
          <td style="vertical-align: top; text-align: right; width: 40%;">
            <h2 style="margin: 0; font-size: 18px; color: #0a85a0;">FACTURA DE VENTA</h2>
            <p style="margin: 0.5px 0; font-size: 14px; font-weight: 600;">No. {{$resolution->prefix}}-{{ $sale->invoiceNumber }}</p>
            <p style="margin: 0.5px 0; font-size: 13px; font-weight: 600;">Fecha: {{$sale->saleDate}}</p>
          </td>
        </tr>
      </table>

    </div>


    <!-- Información DIAN y resolución -->
    <div style="background-color: #e6f7fc; padding: 10px; margin-bottom: 10px; border: 1px solid #bce9f5; border-radius: 10px;">
      <h3 style="margin: 0 0 2px 0; font-size: 14px; color: #0a85a0;">INFORMACIÓN TRIBUTARIA</h3>
      <p style="margin: 1px 0; font-size: 13px;"><strong>Resolución DIAN:</strong> {{$resolution->number}}</p>
      <p style="margin: 1px 0; font-size: 13px;"><strong>Fecha de resolución: </strong>{{$resolution->startDate}}</p>
      <p style="margin: 1px 0;  font-size: 13px;"><strong>Vigencia:</strong> {{$resolution->validMonths}} meses</p>
      <p style="margin: 1px 0;  font-size: 13px;"><strong>Rango autorizado: </strong>
        {{$resolution->startInvoice}} al {{$resolution->endInvoice}}<!-- {{$resolution->prefix}}-{{$resolution->startInvoice}} al {{$resolution->prefix}}-{{$resolution->endInvoice}} -->
      </p>
      <p style="margin: 1px 0;  font-size: 13px;"><strong>Régimen tributario: </strong>{{$company->regime}}</p>
    </div>

    <!-- Información del cliente -->
    <div style="background-color:rgb(255, 255, 255); padding: 10px; margin-bottom: 10px; border: 1px solid #bce9f5; border-radius: 10px; ">
      <h3 style="margin: 0 0 2px 0; font-size: 14px; color: #0a85a0;">INFORMACIÓN DEL CLIENTE</h3>
      <table width="100%" style="font-size: 13px; border-collapse: collapse;">
        <tr>
          <td style="padding: 1px; text-align: left;"><strong>Nombre:</strong> {{$customer->name}}</td>
          <td style="padding: 1px; text-align: left;"><strong>Celular: </strong> {{$customer->cellphone}}</td>
        </tr>
        <tr>
          <td style="padding: 1px; text-align: left;"><strong>Documento: </strong>{{$customer->documentType}} {{$customer->documentNumber}}</td>
          <td style="padding: 1px; text-align: left;"><strong>Correo: </strong>{{$customer->email}}</td>
        </tr>
      </table>
    </div>

    <!-- Detalles de la factura -->
    <div style="margin-bottom: 10px;">
      <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #0a85a0;">DETALLE DE PRODUCTOS</h3>
      <!-- Contenedor con borde exterior redondeado -->
      <div style="border: 1px solid #bce9f5; border-radius: 10px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
          <thead>
            <tr style="background-color: #e6f7fc;">
              <!-- Se asignan bordes internos solo en las divisiones entre celdas -->
              <th style="padding: 8px; text-align: left; border-right: 1px solid #bce9f5;">Producto</th>
              <th style="padding: 8px; text-align: center; border-right: 1px solid #bce9f5;">Precio</th>
              <th style="padding: 8px; text-align: center; border-right: 1px solid #bce9f5;">Cantidad</th>
              <th style="padding: 8px; text-align: center; border-right: 1px solid #bce9f5;">IVA</th>
              <th style="padding: 8px; text-align: center;">Total</th>
            </tr>
          </thead>
          <tbody>
            <!-- Para cada fila, se dibujan las líneas internas (solo separadoras) -->
            @foreach($items as $index => $item)
            <tr>
              <td style="padding: 8px;  text-align: left; border-right: 1px solid #bce9f5; border-top: 1px solid #bce9f5;">
                {{ $item->product }}
              </td>
              <td style="padding: 8px;  text-align: right; border-right: 1px solid #bce9f5; border-top: 1px solid #bce9f5;">
                {{ $item->price }}
              </td>
              <td style="padding: 8px;  text-align: center; border-right: 1px solid #bce9f5; border-top: 1px solid #bce9f5;">
                {{ $item->quantity }}
              </td>
              <td style="padding: 8px;  text-align: right; border-right: 1px solid #bce9f5; border-top: 1px solid #bce9f5;">
                {{ $item->iva }}%
              </td>
              <td style="padding: 8px;  text-align: right; border-top: 1px solid #bce9f5;">
                {{ $item->total }}
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <!-- Totales -->
    <div style="overflow: hidden; margin-bottom: 10px;">
      <div style="width: 60%; float: left;">
        <div style="background-color: #e6f7fc; padding: 10px; border: 1px solid #bce9f5; border-radius: 10px;">
          <h3 style="margin: 0 0 2px 0; font-size: 14px; color: #0a85a0;">INFORMACIÓN DE PAGO</h3>
          <p style="margin: 1px 0; font-size: 13px;  "><strong>Método de pago: </strong> {{$sale->paymentMethod}}</p>
          <p style="margin: 1px 0; font-size: 13px;  "><strong>Recibido: </strong> ${{$sale->received}}</p>
          <p style="margin: 1px 0; font-size: 13px;  "><strong>Estado:</strong> {{$sale->status}}</p>
        </div>
      </div>
      <div style="width: 35%; float: right;">
        <table style="width: 100%; font-size: 13px;  border-collapse: separate; border-spacing: 0;">
          <tr>
            <td style="padding: 1px; text-align: right; border: none;"><strong>Subtotal:</strong></td>
            <td style="padding: 1px; text-align: right; border: none;">$ {{$sale->subtotal}}</td>
          </tr>
          <tr>
            <td style="padding: 1px; text-align: right; border: none;"><strong>IVA (19%):</strong></td>
            <td style="padding: 1px; text-align: right; border: none;">$ {{$sale->totalIva}}</td>
          </tr>
          <tr>
            <td style="padding: 1px; text-align: right; border: none;"><strong>Descuentos:</strong></td>
            <td style="padding: 1px; text-align: right; border: none;">-$ {{$sale->totalDiscount}}</td>
          </tr>
          <tr style="background-color: #e6f7fc;">
            <!-- Celda con borde redondeado izquierdo -->
            <td style="padding: 8px; text-align: right; border-left: 1px solid #bce9f5; border-top: 1px solid #bce9f5; border-bottom: 1px solid #bce9f5; border-top-left-radius: 10px; border-bottom-left-radius: 10px;">
              <strong>TOTAL A PAGAR:</strong>
            </td>
            <!-- Celda con borde redondeado derecho -->
            <td style="padding: 8px; text-align: right; border-right: 1px solid #bce9f5; border-top: 1px solid #bce9f5; border-bottom: 1px solid #bce9f5; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
              <strong>$ {{$sale->totalAmount}}</strong>
            </td>
          </tr>
        </table>

      </div>
      <div style="clear: both;"></div>
    </div>


    <!-- Notas y Observaciones -->
    <div style="background-color:rgb(255, 255, 255); padding: 10px; margin-bottom: 10px; border: 1px solid #bce9f5; border-radius: 10px;">
      <h3 style="margin: 0 0 2px 0; font-size: 14px; color: #0a85a0;">OBSERVACIONES</h3>
      <p style="margin: 1px 0 1px 0; font-size: 13px;">Esta venta realizada en el punto de venta (POS) se asimila en todos sus efectos a una factura de venta, conforme al artículo 3 del Decreto 2242 de 2015 y demás normativas aplicables.</p>
    </div>

    <!-- Pie de página -->
    <div style="border: 1px solid  #bce9f5; border-radius: 10px; padding: 2px; font-size: 11px; text-align: center; background-color: #E6F7FC; ">
      <p style="margin: 0.5px 0;">{{$company->slogan}}</p>
      <p style="margin: 0.5px 0;">Factura de venta generada por <strong>{{ env('APP_NAME') }}</strong></p>
    </div>
  </div>

</body>

</html>