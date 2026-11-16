<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive } from "vue";
import ShowMessage from "@/components/ShowMessage.vue";
import { t } from "@/lib/i18n";
import {
    brandingImages,
    type BrandingImage,
} from "@/services/branding-images";
import type { BrandingSlot } from "@/types/templates";

type ImageSettings = {
    slot: BrandingSlot;
    image: BrandingImage | null;
    selection: { file: File; previewUrl: string } | null;
    inputVersion: number;
    loading: boolean;
    loaded: boolean;
    saving: boolean;
    notice: string;
    error: string;
};

const settings = reactive<ImageSettings[]>(
    (["logo", "stamp"] as const).map((slot) => ({
        slot,
        image: null,
        selection: null,
        inputVersion: 0,
        loading: true,
        loaded: false,
        saving: false,
        notice: "",
        error: "",
    })),
);

async function load(item: ImageSettings) {
    item.loading = true;
    item.error = "";
    try {
        item.image = await brandingImages.get(item.slot);
        item.loaded = true;
    } catch (error) {
        item.error = error instanceof Error ? error.message : t("error.unknown");
    } finally {
        item.loading = false;
    }
}

onMounted(() => {
    for (const item of settings) {
        void load(item);
    }
});

onBeforeUnmount(() => {
    for (const item of settings) {
        setSelection(item, null);
    }
});

function setSelection(item: ImageSettings, file: File | null) {
    if (item.selection) {
        URL.revokeObjectURL(item.selection.previewUrl);
    }

    item.selection = file ? { file, previewUrl: URL.createObjectURL(file) } : null;
    if (!file) {
        item.inputVersion++;
    }
}

function selectFile(item: ImageSettings, event: Event) {
    setSelection(item, (event.target as HTMLInputElement).files?.[0] ?? null);
    item.notice = "";
    item.error = "";
}

function cancelSelection(item: ImageSettings) {
    setSelection(item, null);
    item.error = "";
}

async function changeImage(item: ImageSettings, file: File | null) {
    if (item.saving) {
        return;
    }

    item.saving = true;
    item.notice = "";
    item.error = "";
    try {
        if (file) {
            item.image = await brandingImages.save(item.slot, file);
            item.notice = t("branding.saved");
        } else {
            await brandingImages.delete(item.slot);
            item.image = null;
            item.notice = t("branding.deleted");
        }
        setSelection(item, null);
    } catch (error) {
        item.error = error instanceof Error ? error.message : t("error.unknown");
    } finally {
        item.saving = false;
    }
}

function confirmDelete(item: ImageSettings) {
    if (window.confirm(t("branding.confirm_delete"))) {
        void changeImage(item, null);
    }
}
</script>

<template>
    <h2>{{ t("branding.title") }}</h2>
    <p id="ossr-branding-limits">{{ t("branding.limits") }}</p>

    <section v-for="item in settings" :key="item.slot">
        <h3>{{ t(`branding.${item.slot}`) }}</h3>
        <div aria-live="polite">
            <ShowMessage :notice-message="item.notice" :error-message="item.error" />
        </div>
        <p v-if="item.loading">{{ t("template.loading") }}</p>
        <button v-else-if="!item.loaded" class="button" type="button" @click="load(item)">
            {{ t("branding.retry") }}
        </button>
        <form v-else @submit.prevent="item.selection && changeImage(item, item.selection.file)">
            <template v-if="item.selection || item.image">
                <p>{{ t(item.selection ? "branding.preview_unsaved" : "branding.preview_saved") }}</p>
                <img
                    class="ossr-branding-preview"
                    :src="item.selection?.previewUrl ?? `data:${item.image?.mime};base64,${item.image?.data}`"
                    :alt="t(`branding.${item.slot}`)"
                />
            </template>
            <p v-else>{{ t("branding.empty") }}</p>
            <p>
                <label :for="`ossr-branding-${item.slot}`">{{ t("branding.choose") }}</label>
                <input
                    :id="`ossr-branding-${item.slot}`"
                    :key="item.inputVersion"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    aria-describedby="ossr-branding-limits"
                    :disabled="item.saving"
                    @change="selectFile(item, $event)"
                />
            </p>
            <p>
                <button class="button button-primary" type="submit" :disabled="item.saving || !item.selection">
                    {{ t(item.image ? "branding.replace" : "branding.register") }}
                </button>
                <button
                    v-if="item.selection"
                    class="button"
                    type="button"
                    :disabled="item.saving"
                    @click="cancelSelection(item)"
                >
                    {{ t("branding.cancel_selection") }}
                </button>
                <button
                    v-if="item.image"
                    class="button"
                    type="button"
                    :disabled="item.saving"
                    @click="confirmDelete(item)"
                >
                    {{ t("common.delete") }}
                </button>
            </p>
        </form>
    </section>
</template>

<style scoped>
.ossr-branding-preview {
    display: block;
    max-width: min(100%, 320px);
    max-height: 180px;
    object-fit: contain;
}

section + section {
    border-top: 1px solid #c3c4c7;
    margin-top: 1.5em;
}

button + button,
label + input {
    margin-inline-start: 0.5em;
}
</style>
