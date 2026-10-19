@inject('branding', 'App\Support\MailBranding')
<x-mail::layout>
<x-slot:header>@include('runa-mail::text.header')</x-slot:header>
{{ $slot }}
@isset($subcopy)
<x-slot:subcopy>{{ $subcopy }}</x-slot:subcopy>
@endisset
<x-slot:footer>@include('runa-mail::text.footer')</x-slot:footer>
</x-mail::layout>
