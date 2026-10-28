@inject('branding', 'Runa\Mail\MailBranding')
@if(config('branding.mail_footer.visible'))
@if(config('branding.mail_footer.application'))
{{ $branding->name() }} v{{ app(\Runa\Identity\ApplicationIdentity::class)->applicationVersion }}
@endif
@if(config('branding.mail_footer.platform'))
{{ $branding->poweredBy() }} v{{ app(\Runa\Identity\ApplicationIdentity::class)->platformVersion }}
@endif
@endif
