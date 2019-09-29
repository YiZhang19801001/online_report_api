<h1 style="width:100%;text-align:center;">{{$title}}</h1>
<h3 style="width:100%;text-align:center;">{{$shopName}}</h3>
<h4 style="width:100%;text-align:center;">{{$groupCode}}</h6>
<table style="border-spacing:0 3px" id="laravel_crud">
 <thead>
    <tr style="background-color:grey">
      <th>Product ID</th>
      <th>Product Description</th>
      <th style="text-align:right">Cost Inc</th>
      <th style="text-align:right">Sell Inc</th>
      <th style="text-align:right">GP</th>
      <th style="text-align:right">Qty</th>
      <th style="text-align:right">Extension</th>
    </tr>
 </thead>
 <tbody>
    @foreach($reports as $report)
    <tr style="border-bottom:1px solid rgba(0,0,0,0.3)">
      <td>{{ $report->Barcode }}</td>
      <td>{{ $report->description }}</td>
      <td style="text-align:right">{{ number_format($report->cost_inc,2) }}</td>
      <td style="text-align:right">{{number_format($report->sell_inc,2)}}</td>
      <td style="text-align:right">{{number_format($report->gp * 100,2)}}%</td>
      <td style="text-align:right">{{number_format($report->qty,0)}}</td>
      <td style="text-align:right">{{number_format($report->extension,2)}}</td>
    </tr>
    @endforeach
    <tr style="font-weight:bold">
    <td></td>
      <td></td>
      <td style="border-top:1px solid #000" colspan="2">total sales</td>
      <td style="border-top:1px solid #000"></td>

      <td style="border-top:1px solid #000;text-align:right">{{number_format($totalQty,0)}}</td>
      <td style="border-top:1px solid #000;text-align:right"> {{number_format($totalSale,2)}}</td>
 </tr>
 </tbody>

</table>
