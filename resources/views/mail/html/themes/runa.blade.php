body, table, td, p, a, h1, h2, h3 { font-family: 'IBM Plex Mono', Consolas, 'Courier New', monospace; box-sizing: border-box; }
body { background-color: {{ config('branding.colors.background') }}; color: {{ config('branding.colors.text') }}; margin: 0; padding: 0; width: 100%; -webkit-text-size-adjust: 100%; }
.wrapper { background-color: {{ config('branding.colors.background') }}; width: 100%; }
.content { width: 100%; }
.header { padding: 32px 20px 24px; text-align: center; }
.logo { display: block; margin: 0 auto 12px; border: 0; }
.brand-name { font-size: 20px; font-weight: bold; margin: 0; text-align: center; }
.body { padding: 0 12px; }
.inner-body { width: 600px; max-width: 600px; background-color: {{ config('branding.colors.surface-raised') }}; border: 1px solid {{ config('branding.colors.border') }}; border-top: 4px solid {{ config('branding.colors.primary-solid') }}; border-radius: 4px; }
.content-cell { padding: 32px; overflow-wrap: anywhere; word-break: break-word; }
h1 { font-size: 24px; line-height: 1.35; margin: 0 0 24px; }
h2 { font-size: 19px; line-height: 1.4; margin: 24px 0 16px; }
h3 { font-size: 16px; line-height: 1.5; }
p, li { font-size: 14px; line-height: 1.75; }
p { margin: 0 0 20px; }
a { color: {{ config('branding.colors.primary-text') }}; text-decoration: underline; word-break: break-all; }
img { max-width: 100%; }
.action { margin: 28px auto; width: 100%; text-align: center; }
.button { display: inline-block; color: {{ config('branding.colors.primary-foreground') }}; background-color: {{ config('branding.colors.primary-solid') }}; border: 12px solid {{ config('branding.colors.primary-solid') }}; border-left-width: 20px; border-right-width: 20px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold; }
.button-success, .button-green { background-color: {{ config('branding.colors.success-solid') }}; border-color: {{ config('branding.colors.success-solid') }}; }
.button-error, .button-red { background-color: {{ config('branding.colors.danger-solid') }}; border-color: {{ config('branding.colors.danger-solid') }}; }
.subcopy { margin-top: 24px; padding-top: 20px; border-top: 1px solid {{ config('branding.colors.border') }}; }
.subcopy p { font-size: 12px; color: {{ config('branding.colors.text-secondary') }}; }
.footer { width: 600px; max-width: 600px; }
.footer-cell { padding: 24px 20px 32px; }
.footer p { font-size: 12px; line-height: 1.75; color: {{ config('branding.colors.text-secondary') }}; text-align: center; margin: 0; }
.panel { margin: 24px 0; border-left: 4px solid {{ config('branding.colors.primary-solid') }}; }
.panel-content { background-color: {{ config('branding.colors.primary-subtle') }}; padding: 16px; }
.panel-item p:last-child { margin-bottom: 0; }
.table table { width: 100%; border-collapse: collapse; margin: 24px 0; }
.table th, .table td { font-size: 13px; padding: 10px 8px; border-bottom: 1px solid {{ config('branding.colors.border') }}; text-align: left; }
.break-all { word-break: break-all; }
