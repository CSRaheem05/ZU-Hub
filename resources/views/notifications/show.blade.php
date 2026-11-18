@extends('layouts.glass')
@section('title', $notification->title())
@section('content')
<a class="back-link reveal" style="--i:0" href="{{ route('notifications.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>All notifications</a>

<div class="reveal" style="--i:1; max-width:820px">
  @include('notifications.partials.detail', ['n' => $notification])
</div>
@endsection
