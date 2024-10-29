<!DOCTYPE html>
<html class="no-js" lang="es">

<head>
  <!-- Meta Tags -->
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Laralink">
  <!-- Site Title -->
  <title>Factura de venta {{$resolution->prefix}}-{{ $sale->invoiceNumber }}</title>
  <link rel="stylesheet" href="{{ asset('css/invoice/style.css') }}">
</head>

<body>
  <div class="tm_container">
    <div class="tm_invoice_wrap">
      <div class="tm_invoice tm_style2" id="tm_download_section">
        <div class="tm_invoice_in">
          <div class="tm_invoice_content">
            <div class="tm_invoice_head tm_mb30">
              <div class="tm_invoice_left">
                @if($company->logo)
                <div class="image-wrapper"><img src="{{$company->logo}}" alt="Logo"></div>
                @endif
              </div>
              <div class="tm_invoice_right tm_text_right">
                <b class="tm_f30 tm_medium tm_primary_color">FACTURA</b>
                <p class="tm_m0">Número {{$resolution->prefix}}-{{ $sale->invoiceNumber }}</p>
              </div>
            </div>
            <div class="tm_invoice_info tm_mb25">
              <div class="tm_invoice_info_left">
                <p class="tm_mb17">
                  <b class="tm_f16 tm_primary_color">{{$company->name}}</b> <br>
                  {{$company->legal_representative}} <br>{{$company->document_type}}: {{$company->document_number}} <br>
                  {{$company->address}} <br>
                  {{$company->cellphone}} <br>
                  {{$company->email}}
                </p>
              </div>
              <div class="tm_invoice_info_right">
                <div class="tm_grid_row tm_col_3 tm_col_2_sm tm_invoice_info_in tm_gray_bg tm_round_border">
                  <div>
                    <span>Resolución DIAN:</span> <br>
                    <b class="tm_primary_color">{{$resolution->number}}</b>
                  </div>
                  <div>
                    <span>Autorizada:</span> <br>
                    <b class="tm_primary_color">{{$resolution->startDate}}</b>
                  </div>
                  <div>
                    <span>Vigencia:</span> <br>
                    <b class="tm_primary_color">{{$resolution->validMonths}} meses</b>
                  </div>
                  <div>
                    <span>Numeración:</span> <br>
                    <b class="tm_primary_color">{{$resolution->startInvoice}} al {{$resolution->endInvoice}}</b>
                  </div>
                  <div>
                    <span>Factura POS N°:</span> <br>
                    <b class="tm_primary_color">{{number_format($sale->invoiceNumber, 0, ',', '.')}}</b>
                  </div>
                  <div>
                    <span>Responsable IVA:</span> <br>
                    <b class="tm_primary_color">Si</b>
                  </div>
                </div>
              </div>
            </div>
            <div class="tm_grid_row tm_col_2 tm_invoice_info_in tm_round_border tm_mb30">
              <div class="tm_border_right tm_border_none_sm">
                <b class="tm_primary_color">Información del cliente:</b>
                <p class="tm_m0">Nombre: {{$customer->name}} <br> Documento: {{$customer->documentType}} {{$customer->documentNumber}} <br> Celular: {{$customer->cellphone}} <br> Correo: {{$customer->email}} </p>
              </div>
              <div>
                <b class="tm_primary_color">Información de la factura:</b>
                <p class="tm_m0">Factura: {{$resolution->prefix}}-{{ $sale->invoiceNumber }} <br> Fecha: {{$sale->saleDate}} <br> Cajero: {{$sale->user}} <br> Caja: {{$sale->box}} </p>
              </div>
            </div>
            <div class="tm_table tm_style1">
              <div class="tm_round_border">
                <div class="tm_table_responsive">
                  <table>
                    <thead>
                      <tr>
                        <th class="tm_width_6 tm_semi_bold tm_primary_color">Producto</th>
                        <th class="tm_width_2 tm_semi_bold tm_primary_color">Precio</th>
                        <th class="tm_width_2 tm_semi_bold tm_primary_color">Cantidad</th>
                        <th class="tm_width_2 tm_semi_bold tm_primary_color">IVA</th>
                        <th class="tm_width_2 tm_semi_bold tm_primary_color tm_text_right">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($items as $index => $item)
                      <tr>
                        <td class="tm_width_6">{{ $item->product }}</td>
                        <td class="tm_width_2">${{ $item->price }}</td>
                        <td class="tm_width_2">{{ $item->quantity }}</td>
                        <td class="tm_width_2">{{ $item->iva }}%</td>
                        <td class="tm_width_2 tm_text_right">${{ $item->total }}</td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="tm_invoice_footer tm_mb15">
                <div class="tm_left_footer">
                  <p class="tm_mb2"><b class="tm_primary_color">Información del pago:</b></p>
                  <p class="tm_m0">Método de pago: {{$sale->paymentMethod}} <br>Recibido: $ {{$sale->received}} <br> Cambio: $ {{$sale->exchange}} <br> Pendiente: $ {{$sale->totalPending}} <br> Estado: {{$sale->status}} </p>
                </div>
                <div class="tm_right_footer">
                  <table class="tm_mb15">
                    <tbody>
                      <tr>
                        <td class="tm_width_3 tm_primary_color tm_border_none tm_bold">Subtotal</td>
                        <td class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_bold">$ {{$sale->subtotal}}</td>
                      </tr>
                      <tr>
                        <td class="tm_width_3 tm_primary_color tm_border_none tm_pt0">IVA</td>
                        <td class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_pt0">$ {{$sale->totalIva}}</td>
                      </tr>
                      <tr>
                        <td class="tm_width_3 tm_danger_color tm_border_none tm_pt0">Descuento</td>
                        <td class="tm_width_3 tm_danger_color tm_text_right tm_border_none tm_pt0">-$ {{$sale->totalDiscount}}</td>
                      </tr>
                      <tr>
                        <td class="tm_width_3 tm_border_top_0 tm_bold tm_f15 tm_primary_color tm_gray_bg tm_radius_6_0_0_6">Total a pagar </td>
                        <td class="tm_width_3 tm_border_top_0 tm_bold tm_f15 tm_primary_color tm_text_right tm_gray_bg tm_radius_0_6_6_0">$ {{$sale->totalAmount}}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="tm_note tm_text_center tm_font_style_normal">
              <hr class="tm_mb12">
              <p class="tm_mb2"><b class="tm_primary_color"></b></p>
              <p class="tm_m0">{{$company->slogan}} <br> Software SOPHIA POS</p>
            </div>
          </div>
        </div>
      </div>
      <div class="tm_invoice_btns tm_hide_print">
        <a href="javascript:window.print()" class="tm_invoice_btn tm_color1">
          <span class="tm_btn_icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512">
              <path d="M384 368h24a40.12 40.12 0 0040-40V168a40.12 40.12 0 00-40-40H104a40.12 40.12 0 00-40 40v160a40.12 40.12 0 0040 40h24" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" />
              <rect x="128" y="240" width="256" height="208" rx="24.32" ry="24.32" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" />
              <path d="M384 128v-24a40.12 40.12 0 00-40-40H168a40.12 40.12 0 00-40 40v24" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" />
              <circle cx="392" cy="184" r="24" fill='currentColor' />
            </svg>
          </span>
          <span class="tm_btn_text">Imprimir</span>
        </a>
      </div>
    </div>
  </div>
</body>