<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Voucher</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 10mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(7, auto);
            gap: 5mm;
            margin-bottom: 5mm;
        }

        .voucher-item {
            border: 1px solid #ddd;
            padding: 3mm;
            background: white;
            page-break-inside: avoid;
        }

        .voucher-item .voucher-content {
            transform: scale(0.7);
            transform-origin: top left;
        }

        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }

            body {
                background: white;
                padding: 0;
            }

            .print-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: none;
            }

            .voucher-item {
                border: 1px solid #000;
                padding: 3mm;
            }

            .voucher-item:nth-child(21n) {
                page-break-after: always;
            }
        }

        .controls {
            margin-bottom: 20px;
            text-align: center;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .controls button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-print {
            background: #00c6ff;
            color: white;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn-print:hover {
            background: #00a8cc;
        }

        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="controls">
        <button class="btn-back" onclick="window.location.href='../admin/hotspot-user.php';">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <button class="btn-print" onclick="window.print();">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    <div class="print-container" id="voucherContainer">
        <div class="voucher-grid" id="voucherGrid">
            <!-- Vouchers will be loaded here -->
        </div>
    </div>

    <script>
        // Get voucher data and template from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const vouchers = urlParams.get('vouchers') ? JSON.parse(urlParams.get('vouchers')) : [];
        const template = urlParams.get('template') || 'mikhmon_style.php';
        
        const voucherGrid = document.getElementById('voucherGrid');
        
        if (vouchers.length === 0) {
            document.getElementById('voucherContainer').innerHTML = '<p style="text-align: center; padding: 50px;">No vouchers to print. <a href="../admin/hotspot-user.php">Back</a></p>';
        } else {
            // Fetch template content
            fetch('../templates/vouchers/' + template)
                .then(response => response.text())
                .then(templateContent => {
                    vouchers.forEach((voucher, index) => {
                        const voucherItem = document.createElement('div');
                        voucherItem.className = 'voucher-item';
                        
                        // Replace placeholders in template
                        let voucherHtml = templateContent;
                        voucherHtml = voucherHtml.replace(/\{\{username\}\}/g, voucher.username);
                        voucherHtml = voucherHtml.replace(/\{\{password\}\}/g, voucher.password);
                        voucherHtml = voucherHtml.replace(/\{\{profile\}\}/g, voucher.profile);
                        voucherHtml = voucherHtml.replace(/\{\{price\}\}/g, voucher.price);
                        voucherHtml = voucherHtml.replace(/\{\{validity\}\}/g, voucher.validity);
                        voucherHtml = voucherHtml.replace(/\{\{hotspotname\}\}/g, voucher.hotspotname || 'Gembok WiFi');
                        voucherHtml = voucherHtml.replace(/\{\{dnsname\}\}/g, voucher.dnsname || 'hotspot.net');
                        voucherHtml = voucherHtml.replace(/\{\{num\}\}/g, index + 1);
                        
                        // Add dummy QR code if needed
                        if (voucherHtml.includes('{{qrcode}}')) {
                            voucherHtml = voucherHtml.replace(/\{\{qrcode\}\}/g, '<div style="width:30px;height:30px;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-size:6px;border:1px solid #fff;">QR</div>');
                        }
                        
                        voucherItem.innerHTML = `<div class="voucher-content">${voucherHtml}</div>`;
                        voucherGrid.appendChild(voucherItem);
                    });
                })
                .catch(error => {
                    console.error('Failed to load template:', error);
                    document.getElementById('voucherContainer').innerHTML = '<p style="text-align: center; padding: 50px;">Failed to load template. <a href="../admin/hotspot-user.php">Back</a></p>';
                });
        }
    </script>
</body>
</html>
