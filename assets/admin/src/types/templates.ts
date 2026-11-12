// assets/admin/src/types/templates.ts

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
};
