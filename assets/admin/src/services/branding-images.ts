import { t } from "@/lib/i18n";
import { wpRest } from "@/lib/wp-rest";

export type BrandingSlot = "logo" | "stamp";

export type BrandingImage = {
    mime: "image/png" | "image/jpeg" | "image/webp";
    data: string;
};

type ImageResponse = { image: BrandingImage | null };

const maxBytes = 1_048_576;

function route(slot: BrandingSlot): string {
    return `/config/branding/${slot}`;
}

function readImage(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const result = reader.result;
            if (typeof result === "string" && result.includes(",")) {
                resolve(result.slice(result.indexOf(",") + 1));
            } else {
                reject(new Error(t("branding.read_failed")));
            }
        };
        reader.onerror = () => reject(new Error(t("branding.read_failed")));
        reader.onabort = reader.onerror;
        reader.readAsDataURL(file);
    });
}

export const brandingImages = {
    async get(slot: BrandingSlot): Promise<BrandingImage | null> {
        return (await wpRest.get<ImageResponse>(route(slot))).image;
    },
    async save(slot: BrandingSlot, file: File): Promise<BrandingImage> {
        if (file.size > maxBytes) {
            throw new Error(t("branding.too_large"));
        }

        const data = await readImage(file);
        return (await wpRest.put<{ image: BrandingImage }>(route(slot), { data })).image;
    },
    delete(slot: BrandingSlot): Promise<void> {
        return wpRest.delete<void>(route(slot));
    },
};
