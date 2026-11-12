<script setup lang="ts">
defineProps<{
    noticeMessage: string;
    errorMessage: string;
}>();
</script>

<template>
    <div
        v-if="noticeMessage"
        class="notice notice-success inline ossr-pk-message"
    >
        <p>{{ noticeMessage }}</p>
    </div>
    <div v-if="errorMessage" class="notice notice-error inline ossr-pk-message">
        <p>{{ errorMessage }}</p>
    </div>
</template>

<style scoped lang="css">
.ossr-pk-message p {
    white-space: pre-wrap;
}
</style>
