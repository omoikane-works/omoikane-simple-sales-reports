// assets/admin/src/types/wp.d.ts

export {};

export type OssrRestConfig = {
    restUrl: string;
    nonce: string;
};

export type OssrI18n = Record<string, string>;

declare global {
    interface Window {
        ossrRestConfig?: OssrRestConfig;
        ossrI18n?: OssrI18n;
    }
}
