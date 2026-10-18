@inject('branding', 'App\Support\MailBranding')
@php($logo = $branding->logo())
<tr><td class="header" align="center">
@if($logo)
<img src="{{ $logo }}" width="64" height="64" alt="{{ config('branding.mail_logo_alt') ?: $branding->name() }}" class="logo" />
@endif
<p class="brand-name">{{ $branding->name() }}</p>
</td></tr>
