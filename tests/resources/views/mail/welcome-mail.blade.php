<x-mail::message>
Hi, welcome to {{ config('app.name') }}!

<x-mail::button url="{{ url('/') }}">
{{ config('app.name') }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
