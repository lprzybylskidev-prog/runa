/** Escaped, optionally JSON-formatted text; expansion never fetches more data. */
export interface ValuePreviewProps {
  text: string;
  format?: 'text' | 'json';
  locale?: 'pl' | 'en';
}
