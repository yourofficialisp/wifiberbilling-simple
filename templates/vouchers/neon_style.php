<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial Black', Arial, sans-serif; background: #1a1a1a; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; border-radius: 20px; padding: 30px; width: 380px; box-shadow: 0 20px 60px rgba(238, 90, 36, 0.4); }
        .voucher-header { text-align: center; border-bottom: 2px dashed rgba(255,255,255,0.3); padding-bottom: 20px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 28px; text-transform: uppercase; letter-spacing: 3px; }
        .voucher-body .field { margin-bottom: 15px; }
        .voucher-body .field .label { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; }
        .voucher-body .field .value { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px dashed rgba(255,255,255,0.3); }
        .voucher-footer .price { font-size: 36px; font-weight: bold; }
        .qr-code { width: 80px; height: 80px; background: white; margin: 15px auto; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #ff6b6b; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
            <p style="font-size: 12px; opacity: 0.8;">WIFI ACCESS</p>
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
        <div class="qr-code">{{qrcode}}</div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
        </div>
    </div>
</body>
</html>
