<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #e8e8e8; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; border: 2px dashed #333; padding: 20px; width: 380px; }
        .voucher-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .voucher-body { }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .voucher-body .row .label { font-weight: bold; }
        .voucher-body .row .value { font-family: 'Courier New', monospace; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #333; }
        .voucher-footer .price { font-size: 28px; font-weight: bold; }
        .voucher-footer .validity { font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
        </div>
        <div class="voucher-body">
            <div class="row">
                <span class="label">USERNAME:</span>
                <span class="value">{{username}}</span>
            </div>
            <div class="row">
                <span class="label">PASSWORD:</span>
                <span class="value">{{password}}</span>
            </div>
            <div class="row">
                <span class="label">DURATION:</span>
                <span class="value">{{validity}}</span>
            </div>
            <div class="row">
                <span class="label">PRICE:</span>
                <span class="value">{{price}}</span>
            </div>
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
            <div class="validity">NO. {{num}}</div>
        </div>
    </div>
</body>
</html>
