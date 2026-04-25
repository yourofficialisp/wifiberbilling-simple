<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Georgia', serif; background: #f5f5dc; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: #fff8dc; padding: 30px; width: 400px; border: 3px double #8b4513; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; }
        .voucher::before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 1px solid #8b4513; }
        .voucher-header { text-align: center; border-bottom: 2px solid #8b4513; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 22px; font-weight: bold; color: #8b4513; letter-spacing: 2px; }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .voucher-body .row .label { font-weight: bold; color: #8b4513; }
        .voucher-body .row .value { color: #333; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #8b4513; }
        .voucher-footer .price { font-size: 24px; font-weight: bold; color: #8b4513; }
        .stamp { position: absolute; bottom: 50px; right: 30px; border: 3px solid #8b4513; padding: 10px 15px; transform: rotate(-15deg); font-size: 11px; font-weight: bold; color: #8b4513; opacity: 0.7; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
        </div>
        <div class="voucher-body">
            <div class="row">
                <span class="label">Username:</span>
                <span class="value">{{username}}</span>
            </div>
            <div class="row">
                <span class="label">Password:</span>
                <span class="value">{{password}}</span>
            </div>
            <div class="row">
                <span class="label">Validity:</span>
                <span class="value">{{validity}}</span>
            </div>
            <div class="row">
                <span class="label">Price:</span>
                <span class="value">{{price}}</span>
            </div>
        </div>
        <div class="stamp">PAID</div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
        </div>
    </div>
</body>
</html>
