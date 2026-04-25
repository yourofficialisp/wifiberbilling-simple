<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #2c3e50; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: linear-gradient(145deg, #3498db 0%, #2980b9 100%); color: white; border-radius: 15px; padding: 30px; width: 400px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); position: relative; overflow: hidden; }
        .voucher::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .voucher-content { position: relative; z-index: 1; }
        .voucher-header { text-align: center; margin-bottom: 25px; }
        .voucher-header h1 { font-size: 26px; font-weight: bold; letter-spacing: 2px; }
        .voucher-body .field { margin-bottom: 18px; }
        .voucher-body .field .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .voucher-body .field .value { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        .voucher-footer { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.3); }
        .voucher-footer .price { font-size: 32px; font-weight: bold; }
        .qr-code { width: 70px; height: 70px; background: white; margin: 15px auto; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #3498db; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-content">
            <div class="voucher-header">
                <h1>{{hotspotname}}</h1>
                <p style="font-size: 12px; opacity: 0.9;">WIFI VOUCHER</p>
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
    </div>
</body>
</html>
