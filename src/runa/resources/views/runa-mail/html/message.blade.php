@inject('branding', 'Runa\Support\MailBranding')
<x-mail::layout>
<x-slot:header>
@include('runa-mail::html.header')
</x-slot:header>
{!! $slot !!}
@isset($subcopy)
<x-slot:subcopy><x-mail::subcopy>{!! $subcopy !!}</x-mail::subcopy></x-slot:subcopy>
@endisset
<x-slot:footer>
@include('runa-mail::html.footer')
</x-slot:footer>
</x-mail::layout>
