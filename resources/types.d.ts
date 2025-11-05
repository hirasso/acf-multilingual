interface Window {
  ACFMultilingual?: {
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
}
