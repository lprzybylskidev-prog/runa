/** Narrow declarations for the browser-only API used by our bounded table adapter. */
declare module 'pdfmake/build/pdfmake.min.js' {
  interface Cell {
    text: string;
    bold?: boolean;
  }
  interface Definition {
    pageSize: 'A4';
    pageOrientation: 'portrait' | 'landscape';
    defaultStyle: { font: string; fontSize: number };
    content: {
      table: { headerRows: number; widths: string[]; body: Cell[][] };
      layout: string;
    }[];
  }
  const pdf: {
    addVirtualFileSystem(fonts: Record<string, string>): void;
    setUrlAccessPolicy(policy: (url: string) => boolean): void;
    createPdf(definition: Definition): { getBlob(): Promise<Blob> };
  };
  export default pdf;
}
declare module 'pdfmake/build/vfs_fonts.js' {
  const fonts: Record<string, string>;
  export default fonts;
}
