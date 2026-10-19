<x-mail::message>
# {{ $polish ? 'Raport jest gotowy' : 'Report is ready' }}

{{ $polish ? 'Dzień dobry,' : 'Hello,' }}

{{ $polish ? 'Raport projektu został przygotowany. Możesz teraz przejrzeć podsumowanie i pobrać dokument.' : 'The project report has been prepared. You can now review the summary and download the document.' }}

<x-mail::panel>
{{ $polish ? 'Projekt: Atlas · Okres: październik 2026' : 'Project: Atlas · Period: October 2026' }}
</x-mail::panel>

<x-mail::button url="https://example.invalid/reports/sample">
{{ $polish ? 'Otwórz raport' : 'Open report' }}
</x-mail::button>

@if($long)
@foreach(range(1, 12) as $section)
## {{ $polish ? 'Podsumowanie' : 'Summary' }} {{ $section }}

{{ $polish ? 'Zestawienie zawiera aktualny stan projektu, zakończone zadania i pozycje oczekujące na sprawdzenie. Szczegóły są dostępne w raporcie.' : 'The summary includes the current project status, completed tasks and items awaiting review. Details are available in the report.' }}

@endforeach
@endif
<x-slot:subcopy>
{{ $polish ? 'Jeśli przycisk nie działa, skopiuj ten adres do przeglądarki:' : 'If the button does not work, copy this address into your browser:' }}
[https://example.invalid/reports/sample](https://example.invalid/reports/sample)
</x-slot:subcopy>
</x-mail::message>
