<table>
    <thead>
        <tr>
            <th style="text-align:left;">Month</th>
            <th style="text-align:left;">Employee Name</th>
            <th style="text-align:left;">Total Calls</th>
            <th style="text-align:left;">Total Leads</th>
            <th style="text-align:left;">Total Deals</th>
            <th style="text-align:left;">Deals Closed Won</th>
            <th style="text-align:left;">Deals Closed Lost</th>
            <th style="text-align:left;">Deal Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($results as $row)
        <tr>
            <td style="text-align:left;">{{ $row['month'] }}</td>
            <td style="text-align:left;">{{ $row['user']->name }}</td>
            <td style="text-align:left;">{{ $row['calls_count'] }}</td>
            <td style="text-align:left;">{{ $row['leads_count'] }}</td>
            <td style="text-align:left;">{{ $row['deals_count'] }}</td>
            <td style="text-align:left;">{{ $row['deals_won_count'] }}</td>
            <td style="text-align:left;">{{ $row['deals_lost_count'] }}</td>
            <td style="text-align:left;">{{ \App\Helpers\MoneyFormatter::format($row['deal_amount']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
