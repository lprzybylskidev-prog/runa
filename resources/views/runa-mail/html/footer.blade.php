@inject('branding', 'Runa\Support\MailBranding')
@if(config('branding.mail_footer.visible'))
<tr><td align="center"><table class="footer" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation"><tr><td class="footer-cell" align="center"><p>
@if(config('branding.mail_footer.application'))
{{ $branding->name() }} v{{ config('branding.application_version') }}
@endif
@if(config('branding.mail_footer.application') && config('branding.mail_footer.platform')) | @endif
@if(config('branding.mail_footer.platform'))
{{ $branding->poweredBy() }} v{{ config('branding.platform_version') }}
@endif
</p></td></tr></table></td></tr>
@endif
