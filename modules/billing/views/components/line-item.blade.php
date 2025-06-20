@if($item["type"] === "title")
    <tr>
        <td style="padding: 22px 15px 6px 15px; font-size: 12px; font-weight: 700;">{!! nl2br($item['name']) !!}</td>

        <td></td>

        <td></td>

        <td></td>

        <td></td>

        <td></td>
    </tr>
@elseif($item["type"] === "text")
    <tr>
        <td style="padding: 6px 15px; font-size: 12px;">{!! nl2br($item['name']) !!}</td>

        <td></td>

        <td></td>

        <td></td>

        <td></td>

        <td></td>
    </tr>
@else
    <tr>
        <td style="padding: 6px 15px; font-size: 10px; border-top: 1px solid #F2F2F2;">{!! nl2br($item['name']) !!}</td>

        <td style="padding: 6px 15px; font-size: 10px; border-top: 1px solid #F2F2F2;">{!! $item['quantity'] !!}</td>

        @if(isset($item['retail'])) <td style="white-space: nowrap; padding: 6px 15px; font-size: 10px; border-top: 1px solid #F2F2F2;">{!! \Diji\Billing\Helpers\PricingHelper::formatCurrency($item['retail']['subtotal']) !!}</td> @else <td style="border-top: 1px solid #F2F2F2;"></td> @endif

        @if(isset($item['vat'])) <td style="padding: 6px 15px; font-size: 10px; border-top: 1px solid #F2F2F2;">{!! $item['vat'] !!}%</td> @else <td style="border-top: 1px solid #F2F2F2;"></td> @endif

        @if(isset($item['retail'])) <td style="white-space: nowrap; padding: 6px 15px; font-size: 10px; border-top: 1px solid #F2F2F2;">{!! \Diji\Billing\Helpers\PricingHelper::formatCurrency($item['retail']['subtotal'] * ($item['quantity'] ?? 1)) !!}</td> @else <td style="border-top: 1px solid #F2F2F2;"></td> @endif

        @if(isset($item['retail'])) <td style="white-space: nowrap; padding: 6px 15px; font-size: 10px; border-top: 1px solid #F2F2F2;">{!! \Diji\Billing\Helpers\PricingHelper::formatCurrency($item['retail']['total'] * ($item['quantity'] ?? 1)) !!}</td> @else <td style="border-top: 1px solid #F2F2F2;"></td> @endif
    </tr>
@endif
