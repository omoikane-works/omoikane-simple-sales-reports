<script setup lang="ts">
import { nextTick, ref, watch } from "vue";
import TemplateBrandingSettings from "@/components/TemplateBrandingSettings.vue";
import type { TemplateData, TemplateSettings } from "@/types/templates";
import type { UpdateTemplatePayload } from "@/services/templates";
import { t } from "@/lib/i18n";
import {
    copySettings,
    createPlacementDraft,
    hasBrandingTags,
    placementChanged,
    readPlacementDraft,
    type InvalidPlacementFields,
    type PlacementDraft,
} from "@/lib/template-placement";

const props = defineProps<{
    editingTemplate: TemplateData;
    isLoading: boolean;
}>();

const emit = defineEmits<{
    save: [payload: UpdateTemplatePayload];
    back: [];
    duplicate: [];
    validationError: [message: string];
}>();

const name = ref("");
const content = ref("");
const baseline = ref<TemplateSettings | null>(null);
const draft = ref<PlacementDraft | null>(null);
const invalidFields = ref<InvalidPlacementFields>({});
const repair = ref(false);
const formElement = ref<HTMLFormElement | null>(null);
const placementHeading = ref<HTMLHeadingElement | null>(null);

watch(
    () => props.editingTemplate,
    (template) => {
        name.value = template.name;
        content.value = template.content;
        baseline.value = copySettings(template.settings);
        draft.value = baseline.value ? createPlacementDraft(baseline.value) : null;
        repair.value = false;
        invalidFields.value = {};
    },
    { immediate: true },
);

function focusPlacement() {
    placementHeading.value?.focus();
}

defineExpose({ focusPlacement });

function setDraft(value: PlacementDraft) {
    draft.value = value;
    invalidFields.value = {};
}

function selectRepair() {
    if (baseline.value) {
        setDraft(createPlacementDraft(baseline.value));
        repair.value = true;
    }
}

async function submit() {
    if (props.isLoading || props.editingTemplate.is_system) {
        return;
    }

    const payload: UpdateTemplatePayload = { name: name.value.trim(), content: content.value.trim() };
    const errors: string[] = [];
    if (payload.name === "") {
        errors.push(t("template.validation.name_required"));
    }
    if (payload.content === "") {
        errors.push(t("template.validation.content_required"));
    }

    if (baseline.value && draft.value) {
        const result = readPlacementDraft(draft.value);
        if ("invalid" in result) {
            invalidFields.value = result.invalid;
            errors.push(t("template.placement.validation_failed"));
        } else if (repair.value || placementChanged(result.settings, baseline.value)) {
            payload.settings = result.settings;
        }
    }

    if (errors.length > 0) {
        emit("validationError", errors.join("\n"));
        await nextTick();
        formElement.value?.querySelector<HTMLInputElement>('[aria-invalid="true"]')?.focus();
        return;
    }

    emit("save", payload);
}
</script>

<template>
    <form ref="formElement" class="ossr-pk-edit-form" novalidate @submit.prevent="submit">
        <p v-if="editingTemplate.is_system">{{ t("template.tooltip.system_edit") }}</p>
        <p class="ossr-pk-form-item">
            <label>
                <span>{{ t("template.form.name") }}</span>
                <input v-model="name" type="text" class="regular-text" :readonly="editingTemplate.is_system || isLoading" />
            </label>
        </p>

        <section aria-labelledby="ossr-placement-heading">
            <h3 id="ossr-placement-heading" ref="placementHeading" tabindex="-1">{{ t("template.placement.title") }}</h3>
            <div v-if="editingTemplate.settings_invalid" class="notice notice-warning inline" role="status">
                <p>{{ t("template.placement.damaged") }}</p>
                <p v-if="!editingTemplate.is_system && draft">
                    <button v-if="!repair" type="button" class="button" :disabled="isLoading" @click="selectRepair">
                        {{ t("template.placement.repair") }}
                    </button>
                    <span v-else>{{ t("template.placement.repair_pending") }}</span>
                </p>
            </div>
            <p v-if="!hasBrandingTags(content)" class="description" role="status">
                {{ t("template.placement.tags_missing") }}
            </p>
            <TemplateBrandingSettings
                v-if="draft"
                :draft="draft"
                :invalid-fields="invalidFields"
                :disabled="isLoading || editingTemplate.is_system"
                @update:draft="setDraft"
            />
            <p v-else role="status">{{ t("template.placement.unavailable") }}</p>
        </section>

        <p class="ossr-pk-form-item">
            <label>
                <span>{{ t("template.form.content") }}</span>
                <textarea v-model="content" rows="18" class="large-text code" :readonly="editingTemplate.is_system || isLoading"></textarea>
            </label>
        </p>

        <div class="ossr-pk-form-actions">
            <button
                v-if="editingTemplate.is_system"
                type="button"
                class="button button-primary"
                :disabled="isLoading"
                @click="emit('duplicate')"
            >
                {{ t("template.action.duplicate_and_edit") }}
            </button>
            <button v-else type="submit" class="button button-primary" :disabled="isLoading">
                {{ t("common.save") }}
            </button>
            <button type="button" class="button" :disabled="isLoading" @click="emit('back')">
                {{ t("common.back") }}
            </button>
        </div>
    </form>
</template>

<style scoped>
.ossr-pk-edit-form {
    max-width: 960px;
}

.ossr-pk-edit-form textarea {
    min-height: 420px;
    font-family: Menlo, Monaco, Consolas, monospace;
}

.ossr-pk-form-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.ossr-pk-form-item label span {
    display: block;
    font-weight: bold;
}
</style>
