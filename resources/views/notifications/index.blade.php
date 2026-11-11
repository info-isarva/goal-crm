@extends('layouts.app')

@section('content')
<div class="container px-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'notificationsPageSkeleton',
        'columns' => 4,
        'rows' => 10,
        'filterFields' => 1,
    ])

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Notifications</h3>
        <div>
            <a href="#" id="markAllReadPage" class="btn btn-sm btn-outline-primary">Mark all read</a>
        </div>
    </div>

    @if($notifications->count())
    <div class="list-group">
        @foreach($notifications as $notification)
        @php $data = $notification->data ?? []; $link = $data['task_link'] ?? $data['related_link'] ?? $data['meeting_link'] ??  '#'; @endphp
        <a href="{{ $link }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start {{ $notification->read_at ? '' : 'list-group-item-warning' }}" target="_self">
            <div>
                <div class="fw-semibold">{!! $data['message'] ?? ($data['title'] ?? 'Notification') !!}</div>
                <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
            </div>
            <div class="ms-3 text-end">
                @if(!$notification->read_at)
                <span class="badge bg-danger">New</span>
                @endif
                <div class="mt-2">
                    <a href="#" class="btn btn-sm btn-outline-secondary mark-read-btn" data-id="{{ $notification->id }}">Mark read</a>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
    @else
    <div class="text-muted">No notifications.</div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    function postMark(payload, cb){
        fetch("{{ route('notifications.markRead') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        }).then(function(r){ return r.json(); }).then(function(j){ if (cb) cb(j); }).catch(function(e){ console.error('Mark read failed', e); });
    }

    document.querySelectorAll('.mark-read-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var item = this.closest('.list-group-item');
            postMark({ id: id }, function(resp){
                if (resp && resp.success){
                    item.classList.remove('list-group-item-warning');
                }
            });
        });
    });

    var markAll = document.getElementById('markAllReadPage');
    if (markAll){
        markAll.addEventListener('click', function(e){
            e.preventDefault();
            postMark({ all: 1 }, function(resp){
                if (resp && resp.success){
                    document.querySelectorAll('.list-group-item.list-group-item-warning').forEach(function(i){ i.classList.remove('list-group-item-warning'); });
                }
            });
        });
    }
});
</script>

@endsection
