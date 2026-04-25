<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Verdana', sans-serif; background: #e0e0e0; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: #fff; border: 1px solid #ccc; padding: 25px; width: 360px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .voucher-header { background: #0066cc; color: white; padding: 15px; text-align: center; }
        .voucher-header h1 { font-size: 20px; font-weight: bold; }
        .voucher-body { padding: 20px 0; }
        .voucher-body .field { margin-bottom: 12px; }
        .voucher-body .field .label { font-size: 11px; color: #666; text-transform: uppercase; }
        .voucher-body .field .value { font-size: 16px; font-weight: bold; color: #333; }
        .voucher-footer { text-align: center; padding: 15px; background: #f5f5f5; border-top: 1px solid #ccc; }
        .voucher-footer .price { font-size: 20px; font-weight: bold; color: #0066cc; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
        </div>
        <div class="voucher-body">
            <div class="field">
                <div class="label">Username</div>
                <div class="value">{{username}}</div>
            </div>
            <div class="field">
                <div class="label">Password</div>
                <div class="value">{{password}}</div>
            </div>
            <div class="field">
                <div class="label">Validity</div>
                <div class="value">{{validity}}</div>
            </div>
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
        </div>
    </div>
</body>
</html>
