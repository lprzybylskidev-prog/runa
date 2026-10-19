@inject('branding', 'App\Support\MailBranding')
@php($logo = $branding->logo())
<tr><td class="header" align="center">
<table align="center" cellpadding="0" cellspacing="0" role="presentation"><tr>
@if($logo)
<td width="42" valign="middle"><img src="{{ $logo }}" width="42" height="42" alt="{{ config('branding.mail_logo_alt') ?: $branding->name() }}" class="logo" /></td>
<td width="12" aria-hidden="true">&nbsp;</td>
@endif
<td class="brand-name" valign="middle">@if(config('branding.application_id') === 'runa' && $branding->name() === 'Runa')runa<span class="brand-dot">.</span>@else{{ $branding->name() }}@endif</td>
</tr></table>
</td></tr>
