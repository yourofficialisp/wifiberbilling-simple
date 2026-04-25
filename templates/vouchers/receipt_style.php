<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; border: 2px solid #333; padding: 30px; width: 400px; position: relative; }
        .voucher::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: repeating-linear-gradient(45deg, #333 0, #333 10px, #666 10px, #666 20px); }
        .voucher-header { text-align: center; border-bottom: 1px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 26px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; border-bottom: 1px dotted #ccc; padding-bottom: 8px; }
        .voucher-body .row .label { font-weight: bold; }
        .voucher-body .row .value { font-family: 'Courier New', monospace; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #333; }
        .voucher-footer .price { font-size: 24px; font-weight: bold; }
        .stamp { position: absolute; bottom: 60px; right: 20px; border: 3px solid #333; padding: 10px 20px; transform: rotate(-15deg); font-size: 12px; font-weight: bold; opacity: 0.5; }
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
        <div class="stamp">PAID</div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
            <p style="font-size: 10px; margin-top: 5px;">NO. {{num}}</p>
        </div>
    </div>
</body>
</html>
