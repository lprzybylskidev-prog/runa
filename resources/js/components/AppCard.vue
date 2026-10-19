<template>
  <section
    class="rounded border border-line bg-[var(--runa-surface-raised)] text-sm"
  >
    <header
      v-if="$slots.header || $slots.actions"
      class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-5"
    >
      <div><slot name="header" /></div>
      <slot name="actions" />
    </header>
    <div class="p-5"><slot /></div>
    <footer v-if="$slots.footer" class="border-t border-line p-5 text-muted">
      <slot name="footer" />
    </footer>
  </section>
</template>
