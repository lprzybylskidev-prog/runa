import type { FooterOptions } from './identity';
export interface HttpErrorContent {
  status: number;
  locale: 'pl' | 'en';
  theme: 'system' | 'light' | 'dark';
  code: string;
  title: string;
  description: string;
  home: string;
  retry: string;
}
export interface HttpErrorLayoutProps {
  staticPreferences: {
    locale: HttpErrorContent['locale'];
    theme: HttpErrorContent['theme'];
  };
  footer: FooterOptions;
  error: HttpErrorContent;
}
