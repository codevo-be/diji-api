<table style="width: 100%; margin-bottom: 30px;">
    <thead>
    <tr>
        <th style="padding: 10px 15px; font-size:9px; text-align: left; color:black;">Nom</th>
        <th style="padding: 10px 15px; font-size:9px; text-align: left; color:black;">Quantité</th>
        <th style="padding: 10px 15px; font-size:9px; text-align: left; color:black;">Prix</th>
        <th style="padding: 10px 15px; font-size:9px; text-align: left; color:black;">TVA</th>
        <th style="padding: 10px 15px; font-size:9px; text-align: left; color:black;">Total HT</th>
        <th style="padding: 10px 15px; font-size:9px; text-align: left; color:black;">Total TVAC</th>
    </tr>
    </thead>
    <tbody>
    @foreach(($items ?? []) as $item)
        @include('billing::components.line-item')
    @endforeach
    </tbody>
</table>
