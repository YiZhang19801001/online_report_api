<h1>{{$title}}</h1>
<h3>{{$shopName}}</h3>
<h6>{{$groupCode}}</h6>
<table class="table table-bordered" id="laravel_crud">
 <thead>
    <tr>
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
      <td>{{ $report->cost_inc }}</td>
      <td>{{$report->sell_inc}}</td>
      <td>{{$report->gp}}</td>
      <td>{{$report->qty}}</td>
      <td>{{$report->extension}}</td>
    </tr>
    @endforeach
 </tbody>
</table>
