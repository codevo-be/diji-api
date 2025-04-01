<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <style>
        body{
            margin: 0;
            font-family: sans-serif;
        }

        .subtitle{
            text-align: center;
            font-size: 14px;
            color: #7A7A7A;
            margin: 0;
        }

        .title{
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: black;
            margin: 10px 0 0 0;
        }

        .item{
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<main style="background-color: #00002B; padding: 30px 0">
    <div style="background-color: white; max-width: 640px; margin: 0 auto; padding:50px; border-radius: 12px;">

        @if($logo)
            <img style="width:220px; display: block; margin: 0 auto 40px auto; object-position: center; object-fit: contain;" src={!! $logo !!} />
        @endif

        <div style="text-align: center; font-size: 14px; margin-bottom:50px;">
            {!! nl2br(e($body)) !!}
        </div>

        <h2 style="text-align: center; margin-bottom: 30px;">Facture {!! $invoice["identifier"] !!}</h2>

        <div>

            @if($qrcode)
                <img style="display:block; margin: 0 auto 40px auto; width: 220px; height: 220px;" src="{!! $qrcode !!}"/>
            @endif

            <div class="item">
                <p class="subtitle">Numéro de compte</p>
                <p class="title">{!! $invoice["issuer"]["iban"] !!}</p>
            </div>

            <div class="item">
                <p class="subtitle">Communication structurée</p>
                <p class="title">{!! \Diji\Billing\Helpers\Invoice::formatStructuredCommunication($invoice["structured_communication"]) !!}</p>
            </div>

            <div class="item">
                <p class="subtitle">Avant le</p>
                <p class="title">{!! \Illuminate\Support\Carbon::parse($invoice["due_date"])->translatedFormat('d F Y') !!}</p>
            </div>

            <div class="item">
                <p class="subtitle">Le montant</p>
                <p class="title">{!! \Diji\Billing\Helpers\PricingHelper::formatCurrency($invoice["total"]) !!}</p>
            </div>
        </div>
    </div>
</main>
</body>
</html>
