// assets/admin/src/types/templates.ts

export type BrandingSlot = "logo" | "stamp";

export type BrandingPlacement = {
    enabled: boolean;
    x_mm: number;
    y_mm: number;
    width_mm: number;
};

export type TemplateSettings = {
    branding: Record<BrandingSlot, BrandingPlacement>;
};

export type TemplateListItem = {
    id: number;
    template_key: string;
    name: string;
    type: string;
    version: string;
    is_system: boolean;
    is_default: boolean;
    is_active: boolean;
    created_at?: string;
    updated_at: string;
};

export type TemplateData = TemplateListItem & {
    content: string;
    settings?: TemplateSettings;
    settings_invalid: boolean;
};
