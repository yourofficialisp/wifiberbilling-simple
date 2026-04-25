<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Impact', sans-serif; background: #ff6b6b; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: #fff; padding: 30px; width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative; }
        .voucher::before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 4px solid #ff6b6b; }
        .voucher-header { text-align: center; border-bottom: 3px solid #ff6b6b; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 32px; text-transform: uppercase; letter-spacing: 3px; color: #ff6b6b; }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 16px; }
        .voucher-body .row .label { font-weight: bold; color: #666; }
        .voucher-body .row .value { font-weight: bold; color: #ff6b6b; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 3px solid #ff6b6b; }
        .voucher-footer .price { font-size: 36px; font-weight: bold; color: #ff6b6b; }
        .badge { position: absolute; top: -10px; right: -10px; background: #ff6b6b; color: white; padding: 5px 15px; font-size: 12px; font-weight: bold; transform: rotate(45deg); }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="badge">HOTSPOT</div>
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
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
        </div>
    </div>
</body>
</html>
