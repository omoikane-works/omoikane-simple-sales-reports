<script setup lang="ts">
import { ref, watch } from "vue";
import type { TemplateData } from "@/types/templates";
import type { UpdateTemplatePayload } from "@/services/templates";
import { t } from "@/lib/i18n";

const props = defineProps<{
    editingTemplate: TemplateData;
    isLoading: boolean;
}>();

const emit = defineEmits<{
    save: [payload: UpdateTemplatePayload];
    back: [];
    validationError: [message: string];
}>();

const form = ref<UpdateTemplatePayload>({
    name: props.editingTemplate.name,
    content: props.editingTemplate.content,
});

const submit = () => {
    const errors: string[] = [];
    const name = form.value.name.trim();
    const content = form.value.content.trim();

    if (name === "") {
        errors.push(t("template.validation.name_required"));
    }

    if (content === "") {
        errors.push(t("template.validation.content_required"));
    }

    if (errors.length > 0) {
        emit("validationError", errors.join("\n"));
        return;
    }

    emit("save", { name, content });
};

watch(
    () => props.editingTemplate,
    (template) => {
        form.value = {
            name: template.name,
            content: template.content,
        };
    },
);
</script>

<template>
    <div class="ossr-pk-edit-form">
        <p class="ossr-pk-form-item">
            <label>
                <span>{{ t("template.form.name") }}</span>
                <input v-model="form.name" type="text" class="regular-text" />
            </label>
        </p>

        <p class="ossr-pk-form-item">
            <label>
                <span>{{ t("template.form.content") }}</span>
                <textarea
                    v-model="form.content"
                    rows="18"
                    class="large-text code"
                ></textarea>
            </label>
        </p>

        <div class="ossr-pk-form-actions">
            <button
                type="button"
                class="button button-primary"
                :disabled="isLoading || editingTemplate.is_system"
                @click="submit"
            >
                {{ t("common.save") }}
            </button>

            <button
                type="button"
                class="button"
                :disabled="isLoading"
                @click="emit('back')"
            >
                {{ t("common.back") }}
            </button>
        </div>
    </div>
</template>

<style scoped lang="css">
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
    font-weight: bold;
}

.ossr-pk-form-item label span::after {
    content: "\A";
    white-space: pre;
}
</style>
