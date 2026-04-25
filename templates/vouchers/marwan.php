<style>
    .voucher-card {
        width: 320px;
        border: 2px solid #000;
        background: #fff;
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        padding: 0;
        overflow: hidden;
        display: inline-block;
        margin: 5px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 10px;
        border-bottom: 2px solid #000;
    }

    .logo-box {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .logo-img {
        width: 40px;
        height: auto;
    }

    .brand-text {
        font-weight: 900;
        font-size: 14px;
        line-height: 1;
    }

    .price-box {
        text-align: right;
    }

    .price-currency {
        font-weight: bold;
        font-size: 16px;
        vertical-align: top;
    }

    .price-amount {
        font-weight: 900;
        font-size: 38px;
        line-height: 0.8;
    }

    .main-body {
        display: flex;
        padding: 5px 10px;
    }

    .left-section {
        flex: 1.2;
        border-right: 1px solid #000;
        padding-right: 10px;
        text-align: center;
    }

    .right-section {
        flex: 1;
        padding-left: 10px;
        text-align: right;
    }

    .voucher-label {
        font-weight: 800;
        font-size: 16px;
        letter-spacing: 1px;
        margin-bottom: 2px;
    }

    .username-box {
        font-weight: 900;
        font-size: 32px;
        border-bottom: 2px solid #000;
        margin-bottom: 5px;
        padding-bottom: 2px;
    }

    .instructions {
        font-size: 9px;
        font-weight: bold;
        line-height: 1.2;
    }

    .validity-label {
        font-weight: 900;
        font-size: 10px;
        margin-bottom: 2px;
    }

    .limit-info {
        font-size: 10px;
        font-weight: bold;
        line-height: 1.1;
    }

    .qr-container {
        margin-top: 5px;
        display: flex;
        justify-content: flex-end;
    }

    .qr-container>div {
        width: 70px !important;
        height: 70px !important;
    }

    .footer {
        background: #000;
        color: #fff;
        text-align: center;
        padding: 4px;
        font-weight: 900;
        font-size: 11px;
        letter-spacing: 0.5px;
    }
</style>

<div class="voucher-card">
    <div class="header">
        <div class="logo-box">
            <img src="{{logo}}" class="logo-img">
            <div class="brand-text">MARWAN<br><small style="font-size: 8px; letter-spacing: 1px;">HOTSPOT</small></div>
        </div>
        <div class="price-box">
            <span class="price-currency">{{price_small}}</span>
            <span class="price-amount">{{price_big}}</span>
        </div>
    </div>

    <div class="main-body">
        <div class="left-section">
            <div class="voucher-label">VOUCHER</div>
            <div class="username-box">{{username}}</div>
            <div class="instructions">
                Hubungkan ke wifi<br>
                <b>{{hotspotname}}</b><br>
                Buka browser:<br>
                {{dnsname}}
            </div>
        </div>

        <div class="right-section">
            <div class="validity-label">MASA AKTIF : {{validity}}</div>
            <div class="limit-info">
                Durasi:{{timelimit}}<br>
                {{datalimit}}
            </div>
            <div class="qr-container">
                {{qrcode}}
            </div>
        </div>
    </div>

    <div class="footer">
        BERUBAH, BERKARYA, BERDAMPAK
    </div>
</div>