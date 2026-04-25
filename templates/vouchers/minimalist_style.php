<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f0f0f0; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; border: 1px solid #ddd; width: 360px; }
        .voucher-header { background: #000; color: white; padding: 15px; text-align: center; }
        .voucher-header h1 { font-size: 18px; font-weight: 300; letter-spacing: 3px; }
        .voucher-body { padding: 20px; }
        .voucher-body .field { margin-bottom: 12px; }
        .voucher-body .field .label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #999; }
        .voucher-body .field .value { font-size: 16px; font-weight: 500; color: #000; }
        .voucher-footer { text-align: center; padding: 15px; border-top: 1px solid #eee; }
        .voucher-footer .price { font-size: 20px; font-weight: 300; }
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
