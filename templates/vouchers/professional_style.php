<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Georgia', serif; background: linear-gradient(45deg, #1a1a2e 0%, #16213e 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; width: 400px; }
        .voucher-header { padding: 30px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .voucher-header h1 { font-size: 26px; font-weight: 300; letter-spacing: 2px; margin-bottom: 5px; }
        .voucher-header p { font-size: 12px; opacity: 0.8; letter-spacing: 3px; }
        .voucher-body { padding: 30px; }
        .voucher-body .field { margin-bottom: 20px; }
        .voucher-body .field .label { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.7; margin-bottom: 8px; }
        .voucher-body .field .value { font-size: 20px; font-weight: 300; letter-spacing: 1px; }
        .voucher-footer { text-align: center; padding: 20px; background: rgba(0,0,0,0.1); }
        .voucher-footer .price { font-size: 32px; font-weight: 300; }
        .voucher-footer .validity { font-size: 11px; opacity: 0.7; margin-top: 5px; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
            <p>WIFI ACCESS</p>
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
            <div class="field">
                <div class="label">Profileeeeeeeeeeee</div>
                <div class="value">{{profile}}</div>
            </div>
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
            <div class="validity">VALIDITY: {{validity}}</div>
        </div>
    </div>
</body>
</html>
