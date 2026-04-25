<style>
    .qrcode {
        height: 60px;
        width: 60px;
    }
</style>
<table
    style="display: inline-block;border-collapse: collapse;border: 1px solid #000000;margin: 2px;width: 190px;overflow:hidden;position:relative;padding: 0px;background-color: #fff;">
    <tbody>
        <tr>
            <td style="color:#000000;" valign="top">
                <table style="width:100%;">
                    <tbody>
                        <tr>
                            <td style="width:75px">
                                <div style="position:relative;z-index:1;padding: 0px;float:left;">
                                    <div
                                        style="position:absolute;top:0;display:inline;margin-top:-100px;width: 0; height: 0; border-top: 230px solid transparent;border-left: 50px solid transparent;border-right:140px solid #ffffff; ">
                                    </div>
                                </div>
                                <img style="margin:5px 0 0 5px;" width="85" height="20" src="{{logo}}" alt="logo">
                            </td>
                            <td style="width:115px">
                                <div
                                    style="float:right;margin-top:-6px;margin-right:0px;width:5%;text-align:right;font-size:7px;">
                                </div>
                                <div
                                    style="text-align:right;font-weight:bold;font-family:Tahoma;font-size:20px;padding-left:17px;color:#000000">
                                    <small
                                        style="font-size:10px;margin-left:-17px;position:absolute;">{{price_small}}</small>{{price_big}}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td style="color:#000000;border-collapse: collapse;" valign="top">
                <table style="width:100%;border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td style="width:95px" valign="top">
                                <div style="clear:both;color:#000000;margin-top:2px;margin-bottom:2.5px;">
                                    <div
                                        style="padding:0px;border-bottom:1px solid #000000;text-align:center;font-weight:bold;font-size:10px;">
                                        VOUCHER</div>
                                    <div
                                        style="padding:0px;border-bottom:1px solid #000000;text-align:center;font-weight:bold;font-size:17px;color:#000000;">
                                        {{username}}</div>
                                </div>
                                <div
                                    style="text-align:center;color:#000000;font-size:7px;font-weight:bold;margin:0px;padding:2.5px;">
                                    Hubungkan ke wifi {{hotspotname}}<br>
                                    Buka browser: {{dnsname}}
                                </div>
                            </td>
                            <td style="width:100px;text-align:right;">
                                <div style="clear:both;padding:0 2.5px;font-size:7px;font-weight:bold;color:#000000">
                                    {{validity}}<br> {{timelimit}} <br>{{datalimit}}
                                </div>
                                <div style="clear:both;padding:0 2.5px;font-size:7px;font-weight:bold;color:#000000">
                                    {{qrcode}}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="background:#000000;color:#ffffff;padding:0px;" valign="top" colspan="2">
                                <div
                                    style="text-align:center;color:#ffffff;font-size:9px;font-weight:bold;margin:0px;padding:2.5px;">
                                    <b>BERUBAH, BERKARYA, BERDAMPAK</b>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>