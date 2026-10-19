@inject('branding', 'App\Support\MailBranding')
@if(config('branding.mail_footer.visible'))
@if(config('branding.mail_footer.application'))
{{ $branding->name() }} v{{ config('branding.application_version') }}
@endif
@if(config('branding.mail_footer.platform'))
{{ $branding->poweredBy() }} v{{ config('branding.platform_version') }}
@endif
@endif
