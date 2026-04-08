
interface Window {
  ACFMultilingual?: {
    defaultLanguage: string;
    currentLanguage: string;
    languages: Record<string, {
      slug: string;
      locale: string;
      name: string;
      dir?: string;
    }>;
    /** only in the admin */
    isMobile?: boolean;
    cookieHashForCurrentUri?: string;
    /** only in the frontend */
    isFrontPage?: boolean;
  };
}


declare namespace acf {
  function addAction(action: string, callback: (...args: any[]) => void): void;
  function doAction(action: string, ...args: any[]): void;
  function addFilter(filter: string, callback: (...args: any[]) => any): void;
}
