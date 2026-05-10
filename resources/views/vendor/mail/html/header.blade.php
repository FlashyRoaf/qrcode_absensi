@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://res.cloudinary.com/dxqhv1jzz/image/upload/v1778400868/logo-icon_x7mcly.png" class="logo" style="height: 48px; width: auto;" alt="Laravel Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
