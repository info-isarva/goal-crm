<table class="table table-bordered">
    <thead>
        <tr>
            <th>Title</th>
            <th>Contact Name</th>
            <th>Company</th>
            
            <th>Close Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($deals as $deal)
        @php
         $company = $deal->organization ? $deal->organization->name : '-';
            $contact = $deal->person ? trim($deal->person->first_name . ' ' . $deal->person->last_name) : '-';
        @endphp
            <tr>
                <td><a href="{{ route('deals.show', $deal->id) }}">{{ $deal->title }}</a></td>
                <td>{{ $contact }}</td>
                <td>{{ $company }}</td>
                
                <td>{{ $deal->close_date ? date('d-m-Y', strtotime($deal->close_date)) : '-' }}</td>
                <td><span class="badge bg-{{ $deal->status === 'Closed won' ? 'success' : ($deal->status === 'Closed lost' ? 'danger' : 'secondary') }}">{{ $deal->status }}</span></td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">No deals found.</td>
            </tr>
        @endforelse
    </tbody>
</table>