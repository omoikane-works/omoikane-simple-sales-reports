<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import {
    deleteTemplate as deleteTemplateRequest,
    duplicateTemplate,
    fetchTemplate,
    fetchTemplates,
    updateTemplate,
} from "@/services/templates";
import type { TemplateData, TemplateListItem } from "@/types/templates";
import type { UpdateTemplatePayload } from "@/services/templates";
import { format, t } from "@/lib/i18n";

import TemplateList from "@/components/TemplateList.vue";
import TemplateEditForm from "@/components/TemplateEditForm.vue";
import ShowMessage from "@/components/ShowMessage.vue";

const templates = ref<TemplateListItem[]>([]);
const isLoading = ref(false);
const errorMessage = ref("");
const noticeMessage = ref("");
const currentView = ref<"list" | "edit">("list");
const editingTemplate = ref<TemplateData | null>(null);
const hasError = computed(() => errorMessage.value !== "");

const setError = (error: unknown) => {
    errorMessage.value =
        error instanceof Error ? error.message : t("error.unknown");
};

const setMessage = (message: string) => {
    noticeMessage.value = message;
};

const showValidationError = (message: string) => {
    noticeMessage.value = "";
    errorMessage.value = message;
};

const clearMessages = () => {
    noticeMessage.value = "";
    errorMessage.value = "";
};

const loadTemplates = async (showLoading: boolean = true) => {
    if (showLoading) {
        isLoading.value = true;
    }

    try {
        templates.value = await fetchTemplates();
    } catch (error) {
        setError(error);
    } finally {
        if (showLoading) {
            isLoading.value = false;
        }
    }
};

const editTemplate = async (template: TemplateListItem) => {
    isLoading.value = true;
    clearMessages();

    try {
        editingTemplate.value = await fetchTemplate(template.id);
        currentView.value = "edit";
    } catch (error) {
        setError(error);
    } finally {
        isLoading.value = false;
    }
};

const backToList = (clearMessagesFlag: boolean = true) => {
    editingTemplate.value = null;
    currentView.value = "list";

    if (clearMessagesFlag) {
        clearMessages();
    }
};

const duplicateAndEditTemplate = async (template: TemplateListItem) => {
    isLoading.value = true;
    clearMessages();

    try {
        const duplicated = await duplicateTemplate(template.id);

        await loadTemplates(false);
        editingTemplate.value = duplicated;
        setMessage(t("template.notice.duplicated"));
        currentView.value = "edit";
    } catch (error) {
        setError(error);
    } finally {
        isLoading.value = false;
    }
};

const saveTemplate = async (payload: UpdateTemplatePayload) => {
    if (editingTemplate.value === null) {
        return;
    }

    isLoading.value = true;
    clearMessages();

    try {
        const saved = await updateTemplate(editingTemplate.value.id, payload);

        editingTemplate.value = saved;
        await loadTemplates(false);
        backToList(false);
        setMessage(t("template.notice.saved"));
    } catch (error) {
        setError(error);
    } finally {
        isLoading.value = false;
    }
};

const removeTemplate = async (template: TemplateListItem) => {
    if (!window.confirm(format(t("template.confirm.delete"), template.name))) {
        return;
    }

    isLoading.value = true;
    clearMessages();

    try {
        await deleteTemplateRequest(template.id);
        await loadTemplates(false);
        setMessage(t("template.notice.deleted"));
    } catch (error) {
        setError(error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    loadTemplates();
});
</script>

<template>
    <div class="ossr-pk-admin-panel">
        <h2>{{ t("template.title") }}</h2>

        <ShowMessage
            :notice-message="noticeMessage"
            :error-message="errorMessage"
        />

        <div class="ossr-pk-admin">
            <TemplateList
                v-if="currentView === 'list'"
                :templates="templates"
                :is-loading="isLoading"
                :has-error="hasError"
                @edit="editTemplate"
                @remove="removeTemplate"
                @duplicate="duplicateAndEditTemplate"
            />

            <TemplateEditForm
                v-else-if="currentView === 'edit' && editingTemplate !== null"
                :editing-template="editingTemplate"
                :is-loading="isLoading"
                @save="saveTemplate"
                @back="backToList"
                @validation-error="showValidationError"
            />
        </div>
    </div>
</template>

<style scoped lang="css">
.ossr-pk-admin-panel {
    max-width: 960px;
}
</style>
