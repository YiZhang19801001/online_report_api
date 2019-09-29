<h1>{{$title}}</h1>
<h3>{{$shopName}}</h3>
<h6>{{$groupCode}}</h6>
<table class="table table-bordered" id="laravel_crud">
 <thead>
    <tr style="background-color:grey">
      <th>Product ID</th>
      <th>Product Description</th>
      <th>Cost Inc</th>
      <th>Sell Inc</th>
      <th>GP</th>
      <th>Qty</th>
      <th>Extension</th>
    </tr>
 </thead>
 <tbody>
    @foreach($reports as $report)
    <tr>
      <td>{{ $report->Barcode }}</td>
      <td>{{ $report->description }}</td>
      <td>{{ number_format($report->cost_inc,2) }}</td>
      <td>{{number_format($report->sell_inc,2)}}</td>
      <td>{{number_format($report->gp * 100,2)}}%</td>
      <td>{{number_format($report->qty,0)}}</td>
      <td>{{number_format($report->extension,2)}}</td>
    </tr>
    @endforeach
    <tr style="font-weight:bold">
    <td></td>
      <td></td>
      <td colspan="2">total sales</td>
      <td></td>

      <td>{{number_format($totalQty,0)}}</td>
      <td>{{number_format($totalSale,2)}}</td>
 </tr>
 </tbody>

</table>
