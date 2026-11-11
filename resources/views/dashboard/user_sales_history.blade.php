@extends('layouts.app')
@section('content')
<div class="container-fluid ">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center py-4">
         <span class="fw-bold fs-5"><i class="bi bi-list-ul me-2"></i>Sales Target History</span>
          <div class="mt-2 mt-md-0 text-end  w-md-auto">  
            <a href="{{ route('dashboard') }}" class="btn btn-primary ms-auto px-3"><i class="bi bi-arrow-left me-1"></i> </a>
          </div>
    </div>
    
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Year</th>
                            <th>Month</th>
                            <th>Sales Target</th>
                            <th>Achieved Sales</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesHistory as $row)
                            <tr>
                                <td>{{ auth()->user()->name }}</td>
                                <td>{{ $row->year }}</td>
                                <td>{{ \Carbon\Carbon::create()->month($row->month)->format('F') }}</td>
                                <td>{{ $currency_symbol }} {{ number_format($row->sales_target, 2) }}</td>
                                <td>{{ $currency_symbol }} {{ number_format($row->achieved_sales, 2) }}</td>
                                <td>
                                    <div style="min-width:120px;">
                                        <div class="progress" style="height: 20px; background: #fff3cd;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $row->sales_target > 0 ? round(($row->achieved_sales / $row->sales_target) * 100, 2) : 0 }}%;" aria-valuenow="{{ $row->sales_target > 0 ? round(($row->achieved_sales / $row->sales_target) * 100, 2) : 0 }}" aria-valuemin="0" aria-valuemax="100">
                                                {{ $row->sales_target > 0 ? round(($row->achieved_sales / $row->sales_target) * 100, 2) : 0 }}%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        
</div>
@endsection
