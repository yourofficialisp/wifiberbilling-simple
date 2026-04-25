<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); overflow: hidden; width: 380px; }
        .voucher-header { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); color: white; padding: 25px; text-align: center; }
        .voucher-header h1 { font-size: 22px; font-weight: 600; margin-bottom: 5px; }
        .voucher-header p { font-size: 12px; opacity: 0.9; letter-spacing: 1px; }
        .voucher-body { padding: 25px; }
        .voucher-body .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .voucher-body .info-item { background: #f8f9fa; padding: 12px; border-radius: 8px; }
        .voucher-body .info-item .label { font-size: 11px; color: #666; margin-bottom: 5px; }
        .voucher-body .info-item .value { font-size: 16px; font-weight: 600; color: #333; }
        .voucher-footer { background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee; }
        .voucher-footer .price { font-size: 28px; font-weight: 700; color: #0072ff; }
        .voucher-footer .validity { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
            <p>WIFI VOUCHER</p>
        </div>
        <div class="voucher-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Username</div>
                    <div class="value">{{username}}</div>
                </div>
                <div class="info-item">
                    <div class="label">Password</div>
                    <div class="value">{{password}}</div>
                </div>
            </div>
            <div class="info-item" style="margin-bottom: 10px;">
                <div class="label">Validity</div>
                <div class="value">{{validity}}</div>
            </div>
            <div class="info-item">
                <div class="label">Profileeeeeeeeeeee</div>
                <div class="value">{{profile}}</div>
            </div>
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
            <div class="validity">Berlaku: {{validity}}</div>
        </div>
    </div>
</body>
</html>
