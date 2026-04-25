<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Comic Sans MS', cursive, sans-serif; background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: white; border-radius: 30px; padding: 30px; width: 380px; box-shadow: 0 20px 50px rgba(255, 154, 158, 0.3); border: 4px dashed #ff9a9e; }
        .voucher-header { text-align: center; margin-bottom: 25px; }
        .voucher-header h1 { font-size: 26px; color: #ff9a9e; text-shadow: 2px 2px 0 #fecfef; }
        .voucher-body .field { margin-bottom: 15px; }
        .voucher-body .field .label { font-size: 12px; color: #ff9a9e; font-weight: bold; }
        .voucher-body .field .value { font-size: 18px; font-weight: bold; color: #333; }
        .voucher-footer { text-align: center; margin-top: 20px; padding: 15px; background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); border-radius: 15px; }
        .voucher-footer .price { font-size: 28px; font-weight: bold; color: white; }
        .emoji { font-size: 40px; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="emoji">🌸</div>
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
        <div class="emoji">🌸</div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
        </div>
    </div>
</body>
</html>
