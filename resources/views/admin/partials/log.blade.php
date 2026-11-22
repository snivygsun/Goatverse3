<div class="table-wrap"><table>
    <tr><th>When</th><th>Who</th><th>Action</th><th>Target</th><th>Details</th></tr>
    @forelse($logs as $l)
    <tr><td>{{ $l->created_at->format('d M H:i') }}</td><td>{{ $l->actor?->username ?? 'system' }}</td><td>{{ $l->action }}</td><td>{{ $l->target?->username }}</td><td class="small">{{ json_encode($l->data) }}</td></tr>
    @empty<tr><td colspan="5" class="muted">Nothing yet.</td></tr>@endforelse
</table></div>
