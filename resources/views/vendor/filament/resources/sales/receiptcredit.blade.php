<!DOCTYPE html>
<html lang="es">

<head>
  <!-- Meta Tags -->
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="Laralink">
  <title>Recibo de crédito {{$credit->id}}</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap');

    *,
    ::after,
    ::before {
      box-sizing: border-box;
    }

    body {
      color: #000;
      font-size: 12px;
      font-weight: 400;
      line-height: 1.4em;
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background-color: #f5f6fa;
    }

    .invoice-data {
      display: flex;
      align-items: center;
    }

    .tm_pos_invoice_wrap {
      max-width: 340px;
      margin: auto;
      margin-top: 0px;
      padding: 30px 20px;
      background-color: #fff;
    }

    .tm_pos_company_logo {
      display: flex;
      justify-content: center;
      margin-bottom: 7px;
    }

    .image-wrapper {
      width: 100%;
      max-width: 200px;
      position: relative;
      padding-bottom: 25%;
      overflow: hidden;
    }

    .tm_pos_company_logo img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: contain;
      vertical-align: middle;
      border: 0;
      background-color: white;
    }

    .tm_pos_invoice_top {
      text-align: center;
      margin-bottom: 18px;
    }

    .tm_pos_invoice_heading {
      display: flex;
      justify-content: center;
      position: relative;
      text-transform: uppercase;
      font-size: 12px;
      font-weight: 500;
      margin: 10px 0;
    }

    .tm_pos_invoice_heading:before {
      content: '';
      position: absolute;
      height: 0;
      width: 100%;
      left: 0;
      top: 46%;
      border-top: 1px dashed #666;
    }

    .tm_pos_invoice_heading span {
      display: inline-flex;
      padding: 0 5px;
      background-color: #fff;
      z-index: 1;
      font-weight: 500;
      position: relative;
    }

    .tm_list.tm_style1 {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-wrap: wrap;
    }

    .tm_list.tm_style1 li {
      display: flex;
      width: 50%;
      font-size: 12px;
      line-height: 1.2em;
      margin-bottom: 7px;
    }

    .text-right {
      text-align: right;
      justify-content: flex-end;
    }

    .tm_list_title {
      color: #111;
      margin-right: 4px;
      font-weight: 500;
    }

    .tm_invoice_seperator {
      width: 150px;
      border-top: 1px dashed #666;
      margin: 9px 0;
      margin-left: auto;
    }

    .tm_pos_invoice_table {
      width: 100%;
      margin-top: 10px;
      line-height: 0.7em;
    }

    .tm_pos_invoice_table thead th {
      font-weight: 500;
      color: #111;
      text-align: left;
      padding: 8px 0px;
      border-top: 1px dashed #666;
      border-bottom: 1px dashed #666;
    }

    .tm_pos_invoice_table td {
      padding: 4px;
    }

    .tm_pos_invoice_table tbody tr:first-child td {
      padding-top: 10px;
    }

    .tm_pos_invoice_table tbody tr:last-child td {
      padding-bottom: 10px;
      border-bottom: 1px dashed #666;
    }

    .tm_pos_invoice_table th:last-child,
    .tm_pos_invoice_table td:last-child {
      text-align: right;
      padding-right: 0;
    }

    .tm_pos_invoice_table th:first-child,
    .tm_pos_invoice_table td:first-child {
      padding-left: 0;
    }

    .tm_pos_invoice_table tr {
      vertical-align: baseline;
    }

    .tm_bill_list {
      list-style: none;
      margin: 0;
      padding: 12px 0;
      border-bottom: 1px dashed #666;
    }

    .tm_bill_list_in {
      display: flex;
      text-align: right;
      justify-content: flex-end;
      padding: 0px 0;
    }

    .tm_bill_title {
      padding-right: 20px;
    }

    .tm_bill_value {
      width: 90px;
    }

    .tm_bill_value.tm_bill_focus,
    .tm_bill_title.tm_bill_focus {
      font-weight: 500;
      color: #111;
    }

    .tm_pos_invoice_footer {
      text-align: center;
      margin-top: 20px;
    }

    .tm_pos_sample_text {
      text-align: center;
      padding: 12px 0;
      border-bottom: 1px dashed #666;
      line-height: 1.6em;
      color: #9c9c9c;
    }

    .tm_pos_company_name {
      font-weight: 500;
      color: #111;
      font-size: 13px;
      line-height: 1.4em;
    }

    /* Start Receipt Section */
    .tm_container {
      max-width: 480px;
      padding: 30px 15px;
      margin-left: auto;
      margin-right: auto;
      position: relative;
    }

    @media print {
      @page {
        size: 80mm 300mm;
        margin: 0;
      }

      body {
        width: 80mm;
        margin: 0;
        padding: 0;
      }

      .tm_container,
      .tm_pos_invoice_table,
      .tm_bill_list,
      .tm_pos_invoice_wrap {
        width: 100%;
        font-size: 8pt;
        overflow: hidden;
        padding: 5px;
      }

      * {
        page-break-inside: avoid;
        page-break-before: avoid;
        page-break-after: avoid;
      }

      .tm_invoice_btns {
        display: none;
      }

      .tm_hide_print {
        display: none !important;
      }
    }


    @media (min-width: 575px) {
      .tm_invoice_btns {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-orient: vertical;
        -webkit-box-direction: normal;
        -ms-flex-direction: column;
        flex-direction: column;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        justify-content: center;
        margin-top: 0px;
        margin-left: 0;
        position: absolute;
        right: 0px;
        top: 30px;
        -webkit-box-shadow: -2px 0 24px -2px rgba(43, 55, 72, 0.05);
        box-shadow: -2px 0 24px -2px rgba(43, 55, 72, 0.05);
        border: 3px solid #fff;
        border-radius: 6px;
        background-color: #fff;
      }

      .tm_invoice_btn {
        display: -webkit-inline-box;
        display: -ms-inline-flexbox;
        display: inline-flex;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        border: none;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
        background-color: transparent;
        position: relative;
      }

      .tm_invoice_btn svg {
        width: 24px;
      }

      .tm_invoice_btn .tm_btn_icon {
        padding: 0;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        height: 42px;
        width: 42px;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        justify-content: center;
      }

      .tm_invoice_btn .tm_btn_text {
        position: absolute;
        left: 100%;
        background-color: #111;
        color: #fff;
        padding: 3px 12px;
        display: inline-block;
        margin-left: 10px;
        border-radius: 5px;
        top: 50%;
        -webkit-transform: translateY(-50%);
        transform: translateY(-50%);
        font-weight: 500;
        min-height: 28px;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
      }

      .tm_invoice_btn .tm_btn_text:before {
        content: "";
        height: 10px;
        width: 10px;
        position: absolute;
        background-color: #111;
        -webkit-transform: rotate(45deg);
        transform: rotate(45deg);
        left: -3px;
        top: 50%;
        margin-top: -6px;
        border-radius: 2px;
      }

      .tm_invoice_btn:hover .tm_btn_text {
        opacity: 1;
        visibility: visible;
      }

      .tm_invoice_btn:not(:last-child) {
        margin-bottom: 3px;
      }

      .tm_invoice_btn.tm_color1 {
        background-color: rgba(0, 122, 255, 0.1);
        color: #007aff;
        border-radius: 5px 5px 0 0;
      }

      .tm_invoice_btn.tm_color1:hover {
        background-color: rgba(0, 122, 255, 0.2);
      }

      .tm_invoice_btn.tm_color2 {
        background-color: rgba(52, 199, 89, 0.1);
        color: #34c759;
        border-radius: 0 0 5px 5px;
      }

      .tm_invoice_btn.tm_color2:hover {
        background-color: rgba(52, 199, 89, 0.2);
      }
    }

    @media (max-width: 574px) {
      .tm_invoice_btns {
        display: -webkit-inline-box;
        display: -ms-inline-flexbox;
        display: inline-flex;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        justify-content: center;
        margin-top: 0px;
        margin-top: 20px;
        -webkit-box-shadow: -2px 0 24px -2px rgba(43, 55, 72, 0.05);
        box-shadow: -2px 0 24px -2px rgba(43, 55, 72, 0.05);
        border: 3px solid #fff;
        border-radius: 6px;
        background-color: #fff;
        position: relative;
        left: 50%;
        -webkit-transform: translateX(-50%);
        transform: translateX(-50%);
      }

      .tm_invoice_btn {
        display: -webkit-inline-box;
        display: -ms-inline-flexbox;
        display: inline-flex;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        border: none;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
        background-color: transparent;
        position: relative;
        border-radius: 5px;
        padding: 6px 15px;
        text-decoration: none;
      }

      .tm_invoice_btn svg {
        width: 24px;
      }

      .tm_invoice_btn .tm_btn_icon {
        padding: 0;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        justify-content: center;
        margin-right: 8px;
      }

      .tm_invoice_btn:not(:last-child) {
        margin-right: 3px;
      }

      .tm_invoice_btn.tm_color1 {
        background-color: rgba(0, 122, 255, 0.1);
        color: #007aff;
      }

      .tm_invoice_btn.tm_color1:hover {
        background-color: rgba(0, 122, 255, 0.2);
      }

      .tm_invoice_btn.tm_color2 {
        background-color: rgba(52, 199, 89, 0.1);
        color: #34c759;
      }

      .tm_invoice_btn.tm_color2:hover {
        background-color: rgba(52, 199, 89, 0.2);
      }
    }

    /* End Receipt Section */
  </style>
