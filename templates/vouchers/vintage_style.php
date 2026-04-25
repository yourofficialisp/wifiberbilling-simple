<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Georgia', serif; background: #8B4513; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: #F5DEB3; border: 4px double #8B4513; padding: 30px; width: 420px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); position: relative; }
        .voucher::before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 2px dashed #8B4513; }
        .voucher-header { text-align: center; border-bottom: 2px solid #8B4513; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 24px; font-weight: bold; color: #8B4513; letter-spacing: 3px; }
        .voucher-body .field { margin-bottom: 15px; }
        .voucher-body .field .label { font-size: 12px; color: #8B4513; font-style: italic; }
        .voucher-body .field .value { font-size: 18px; font-weight: bold; color: #333; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #8B4513; }
        .voucher-footer .price { font-size: 26px; font-weight: bold; color: #8B4513; }
        .corner-decoration { position: absolute; width: 30px; height: 30px; border: 2px solid #8B4513; }
        .corner-decoration.top-left { top: 15px; left: 15px; border-right: none; border-bottom: none; }
        .corner-decoration.top-right { top: 15px; right: 15px; border-left: none; border-bottom: none; }
        .corner-decoration.bottom-left { bottom: 15px; left: 15px; border-right: none; border-top: none; }
        .corner-decoration.bottom-right { bottom: 15px; right: 15px; border-left: none; border-top: none; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="corner-decoration top-left"></div>
        <div class="corner-decoration top-right"></div>
        <div class="corner-decoration bottom-left"></div>
        <div class="corner-decoration bottom-right"></div>
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
