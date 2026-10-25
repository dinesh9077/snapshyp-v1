<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Express Seller Cod Voucher</title>
</head>

    <body style="margin: 0; padding: 10px; box-sizing: border-box; font-family: sans-serif;">
        <table border="0" cellpadding="0" cellspacing="0" style="width: 595px; margin: 0 auto;border: 2px solid;padding: 10px 15px; text-transform: capitalize;">
            <tr>
                <td>
                    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; margin: 10px auto;">
                        <tr>
                            <td style="text-align: end;">
                                 <button id="downloadButton" class="btn btn-primary" style="background: #fff; border: 1px solid #000; padding: 8px; border-radius: 5px;cursor: pointer;">Download as PDF</button>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">
                                <a href="#">
                                    <img width="130" src="{{url('storage/settings/logo.png')}}" alt="">
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">
                                <p
                                    style="text-align: center;line-height: 1.5; margin-bottom: 0;margin-top: 30px;margin-bottom: 10px;">
                                    <span style="font-weight: 600;">COD Payment Voucher </span>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="text-align: start;line-height: 1.5; margin-bottom: 0;margin-top: 7px;"><span
                                        style="font-weight: 600;">Voucher No : </span> {{$data['voucher_data']->voucher_no }}
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="text-align: start;line-height: 1.5; margin-bottom: 0;margin-top: 7px;"><span
                                        style="font-weight: 600;">Voucher Date : </span> {{$data['voucher_data']->voucher_date }}
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="text-align: start;line-height: 1.5; margin-bottom: 0;margin-top: 7px;"><span
                                        style="font-weight: 600;">To : </span>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="text-align: start;line-height: 1.5; margin-bottom: 0;margin-top: 7px;"><span
                                        style="font-weight: 600;">{{$data['voucher_data']->user_name }}</span>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: start;border-bottom: 1px solid #bbb;padding-bottom: 0;">
                                <p style="text-align: start;line-height: 1.5;margin-top: 7px;"><span
                                        style="font-weight: 600;">
                                    </span> {{$data['voucher_data']->user_address }}
                                </p>
                            </td>
                        </tr>
                    </table>
                    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; margin: 10px auto;">
                        <tr>
                            <td>
                                <h3 style="margin-bottom: 0;">Item Details :</h3>
                            </td>
                        </tr>
                    </table>
                    <table border="0" cellpadding="0" cellspacing="0"
                        style="width: 100%; margin: 10px auto; border: 1px solid #000; padding: 0;">
                        <thead>
                            <tr style="white-space: nowrap;">
                                <th
                                    style="padding: 10px; text-align: start;background: #1B1B1B; color: #fff; font-size: 14px;">
                                    No.</th>
                                <th
                                    style="padding: 10px; text-align: start;background: #1B1B1B; color: #fff;font-size: 14px;">
                                    Order ID
                                </th>
                                <th
                                    style="padding: 10px; text-align: start;background: #1B1B1B; color: #fff;font-size: 14px;">
                                    Order Details
                                </th>
                                <th
                                    style="padding: 10px; text-align: start;background: #1B1B1B; color: #fff; font-size: 14px;">
                                    Shipping Company</th>
                                <th
                                    style="padding: 10px; text-align: start;background: #1B1B1B; color: #fff; font-size: 14px;">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @foreach($data['voucher_lists'] as $index => $voucher_list)
                            <tr>
                                <td style="padding: 10px;font-size: 14px;">{{ $index + 1 }}</td>
                                <td style="padding: 10px;font-size: 14px;">{{$voucher_list->order_id}}</td>
                                <td style="padding: 10px;font-size: 14px;">AWB No : {{$voucher_list->awb_number}}</td>
                                <td style="padding: 10px;font-size: 14px;">{{$voucher_list->shipping_companies_name}}</td>
                                <td style="padding: 10px;font-size: 14px;">{{$voucher_list->amount}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                         
                    </table>
                    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; margin: 10px auto;">
                        <tr>
                            <td>
                                <p
                                    style="text-align: end;line-height: 1.5; margin-bottom: 0;margin-top: 7px; font-size: 14px;padding-right: 19px;">
                                    <span style="font-weight: 600;">Total Amount :
                                    </span>{{$data['voucher_data']->total_amount }}
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p
                                    style="text-align: start;line-height: 1.5; margin-bottom: 0;margin-top: 7px; font-size: 14px;">
                                    <span style="font-weight: 600;">In Words Amount :
                                    </span> 
                                    <?php
                                      $amountInWords = Helper::AmountInWords($data['voucher_data']->total_amount);
                                     echo $amountInWords;
                                   
                                    ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p
                                    style="text-align: start;line-height: 1.5; margin-bottom: 0;margin-top: 7px; font-size: 14px;">
                                    <span style="font-weight: 600;">Term & Condition
                                    </span>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-top: 10px;">
                                <p style="margin-top: 0;  margin-bottom: 5px; text-align: start;"><span
                                        style="font-weight: 600;">Authorized</span> </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: start; ">
                                <a href="#">
                                    <img width="80" src="{{url('storage/settings/logo.png')}}" alt="">
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.js"></script>

<script>
    document.getElementById('downloadButton').addEventListener('click', function () {
        var element = document.querySelector('body');
        html2pdf(element);
    });
</script>

</html>