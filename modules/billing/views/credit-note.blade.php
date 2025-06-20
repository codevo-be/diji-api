@extends("billing::layouts.main")

@section("content")
    <div>
        @if($logo)
            <img style="width:220px; object-position: left; object-fit: contain; margin-bottom:30px;" src={!! $logo !!} />
        @endif

        <table style="width: 100%;">
            <tbody>
            <tr>
                <td style="width: 50%">
                    <h1 style="font-weight: 700; font-size: 14px;">{!! $issuer["name"] !!}</h1>
                    <p style=" font-size: 14px;">
                        {!! $issuer["street"] !!} {!! $issuer["street_number"] !!}
                    </p>
                    <p style=" font-size: 14px;">
                        {!! $issuer["zipcode"] !!} {!! $issuer["city"] !!}
                    </p>

                    <p style=" font-size: 14px;">
                        {!! $issuer["country"] !!}
                    </p>

                    @if(isset($issuer["vat_number"]))
                        <p style="margin-top: 10px; font-size: 14px;">TVA {!! $issuer["vat_number"] !!}</p>
                    @endif

                    @if(isset($issuer["iban"]))
                        <p style="margin-top: 10px; font-size: 14px;">CB/Compte banquaire : {!! $issuer["iban"] !!}</p>
                    @endif

                    <p style=" font-size: 14px;">
                        @if(isset($issuer["phone"]))
                            {!! $issuer["phone"] !!}
                        @endif
                        @if(isset($issuer["email"]))
                            {!! $issuer["email"] !!}
                        @endif
                    </p>
                </td>
                <td style="width: 50%">
                    <h1 style="font-weight: 700; font-size: 14px;">{!! $recipient["name"] !!}</h1>
                    <p style=" font-size: 14px;">
                        {!! $recipient["street"] !!} {!! $recipient["street_number"] !!}
                    </p>
                    <p style=" font-size: 14px;">
                        {!! $recipient["zipcode"] !!} {!! $recipient["city"] !!}
                    </p>

                    <p style=" font-size: 14px;">
                        {!! $recipient["country"] !!}
                    </p>

                    @if(isset($recipient["vat_number"]))
                        <p style="margin-top: 10px; font-size: 14px;">TVA {!! $recipient["vat_number"] !!}</p>
                    @endif

                    <p style=" font-size: 14px;">
                        @if(isset($recipient["phone"]))
                            {!! $recipient["phone"] !!}
                        @endif
                        @if(isset($recipient["email"]))
                            {!! $recipient["email"] !!}
                        @endif
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top:40px; margin-bottom: 100px;">
        <div style="margin-bottom: 60px;">
            <h2 style="font-size:36px; font-weight: bold;">
                <span style="">Note de crédit </span>
                <span style="color: #A5A5A5;">{!! $identifier !!}</span>
            </h2>

            <table style="font-size:14px; margin-top: 20px;">
                <tbody>
                <tr>
                    <td style="padding-right: 10px;">Date</td>
                    <td>{!! \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') !!}</td>
                </tr>

                </tbody>
            </table>
        </div>

        @include('billing::components.items')

        <div style="margin-top:40px; width:40%; margin-left:auto;">
            <table style="font-size: 14px; page-break-inside: avoid;">
                <tbody>
                <tr>
                    <td style="padding-right: 20px;">Total HTVA</td>
                    <td style="text-align: right;">{!! \Diji\Billing\Helpers\PricingHelper::formatCurrency($subtotal ?? 0) !!}</td>
                </tr>

                @foreach($taxes ?? [] as $tax => $value)

                    @if(\Diji\Billing\Helpers\Invoice::isIntracommunity($issuer, $recipient) && $tax === 0)
                        <tr>
                            <td>TVA intracommunautaire</td>
                        </tr>
                    @else
                        <tr>
                            <td>TVA {!! $tax !!}%</td>
                            <td style="text-align: right">{!! \Diji\Billing\Helpers\PricingHelper::formatCurrency($value ?? 0) !!}</td>
                        </tr>
                    @endif

                @endforeach

                <tr style="font-size: 16px; font-weight: 700;">
                    <td style="padding-top:15px;">Total</td>
                    <td style="padding-top:15px;">{!! \Diji\Billing\Helpers\PricingHelper::formatCurrency($total ?? 0) !!}</td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
@endsection
