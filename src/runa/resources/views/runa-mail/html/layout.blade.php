<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="color-scheme" content="light" />
<meta name="supported-color-schemes" content="light" />
<title>{{ config('branding.application_name') }}</title>
<style>
@media only screen and (max-width: 620px) {
.inner-body, .footer { width: 100% !important; }
.content-cell { padding: 24px 20px !important; }
}
</style>
{!! $head ?? '' !!}
</head>
<body>
<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}
<tr><td class="body" align="center">
<table class="inner-body" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation"><tr><td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}
{!! $subcopy ?? '' !!}
</td></tr></table>
</td></tr>
{!! $footer ?? '' !!}
</table>
</td></tr></table>
</body>
</html>
