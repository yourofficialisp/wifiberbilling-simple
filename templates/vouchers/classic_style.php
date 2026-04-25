<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { border: 3px solid #000; padding: 20px; width: 400px; background: #fff; }
        .voucher-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 15px; }
        .voucher-header h1 { font-size: 28px; text-transform: uppercase; letter-spacing: 2px; }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 16px; }
        .voucher-body .row .label { font-weight: bold; }
        .voucher-body .row .value { font-family: 'Courier New', monospace; letter-spacing: 1px; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #000; }
        .voucher-footer .price { font-size: 32px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
            <p style="font-size: 12px;">WIFI ACCESS VOUCHER</p>
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
                <span class="label">PROFILE:</span>
                <span class="value">{{profile}}</span>
            </div>
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
            <p style="font-size: 10px; margin-top: 5px;">NO. {{num}}</p>
        </div>
    </div>
</body>
</html>
