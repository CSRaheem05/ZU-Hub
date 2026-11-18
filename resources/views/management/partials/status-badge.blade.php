@php
  $labels = ['pending' => 'Pending review', 'changes_requested' => 'Needs changes', 'approved' => 'Approved', 'rejected' => 'Rejected'];
@endphp
<span class="status-badge status-{{ $status }}"><span class="status-dot"></span>{{ $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</span>
