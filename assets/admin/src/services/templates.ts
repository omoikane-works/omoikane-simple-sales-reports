// assets/admin/src/services/templates.ts

import { wpRest } from "../lib/wp-rest";
import type { TemplateListItem, TemplateData, TemplateSettings } from "../types/templates";

type TemplateListResponse = {
    items: TemplateListItem[];
};

type TemplateItemResponse = {
    item: TemplateData;
};

type DeleteTemplateResponse = {
    deleted: boolean;
};

export type UpdateTemplatePayload = {
    name: string;
    content: string;
    settings?: TemplateSettings;
};

export async function fetchTemplates(): Promise<TemplateListItem[]> {
    const data = await wpRest.get<TemplateListResponse>("templates");

    return data.items;
}

export async function fetchTemplate(id: number): Promise<TemplateData> {
    const data = await wpRest.get<TemplateItemResponse>(`templates/${id}`);

    return data.item;
}

export async function duplicateTemplate(id: number): Promise<TemplateData> {
    const data = await wpRest.post<TemplateItemResponse>(
        `templates/${id}/duplicate`,
    );

    return data.item;
}

export async function updateTemplate(
    id: number,
    payload: UpdateTemplatePayload,
): Promise<TemplateData> {
    const data = await wpRest.put<TemplateItemResponse>(
        `templates/${id}`,
        payload,
    );

    return data.item;
}

export async function deleteTemplate(id: number): Promise<boolean> {
    const data = await wpRest.delete<DeleteTemplateResponse>(`templates/${id}`);

    return data.deleted;
}
