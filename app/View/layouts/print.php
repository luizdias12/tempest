<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Impressão' ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .print-container {
            max-width: 750px;
            margin: 0 auto;
            padding: 20px;
        }

        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-header h1 {
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
        }

        .print-table th {
            background: #eee;
            font-weight: bold;
        }

        .print-table .valor {
            text-align: right;
            white-space: nowrap;
        }

        .print-table tfoot td {
            font-weight: bold;
        }

        .total-proventos {
            color: #1a5276;
        }

        .total-descontos {
            color: #922b21;
        }

        .total-liquido {
            color: #1e8449;
        }

        .total-fgts {
            color: #0e6655;
        }

        .print-resumo {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .print-resumo div {
            border: 1px solid #000;
            padding: 8px 14px;
            font-size: 13px;
        }

        .print-resumo span {
            display: block;
            font-weight: bold;
            font-size: 14px;
            margin-top: 4px;
        }

        .no-print {
            display: block;
            text-align: center;
            margin: 15px 0;
        }

        .no-print button {
            padding: 8px 24px;
            font-size: 13px;
            cursor: pointer;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                font-size: 11px;
            }
        }

        @page {
            size: A4;
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Imprimir / Salvar PDF</button>
    </div>

    <div class="print-container">
        <?= $content ?>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });

        window.addEventListener('afterprint', function () {
            setTimeout(function () { window.close(); }, 200);
        });
    </script>
</body>
</html>
