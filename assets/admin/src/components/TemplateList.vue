<script setup lang="ts">
import { t } from "@/lib/i18n";
import type { TemplateListItem } from "@/types/templates";

defineProps<{
    templates: TemplateListItem[];
    isLoading: boolean;
    hasError: boolean;
}>();

const emit = defineEmits<{
    edit: [template: TemplateListItem];
    remove: [template: TemplateListItem];
    duplicate: [template: TemplateListItem];
}>();
</script>

<template>
    <div class="ossr-pk-template-list">
        <p v-if="isLoading">{{ t("template.loading") }}</p>

        <table
            v-if="!isLoading && templates.length > 0"
            class="widefat striped"
        >
            <thead>
                <tr>
                    <th>{{ t("template.column.name") }}</th>
                    <th class="ossr-pk-template-version-column">
                        {{ t("template.column.version") }}
                    </th>
                    <th class="ossr-pk-template-type-column">
                        {{ t("template.column.type") }}
                    </th>
                    <th class="ossr-pk-template-actions-column">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="template in templates" :key="template.id">
                    <td>{{ template.name }}</td>
                    <td class="ossr-pk-template-version-column">
                        {{ template.version }}
                    </td>
                    <td class="ossr-pk-template-type-column">
                        {{
                            template.is_system
                                ? t("template.type.system")
                                : t("template.type.user")
                        }}
                    </td>
                    <td class="ossr-pk-template-actions-column">
                        <div class="ossr-pk-template-actions">
                            <button
                                type="button"
                                class="button"
                                :disabled="isLoading || template.is_system"
                                :title="
                                    template.is_system
                                        ? t('template.tooltip.system_edit')
                                        : ''
                                "
                                @click="emit('edit', template)"
                            >
                                {{ t("common.edit") }}
                            </button>

                            <button
                                type="button"
                                class="button"
                                :disabled="isLoading || template.is_system"
                                :title="
                                    template.is_system
                                        ? t('template.tooltip.system_delete')
                                        : ''
                                "
                                @click="emit('remove', template)"
                            >
                                {{ t("common.delete") }}
                            </button>

                            <button
                                type="button"
                                class="button button-primary"
                                :disabled="isLoading"
                                @click="emit('duplicate', template)"
                            >
                                {{ t("template.action.duplicate_and_edit") }}
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <p v-if="!isLoading && templates.length === 0 && !hasError">
            {{ t("template.empty") }}
        </p>
    </div>
</template>

<style scoped lang="css">
.ossr-pk-template-list {
    max-width: 960px;
}

.ossr-pk-template-version-column {
    width: 100px;
    white-space: nowrap;
}

.ossr-pk-template-type-column {
    width: 150px;
    white-space: nowrap;
}

.ossr-pk-template-actions-column {
    width: 300px;
    text-align: right;
}

.ossr-pk-template-actions {
    display: flex;
    justify-content: flex-end;
    gap: 6px;
    flex-wrap: wrap;
}
</style>
