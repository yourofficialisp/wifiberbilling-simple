<style>
    .voucher-small {
        width: 150px;
        border: 1px solid #ccc;
        padding: 5px;
        font-family: Arial, sans-serif;
        font-size: 0.8rem;
        margin: 2px;
        display: inline-block;
    }

    .s-bold {
        font-weight: bold;
    }
</style>

<div class="voucher-small">
    <div class="s-bold">{{hotspotname}}</div>
    <div class="s-bold">Code: {{username}}</div>
    <div>Pass: {{password}}</div>
    <div>{{validity}} / {{price}}</div>
</div>