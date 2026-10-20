<template>
  <section
    class="min-w-0 rounded border border-line bg-[var(--runa-container-body-background)] text-sm"
  >
    <header
      v-if="$slots.header || $slots.actions"
      class="flex flex-wrap items-center justify-between gap-3 rounded-t-[3px] border-b border-line bg-[var(--runa-surface-raised)] text-ink px-5 py-4"
    >
      <div class="font-semibold"><slot name="header" /></div>
      <slot name="actions" />
    </header>
    <div class="p-5"><slot /></div>
    <footer
      v-if="$slots.footer"
      class="rounded-b-[3px] border-t border-line bg-[var(--runa-surface-raised)] px-5 py-3 text-xs text-muted"
    >
      <slot name="footer" />
    </footer>
  </section>
</template>
