<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Verdana', sans-serif; background: #4a4a4a; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: #fff; padding: 25px; width: 360px; border: 1px solid #333; position: relative; }
        .voucher::before { content: 'VOUCHER'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(0,0,0,0.03); font-weight: bold; pointer-events: none; }
        .voucher-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
        .voucher-body .row .label { font-weight: bold; }
        .voucher-body .row .value { font-family: 'Courier New', monospace; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #333; }
        .voucher-footer .price { font-size: 24px; font-weight: bold; }
        .barcode { text-align: center; margin: 15px 0; font-family: 'Courier New', monospace; font-size: 20px; letter-spacing: 3px; }
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
