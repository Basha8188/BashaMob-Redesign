@props(['tone','icon','title','description','time'])
<div><span class="activity-icon {{ $tone }}">{{ $icon }}</span><p><strong>{{ $title }}</strong><small>{{ $description }}</small></p><time>{{ $time }}</time></div>
