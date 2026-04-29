<style>
    .voucher-thermal {
        width: 100%;
        max-width: 300px;
        font-family: 'Courier New', Courier, monospace;
        margin-bottom: 20px;
        border-bottom: 1px dashed #000;
        padding-bottom: 10px;
    }

    .v-header {
        text-align: center;
        font-weight: bold;
        font-size: 1.2rem;
    }

    .v-body {
        text-align: center;
        margin: 10px 0;
    }

    .v-code {
        font-size: 1.5rem;
        font-weight: bold;
    }

    .v-footer {
        text-align: center;
        font-size: 0.9rem;
    }
</style>

<div class="voucher-thermal">
    <div class="v-header">{{hotspotname}}</div>
    <div class="v-body">
        <div>-----------------</div>
        <div>VOUCHER CODE</div>
        <div class="v-code">{{username}}</div>
        <div>PASS: {{password}}</div>
        <div>-----------------</div>
    </div>
    <div class="v-footer">
        PROFIL: {{profile}}<br>
        PRICE: {{price}}<br>
        VALIDITY PERIOD: {{validity}}<br>
        LOGIN: {{dnsname}}
    </div>
</div>