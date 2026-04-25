<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; width: 350px; }
        .voucher-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
        .voucher-header h2 { font-size: 24px; margin-bottom: 5px; }
        .voucher-header p { font-size: 14px; opacity: 0.9; }
        .voucher-body { padding: 20px; }
        .voucher-body .field { margin-bottom: 15px; }
        .voucher-body .field label { display: block; font-size: 12px; color: #666; margin-bottom: 5px; }
        .voucher-body .field .value { font-size: 18px; font-weight: bold; color: #333; }
        .voucher-footer { background: #f8f9fa; padding: 15px; text-align: center; }
        .voucher-footer .price { font-size: 24px; font-weight: bold; color: #667eea; }
        .voucher-footer .validity { font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h2>{{hotspotname}}</h2>
            <p>WIFI VOUCHER</p>
        </div>
        <div class="voucher-body">
            <div class="field">
                <label>Username</label>
                <div class="value">{{username}}</div>
            </div>
            <div class="field">
                <label>Password</label>
                <div class="value">{{password}}</div>
            </div>
            <div class="field">
                <label>Validity</label>
                <div class="value">{{validity}}</div>
            </div>
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
            <div class="validity">Berlaku: {{validity}}</div>
        </div>
    </div>
</body>
</html>
