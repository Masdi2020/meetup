<template>
    <section class="surface-card"><slot /></section>
</template>
<style scoped>
.surface-card {
    min-width: 0;
    padding: var(--ui-card-padding);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-card-radius);
    background: var(--ui-surface);
    box-shadow: var(--ui-shadow);
}
</style>
