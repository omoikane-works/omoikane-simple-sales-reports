<script setup lang="ts">
import { onMounted, reactive } from "vue";
import { t } from "@/lib/i18n";
import {
    brandingSlots,
    maximum,
    minimum,
    placementFields,
    type InvalidPlacementFields,
    type PlacementDraft,
    type PlacementField,
} from "@/lib/template-placement";
import { brandingImages } from "@/services/branding-images";
import type { BrandingSlot } from "@/types/templates";

const props = defineProps<{
    draft: PlacementDraft;
    invalidFields: InvalidPlacementFields;
    disabled: boolean;
}>();
const emit = defineEmits<{ "update:draft": [draft: PlacementDraft] }>();

type RegistrationStatus = "loading" | "registered" | "missing" | "unknown";
const status = reactive<Record<BrandingSlot, RegistrationStatus>>({
    logo: "loading",
    stamp: "loading",
});
const settingsUrl = window.ossrAdminLinks?.settings;
const fieldLabels = {
    x_mm: "template.placement.x",
    y_mm: "template.placement.y",
    width_mm: "template.placement.width",
};
const statusLabels: Record<RegistrationStatus, string> = {
    loading: "template.loading",
    registered: "template.placement.image_registered",
    missing: "branding.empty",
    unknown: "template.placement.image_unknown",
};

async function loadRegistration(slot: BrandingSlot) {
    status[slot] = "loading";
    try {
        status[slot] = await brandingImages.isRegistered(slot) ? "registered" : "missing";
    } catch {
        status[slot] = "unknown";
    }
}

function setEnabled(slot: BrandingSlot, event: Event) {
    const { checked } = event.target as HTMLInputElement;
    emit("update:draft", { ...props.draft, [slot]: { ...props.draft[slot], enabled: checked } });
}

function setNumber(slot: BrandingSlot, field: PlacementField, event: Event) {
    const input = event.target as HTMLInputElement;
    const value = input.validity.badInput ? "" : input.value;
    emit("update:draft", { ...props.draft, [slot]: { ...props.draft[slot], [field]: value } });
}

function isInvalid(slot: BrandingSlot, field: PlacementField): boolean {
    return props.invalidFields[slot]?.includes(field) ?? false;
}

function fieldId(slot: BrandingSlot, field: PlacementField) {
    return "ossr-placement-" + slot + "-" + field;
}

onMounted(() => {
    for (const slot of brandingSlots) {
        void loadRegistration(slot);
    }
});
</script>

<template>
    <p id="ossr-placement-help">{{ t("template.placement.help") }}</p>
    <div class="ossr-placement-slots">
        <fieldset v-for="slot in brandingSlots" :key="slot" class="ossr-placement-slot">
            <legend>{{ t("branding." + slot) }}</legend>
            <label>
                <input
                    type="checkbox"
                    :id="'ossr-placement-' + slot + '-enabled'"
                    :checked="draft[slot].enabled"
                    :disabled="disabled"
                    @change="setEnabled(slot, $event)"
                />
                {{ t("template.placement.enabled") }}
            </label>

            <p class="description" aria-live="polite">
                {{ t(statusLabels[status[slot]]) }}
                <button
                    v-if="status[slot] === 'unknown'"
                    class="button-link"
                    type="button"
                    @click="loadRegistration(slot)"
                >
                    {{ t("branding.retry") }}
                </button>
            </p>

            <div class="ossr-placement-fields">
                <div v-for="field in placementFields" :key="field">
                    <label :for="fieldId(slot, field)">{{ t(fieldLabels[field]) }}</label>
                    <input
                        :id="fieldId(slot, field)"
                        type="number"
                        step="any"
                        :min="minimum[field]"
                        :max="maximum"
                        :value="draft[slot][field]"
                        :disabled="disabled"
                        :aria-invalid="isInvalid(slot, field) || undefined"
                        :aria-describedby="isInvalid(slot, field) ? fieldId(slot, field) + '-error' : undefined"
                        @input="setNumber(slot, field, $event)"
                    />
                    <p v-if="isInvalid(slot, field)" :id="fieldId(slot, field) + '-error'" class="ossr-field-error">
                        {{ t(field === "width_mm" ? "template.placement.width_error" : "template.placement.coordinate_error") }}
                    </p>
                </div>
            </div>
        </fieldset>
    </div>
    <p v-if="settingsUrl">
        <a :href="settingsUrl" target="_blank" rel="noopener">{{ t("template.placement.open_settings") }}</a>
    </p>
</template>

<style scoped>
.ossr-placement-slots {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
    gap: 12px;
}

.ossr-placement-slot {
    min-width: 0;
    padding: 12px;
    border: 1px solid #c3c4c7;
}

.ossr-placement-slot legend {
    font-weight: 600;
    padding-inline: 4px;
}

.ossr-placement-fields {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-top: 12px;
}

.ossr-placement-fields label {
    display: block;
    margin-bottom: 4px;
}

.ossr-placement-fields input {
    width: 100%;
}

.ossr-field-error {
    color: #b32d2e;
    margin: 4px 0 0;
}
</style>
