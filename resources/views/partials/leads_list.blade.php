<table class="table table-bordered">
    <thead>
        <tr>
            <th>Title</th>
            <th>Contact Name</th>
            <th>Company</th>
            <th>Created At</th>
            <th>Status</th>
            <th>Converted Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($leads as $lead)
        @php
         $company = $lead->organization ? $lead->organization->name : '-';
            $contact = $lead->person ? trim($lead->person->first_name . ' ' . $lead->person->last_name) : '-';
        @endphp
            <tr>
                <td><a href="{{ route('leads.show', $lead->id) }}">{{ $lead->title }}</a></td>
                <td>{{ $contact }}</td>
                <td>{{ $company }}</td>
                <td>{{ ($lead->created_at ? date('d-m-Y', strtotime($lead->created_at)) : '-') }}</td>
                <td>{{ $lead->status }}</td>
                <td><span class="badge bg-{{ $lead->converted_at ? 'success' : 'secondary' }}">{{ $lead->converted_at ? 'Converted' : 'Not Converted' }}</span></td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">No leads found.</td>
            </tr>
        @endforelse
    </tbody>
</table>