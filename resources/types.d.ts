declare const ACFMultilingual: {
  defaultLanguage?: string;
  languages: {
    slug: string;
    locale: string;
    name: string;
    dir?: string;
  }[];
  isMobile: boolean;
  cookieHashForCurrentUri: string;
};

interface Window {
  ACFMultilingual?: ACFMultilingual;
}

declare namespace acf {
  function addAction(action: string, callback: (...args: any[]) => void): void;
  function doAction(action: string, ...args: any[]): void;
  function addFilter(filter: string, callback: (...args: any[]) => any): void;
}
