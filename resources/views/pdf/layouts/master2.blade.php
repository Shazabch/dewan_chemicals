<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
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
            font-size: 10px; /* Smaller font to fit more content */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Forces column widths to respect constraints */
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            word-wrap: break-word; /* Forces long text to wrap */
            font-size: 9px;
        }
        th {
            background-color: #97ca9c;
        }
        tr {
            page-break-inside: avoid;
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
        <div>Page {PAGE_NUM} of {PAGE_COUNT}</div>
        <div>Created By : {{ auth()->guard('admin')->user()->username }} </div>
    </div>

</body>

</html>