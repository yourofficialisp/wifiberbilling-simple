<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #2d3436; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: linear-gradient(145deg, #00cec9 0%, #0984e3 100%); color: white; padding: 30px; width: 400px; border-radius: 20px; box-shadow: 0 25px 60px rgba(0, 206, 201, 0.4); position: relative; overflow: hidden; }
        .voucher::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: pulse 3s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
        .voucher-content { position: relative; z-index: 1; }
        .voucher-header { text-align: center; margin-bottom: 25px; }
        .voucher-header h1 { font-size: 24px; font-weight: bold; letter-spacing: 2px; }
        .voucher-body .field { margin-bottom: 15px; }
        .voucher-body .field .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .voucher-body .field .value { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        .voucher-footer { text-align: center; margin-top: 25px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px; }
        .voucher-footer .price { font-size: 32px; font-weight: bold; }
        .qr-code { width: 70px; height: 70px; background: white; margin: 15px auto; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #00cec9; border-radius: 10px; }
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
