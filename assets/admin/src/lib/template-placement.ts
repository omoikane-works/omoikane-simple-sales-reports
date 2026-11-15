import type { BrandingPlacement, BrandingSlot, TemplateSettings } from "@/types/templates";

export const brandingSlots = ["logo", "stamp"] as const;
export const placementFields = ["x_mm", "y_mm", "width_mm"] as const;
export type PlacementField = (typeof placementFields)[number];
type PlacementInput = Record<PlacementField, string> & { enabled: boolean };
export type PlacementDraft = Record<BrandingSlot, PlacementInput>;
export type InvalidPlacementFields = Partial<Record<BrandingSlot, PlacementField[]>>;

export const minimum = { x_mm: -1000, y_mm: -1000, width_mm: 0.1 };
export const maximum = 1000;

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === "object" && value !== null && !Array.isArray(value);
}

function inRange(field: PlacementField, value: unknown): value is number {
    return typeof value === "number" && Number.isFinite(value)
        && value >= minimum[field] && value <= maximum;
}

function isPlacement(value: unknown): value is BrandingPlacement {
    return isRecord(value) && typeof value.enabled === "boolean"
        && placementFields.every((field) => inRange(field, value[field]));
}

export function copySettings(value: unknown): TemplateSettings | null {
    if (!isRecord(value) || !isRecord(value.branding)) {
        return null;
    }

    const { logo, stamp } = value.branding;
    if (!isPlacement(logo) || !isPlacement(stamp)) {
        return null;
    }

    return {
        branding: {
            logo: pickPlacement(logo),
            stamp: pickPlacement(stamp),
        },
    };
}

function pickPlacement({ enabled, x_mm, y_mm, width_mm }: BrandingPlacement): BrandingPlacement {
    return { enabled, x_mm, y_mm, width_mm };
}

export function createPlacementDraft({ branding }: TemplateSettings): PlacementDraft {
    const fromPlacement = ({ enabled, x_mm, y_mm, width_mm }: BrandingPlacement): PlacementInput => ({
        enabled, x_mm: String(x_mm), y_mm: String(y_mm), width_mm: String(width_mm),
    });
    return { logo: fromPlacement(branding.logo), stamp: fromPlacement(branding.stamp) };
}

export function readPlacementDraft(draft: PlacementDraft):
    | { settings: TemplateSettings }
    | { invalid: InvalidPlacementFields } {
    const toPlacement = ({ enabled, x_mm, y_mm, width_mm }: PlacementInput): BrandingPlacement => ({
        enabled, x_mm: parseNumber(x_mm), y_mm: parseNumber(y_mm), width_mm: parseNumber(width_mm),
    });
    const branding = { logo: toPlacement(draft.logo), stamp: toPlacement(draft.stamp) };
    const invalid: InvalidPlacementFields = {};
    for (const slot of brandingSlots) {
        const fields = placementFields.filter((field) => !inRange(field, branding[slot][field]));
        if (fields.length > 0) {
            invalid[slot] = fields;
        }
    }

    return Object.keys(invalid).length === 0 ? { settings: { branding } } : { invalid };
}

function parseNumber(value: string): number {
    // Match a complete HTML number input value before converting it.
    return /^-?(?:\d+(?:\.\d+)?|\.\d+)(?:e[+-]?\d+)?$/i.test(value) ? Number(value) : NaN;
}

export function placementChanged(current: TemplateSettings, original: TemplateSettings): boolean {
    return brandingSlots.some((slot) => {
        const value = current.branding[slot];
        const baseline = original.branding[slot];
        return value.enabled !== baseline.enabled
            || placementFields.some((field) => value[field] !== baseline[field]);
    });
}

export function hasBrandingTags(content: string): boolean {
    const body = content.replace(/\{\{![\s\S]*?\}\}/g, "");
    return /\{\{\s*#?\s*branding\.(?:logo|stamp)(?:\.src)?\s*\}\}/.test(body)
        || (/\{\{\s*#\s*branding\s*\}\}/.test(body)
            && /\{\{\s*#\s*(?:logo|stamp)\s*\}\}/.test(body));
}
