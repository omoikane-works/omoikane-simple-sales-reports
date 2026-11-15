<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from "vue";
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
const editingTemplate = ref<TemplateData | null>(null);
const editor = ref<InstanceType<typeof TemplateEditForm> | null>(null);
const listError = ref("");
const savedTemplateName = ref<string | null>(null);
const reportUrl = window.ossrAdminLinks?.report;
const activeError = computed(() => errorMessage.value || (editingTemplate.value === null ? listError.value : ""));
let latestListRequest = 0;

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
    savedTemplateName.value = null;
};

const loadTemplates = async (showLoading: boolean = true) => {
    const request = ++latestListRequest;
    if (showLoading) {
        isLoading.value = true;
    }

    try {
        const items = await fetchTemplates();
        if (request === latestListRequest) {
            templates.value = items;
            listError.value = "";
        }
    } catch (error) {
        if (request === latestListRequest) {
            listError.value = error instanceof Error ? error.message : t("error.unknown");
        }
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
    } catch (error) {
        setError(error);
    } finally {
        isLoading.value = false;
    }
};

const backToList = (clearMessagesFlag: boolean = true) => {
    editingTemplate.value = null;

    if (clearMessagesFlag) {
        clearMessages();
    }
};

const duplicateAndEditTemplate = async (template: TemplateListItem) => {
    isLoading.value = true;
    clearMessages();

    try {
        const duplicated = await duplicateTemplate(template.id);

        // The duplicate exists even if fetching the list fails.
        ++latestListRequest;
        templates.value = [duplicated, ...templates.value];
        editingTemplate.value = duplicated;
        setMessage(t("template.notice.duplicated"));
        void loadTemplates(false);
    } catch (error) {
        setError(error);
    } finally {
        isLoading.value = false;
    }

    if (editingTemplate.value && editingTemplate.value.id !== template.id) {
        await nextTick();
        editor.value?.focusPlacement();
    }
};

const saveTemplate = async (payload: UpdateTemplatePayload) => {
    if (isLoading.value || editingTemplate.value === null || editingTemplate.value.is_system) {
        return;
    }

    isLoading.value = true;
    clearMessages();

    try {
        const saved = await updateTemplate(editingTemplate.value.id, payload);

        editingTemplate.value = saved;
        ++latestListRequest;
        templates.value = templates.value.map((template) => template.id === saved.id ? saved : template);
        backToList(false);
        savedTemplateName.value = saved.name;
        setMessage(t("template.notice.saved"));
        void loadTemplates(false);
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

        <div aria-live="polite">
            <ShowMessage :notice-message="noticeMessage" :error-message="activeError" />
        </div>

        <p v-if="savedTemplateName !== null && reportUrl">
            <a :href="reportUrl">{{ t("template.action.open_report") }}</a>
            {{ format(t("template.notice.report_hint"), savedTemplateName) }}
        </p>

        <div class="ossr-pk-admin">
            <TemplateList
                v-if="editingTemplate === null"
                :templates="templates"
                :is-loading="isLoading"
                :has-error="activeError !== ''"
                @edit="editTemplate"
                @remove="removeTemplate"
                @duplicate="duplicateAndEditTemplate"
            />

            <TemplateEditForm
                v-else
                :key="editingTemplate.id"
                ref="editor"
                :editing-template="editingTemplate"
                :is-loading="isLoading"
                @save="saveTemplate"
                @back="backToList()"
                @duplicate="duplicateAndEditTemplate(editingTemplate)"
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
