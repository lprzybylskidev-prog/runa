declare module 'virtual:runa-module-components' {
  const components: import('../../lib/moduleFrontend').ModuleComponentLoaders;
  export const builds: {
    readonly schema_version: 1;
    readonly owners: Readonly<
      Record<
        string,
        {
          readonly manifest_hash: string | null;
          readonly build_id: string;
          readonly sources: Readonly<Record<string, string>>;
          readonly components: readonly string[];
        }
      >
    >;
  };
  export default components;
}
