<x-mail::message>
# {{ $mailTitle }}

{{ $mailBody }}

@if ($code !== null)
<x-mail::panel>
{{ $code }}
</x-mail::panel>
@endif

@if ($actionUrl !== null && $actionLabel !== null)
<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>

<x-slot:subcopy>
{{ $language === 'en' ? 'If the button does not work, copy this address into your browser:' : 'Jeśli przycisk nie działa, skopiuj poniższy adres do przeglądarki:' }}

{{ $actionUrl }}
</x-slot:subcopy>
@endif
</x-mail::message>
