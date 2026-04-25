<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #2c3e50; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; padding: 25px; width: 360px; border: 2px solid #3498db; position: relative; }
        .voucher::before { content: ''; position: absolute; top: 5px; left: 5px; right: 5px; bottom: 5px; border: 1px dashed #3498db; }
        .voucher-header { text-align: center; border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 22px; font-weight: bold; color: #3498db; text-transform: uppercase; letter-spacing: 2px; }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .voucher-body .row .label { color: #666; }
        .voucher-body .row .value { font-weight: bold; color: #3498db; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #3498db; }
        .voucher-footer .price { font-size: 24px; font-weight: bold; color: #3498db; }
        .barcode { text-align: center; margin: 15px 0; font-family: 'Courier New', monospace; font-size: 18px; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
        </div>
        <div class="voucher-body">
            <div class="row">
                <span class="label">USER:</span>
                <span class="value">{{username}}</span>
            </div>
            <div class="row">
                <span class="label">PASS:</span>
                <span class="value">{{password}}</span>
            </div>
            <div class="row">
                <span class="label">TIME:</span>
                <span class="value">{{validity}}</span>
            </div>
        </div>
        <div class="barcode">{{num}}</div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
        </div>
    </div>
</body>
</html>
