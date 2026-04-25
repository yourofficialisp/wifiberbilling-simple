<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; border-radius: 20px; padding: 30px; width: 380px; box-shadow: 0 25px 60px rgba(238, 82, 83, 0.4); position: relative; }
        .voucher::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #ff6b6b, #ee5a24); border-radius: 20px 20px 0 0; }
        .voucher-header { text-align: center; margin-bottom: 25px; }
        .voucher-header h1 { font-size: 22px; font-weight: bold; background: linear-gradient(135deg, #ff7675 0%, #d63031 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .voucher-body .field { margin-bottom: 15px; }
        .voucher-body .field .label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .voucher-body .field .value { font-size: 18px; font-weight: bold; color: #333; }
        .voucher-footer { text-align: center; margin-top: 25px; padding: 20px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); border-radius: 15px; }
        .voucher-footer .price { font-size: 28px; font-weight: bold; color: white; }
        .qr-code { width: 70px; height: 70px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); margin: 15px auto; display: flex; align-items: center; justify-content: center; font-size: 9px; color: white; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
            <p style="font-size: 12px; color: #666;">WIFI VOUCHER</p>
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
