<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; border-radius: 20px; padding: 30px; width: 380px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); position: relative; }
        .voucher::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #1e3c72, #2a5298, #1e3c72); border-radius: 20px 20px 0 0; }
        .voucher-header { text-align: center; margin-bottom: 25px; }
        .voucher-header h1 { font-size: 22px; font-weight: bold; color: #1e3c72; text-transform: uppercase; letter-spacing: 2px; }
        .voucher-body .field { margin-bottom: 15px; }
        .voucher-body .field .label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .voucher-body .field .value { font-size: 18px; font-weight: bold; color: #1e3c72; }
        .voucher-footer { text-align: center; margin-top: 25px; padding: 20px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 15px; }
        .voucher-footer .price { font-size: 28px; font-weight: bold; color: white; }
        .qr-code { width: 70px; height: 70px; background: #1e3c72; margin: 15px auto; display: flex; align-items: center; justify-content: center; font-size: 9px; color: white; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
            <p style="font-size: 12px; color: #666;">WIFI ACCESS</p>
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