</head>

<body>
  <div class="tm_container">
    <div class="tm_pos_invoice_wrap" id="tm_download_section">
      <div class="tm_pos_invoice_top">
        @if($company->logo)
        <div class="tm_pos_company_logo">
          <div class="image-wrapper">
            <img src="{{$company->logo}}" alt="Logo" />
          </div>
        </div>
        @endif


        <div class="tm_pos_company_name">{{$company->name}}</div>
        <div class="tm_pos_company_address">{{$company->legal_representative}}</div>
        <div class="tm_pos_company_address">{{$company->document_type}}: {{$company->document_number}}</div>
        <div class="tm_pos_company_address">{{$company->address}}</div>
        <div class="tm_pos_company_mobile">{{$company->cellphone}}</div>
        <div class="tm_pos_company_mobile">{{$company->email}}</div>
        <div class="tm_pos_company_mobile">Responsable de IVA persona naltural</div>
      </div>

      <div class="tm_pos_invoice_body">
        <div class="tm_pos_invoice_heading"><span>Recibo de crédito {{$credit->id}}</span></div>
        <!-- Customer data -->
        <div class="invoice-data">
          <div class="tm_list_title">Cliente:</div>
          <div class="tm_list_desc">{{$customer->name}}</div>
        </div>
        <div class="invoice-data">
          <div class="tm_list_title">Documento:</div>
          <div class="tm_list_desc">{{$customer->documentType}} {{$customer->documentNumber}}</div>
        </div>

        <div class="invoice-data">
          <div class="tm_list_title">Celular:</div>
          <div class="tm_list_desc">{{$customer->cellphone}}</div>
        </div>

        <div class="invoice-data">
          <div class="tm_list_title">Correo:</div>
          <div class="tm_list_desc">{{$customer->email}}</div>
        </div>

        <div class="tm_pos_invoice_heading"></div>
        <!-- Credit data -->
        <div class="invoice-data">
          <div class="tm_list_title">ID Crédito:</div>
          <div class="tm_list_desc">{{$credit->id }}</div>
        </div>
        <div class="invoice-data">
          <div class="tm_list_title">Factura:</div>
          <div class="tm_list_desc">{{$resolution->prefix}}-{{ $sale->invoiceNumber }}</div>
        </div>
        <div class="invoice-data">
          <div class="tm_list_title">Fecha:</div>
          <div class="tm_list_desc">{{$credit->creditDate}}</div>
        </div>
        <div class="invoice-data">
          <div class="tm_list_title">Total crédito:</div>
          <div class="tm_list_desc">$ {{$credit->totalCredit}}</div>
        </div>
        <div class="invoice-data">
          <div class="tm_list_title">Total pagado:</div>
          <div class="tm_list_desc">$ {{$credit->totalPaid}}</div>
        </div>
        <div class="invoice-data">
          <div class="tm_list_title">Total pendiente:</div>
          <div class="tm_list_desc">$ {{$credit->totalPending}}</div>
        </div>
        <div class="invoice-data">
          <div class="tm_list_title">Estado:</div>
          <div class="tm_list_desc">{{$credit->status}}</div>
        </div>
        <div class="tm_pos_invoice_heading"><span>Historial de pagos</span>
        </div>
        <table class="tm_pos_invoice_table">
          <thead>
            <tr>
              <th>#</th>
              <th>Método</th>
              <th>Cantidad</th>
              <!-- <th>Referencia</th> -->
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            @foreach($payments as $index => $payment)
            <tr>
              <td>{{ $index + 1 }}.</td>
              <td>{{ $payment->paymentMethod }}</td>
              <td>${{ $payment->amount }}</td>
              <!-- <td>{{ $payment->reference }}</td> -->
              <td>{{ $payment->paymentDate }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="tm_bill_list">
          <div class="tm_bill_list_in">
            <div class="tm_bill_title">Método de pago: </div>
            <div class="tm_bill_value">{{$lastPayment->paymentMethod}}</div>
          </div>
          <div class="tm_bill_list_in">
            <div class="tm_bill_title">Referencia:</div>
            <div class="tm_bill_value">{{$lastPayment->reference}}</div>
          </div>
          <div class="tm_bill_list_in">
            <div class="tm_bill_title">Fecha:</div>
            <div class="tm_bill_value">{{$lastPayment->paymentDate}}</div>
          </div>
          <div class="tm_bill_list_in">
            <div class="tm_bill_title tm_bill_focus">Total pagado:</div>
            <div class="tm_bill_value tm_bill_focus">$ {{$lastPayment->amount}}</div>
          </div>

        </div>
        <div class="tm_pos_invoice_footer">Software: SOPHIA POS <br> Fabricante: JHON ARLEM ANDRADE</div>
        <div class="tm_pos_invoice_heading"></div>
        <div class="tm_pos_invoice_footer">¡Gracias por su pago!</div>
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

</body>

</html>