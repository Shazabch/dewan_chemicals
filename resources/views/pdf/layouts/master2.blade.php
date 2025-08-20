<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: 8.27in 11.7in;
            margin: .5in;
        }

        * {
            margin: 0;
            padding: 0;
            outline: 0;
        }

        body {
            font-family: "Arial", sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #023047;
            padding: 0.5in;
        }

        .body {
            padding-top: 10px;
        }

        /* Typography */
        .strong {
            font-weight: 700;
        }

        .fw-md {
            font-weight: 500;
        }

        .primary-text {
            color: #219ebc;
        }

        h1,
        .h1 {
            font-family: "Arial", sans-serif;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 67px;
            line-height: 1.2;
            font-weight: 500;
        }

        h2,
        .h2 {
            font-family: "Arial", sans-serif;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 50px;
            line-height: 1.2;
            font-weight: 500;
        }

        h3,
        .h3 {
            font-family: "Arial", sans-serif;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 38px;
            line-height: 1.2;
            font-weight: 500;
        }

        h4,
        .h4 {
            font-family: "Arial", sans-serif;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 28px;
            line-height: 1.2;
            font-weight: 500;
        }

        h5,
        .h5 {
            font-family: "Arial", sans-serif;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 18px;
            line-height: 1.2;
            font-weight: 500;
        }

        h6,
        .h6 {
            font-family: "Arial", sans-serif;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 16px;
            line-height: 1.2;
            font-weight: 500;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* List Style */
        ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /* Utilities */
        .d-block {
            display: block;
        }

        .mt-0 {
            margin-top: 0;
        }

        .m-0 {
            margin: 0;
        }

        .mt-3 {
            margin-top: 16px;
        }

        .mt-4 {
            margin-top: 24px;
        }

        .mb-3 {
            margin-bottom: 16px;
        }

        /* Title */
        .title {
            display: inline-block;
            letter-spacing: 0.05em;
        }

        .page-title {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .subtitle {
            font-size: 14px;
            font-style: italic;
        }

        /* Table Style */
        table {
            /* width: 7.27in; */
            width: 100%;
            caption-side: bottom;
            border-collapse: collapse;
            border: 1px solid #eafbff;
            color: #023047;
            vertical-align: top;
        }

        table td {
            padding: 5px 8px;
        }

        table th {
            padding: 5px 8px;
        }

        table th:last-child {
            text-align: right !important;
        }

        .table>tbody {
            vertical-align: inherit;
            border: 1px solid #eafbff;
        }

        .table>thead {
            vertical-align: bottom;
            background: #97CA9C;
            color: white;
        }

        .table>thead th {
            font-family: "Arial", sans-serif;
            text-align: left;
            font-size: 14px;
            letter-spacing: 0.03em;
            font-weight: 500;
        }

        .table>body tr td {
            font-size: 5px !important;
        }

        .table td:last-child {
            text-align: right;
        }

        .table th:last-child {
            text-align: right;
        }

        .table> :not(:first-child) {
            border-top: 0;
        }

        .table-sm> :not(caption)>*>* {
            padding: 5px;
        }

        .table-bordered> :not(caption)>* {
            border-width: 1px 0;
        }

        .table-bordered> :not(caption)>*>* {
            border-width: 0 1px;
        }

        .table-borderless> :not(caption)>*>* {
            border-bottom-width: 0;
        }

        .table-borderless> :not(:first-child) {
            border-top-width: 0;
        }

        .table-striped>tbody>tr:nth-of-type(even)>* {
            background: #f6f5fd;
        }

        .product-img {
            height: 80px;
            width: 100px;
            border: 1px solid #1b728a;
        }

        /* Logo */
        .logo {
            width: 100%;
            max-width: 200px;
            height: 50px;
            font-size: 24px;
            text-transform: capitalize;
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .info {
            padding-top: 15px;
            padding-bottom: 15px;
            border-top: 1px solid #023047;
            border-bottom: 1px solid #023047;
        }

        .address {
            padding-top: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #023047;
        }

        header {
            padding-top: 15px;
            padding-bottom: 15px;
        }

        footer {
            padding: 20px;
        }

        .align-items-center {
            align-items: center;
        }

        .footer-link {
            text-decoration: none;
            color: #97CA9C;
        }

        .footer-link:hover {
            text-decoration: none;
            color: #97CA9C;
        }

        .list--row {
            overflow: auto
        }

        .list--row::after {
            content: '';
            display: block;
            clear: both;
        }

        .float-left {
            float: left;
        }

        .float-right {
            float: right;
        }

        .d-block {
            display: block;
        }

        .d-inline-block {
            display: inline-block;
        }

        .logo {
            width: 200px;
            height: auto;
        }

        .mb-5px {
            margin-bottom: 5px;
        }

        .mb-15px {
            margin-bottom: 15px;
        }

        .border {
            border: 1px solid #e6e6e6;
        }

        .p-15px {
            padding: 15px;
        }

        .p-5px {
            padding: 5px;
        }

        .clearfix {
            overflow: auto;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .summary-content {
            width: 3.5in;
        }

        .summary-content>div {
            height: 30px;
            padding: 0 10px;
            overflow: hidden;
        }

        .summary-content div p {
            height: 100%;
            transform: translate(0px, 6px);
        }

        .border-bottom {
            border-bottom: 1px solid #e6e6e6;
        }

        .page-title {
            font-size: 16px;
            font-weight: 500;
        }

        .text-small {
            font-size: 12px;
        }

        .text--success {
            color: #28c76f !important;
        }

        .text--danger {
            color: #ea5455 !important;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 50px 60px;
            /* Top/Bottom: 50px, Left/Right: 60px */
            position: relative;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px 6px;
            /* Improved padding */
            text-align: center;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #97ca9c;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .company-logo {
            height: 50px;
        }

        .company-title {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }

        .watermark-logo {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 530px;
            opacity: 0.25;
            transform: translate(-50%, -50%);
            z-index: 0;
        }

        .top-section {
            margin-bottom: 20px;
            z-index: 1;
            position: relative;
        }

        .top-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
        }

        .customer-info {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            background-color: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            margin-right: 20px;
        }

        .customer-date {
            font-size: 14px;
            color: #555;
            background-color: #f0f8ff;
            padding: 8px 12px;
            border-radius: 5px;
            display: inline-block;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }


        .customer-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }


        .header {
            background-color: #97ca9c;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            z-index: 1;
            position: relative;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 5px;
            text-align: center;
        }

        th {
            background-color: #97ca9c;
            color: white;
        }

        .text-success {
            color: green;
        }

        .text-danger {
            color: red;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 40px;
            right: 40px;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            display: flex;
            justify-content: space-between;
        }

        .signature-block {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            flex-wrap: nowrap;
            /* Prevent wrapping to new line */
            gap: 100px;
            /* Optional: adds space between them */
        }

        .signature {
            flex: 1;
            text-align: center;
        }


        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
    <style>
        body {
            font-size: 10px;
            /* Smaller font to fit more content */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Forces column widths to respect constraints */
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            word-wrap: break-word;
            /* Forces long text to wrap */
            font-size: 9px;
        }

        th {
            background-color: #97ca9c;
        }

        tr {
            page-break-inside: avoid;
        }

        .footer .page-number:after {
            content: "Page " counter(page);
        }
    </style>
</head>

<body>

    <!-- Watermark -->
    <div class="watermark-logo">
        <img src="{{ siteLogo('dark') }}" alt="Dewan Chemicals Logo" style="width: 100%;">
    </div>

    <!-- Company Header -->
    <div class="header-bar">
        <img src="{{ siteLogo('dark') }}" alt="Dewan Chemicals" class="company-logo">
        <div class="company-title">Dewan Chemicals - {{$pageTitle}}</div>
    </div>

    @yield('content')


    <table style="width: 100%; margin-top: 50px; font-size: 13px;">
        <tr>
            <td style="text-align: center;border:none;">
                <div style="border-top: 1px solid #000; width: 200px; margin: 0 auto 5px;"></div>
                Authorized By
            </td>
            <td style="text-align: center;border:none;">
                <div style="border-top: 1px solid #000; width: 200px; margin: 0 auto 5px;"></div>
                Customer Signature
            </td>
        </tr>
    </table>


    <!-- Footer -->
    <div class="footer">
        <div>Dewan Chemicals © {{ date('Y') }}</div>
        <!-- <div footer-center page-number>Page {PAGE_NUM} of {PAGE_COUNT}</div> -->
         <div class="footer-center page-number">
            {{-- This div is intentionally empty. The page number is added by CSS. --}}
        </div>
        <div>Created By : {{ auth()->guard('admin')->user()->username }} </div>
    </div>

</body>

</html>