@props(['href','icon','title','description'])
<a href="{{ $href }}"><x-admin-icon :name="$icon"/><strong>{{ $title }}</strong><small>{{ $description }}</small><b>←</b></a>
