<template>
  <Link :href="`/service/${service.slug}`" class="group flex items-center gap-4 py-4">
    <span v-if="index" class="hidden sm:block w-7 text-[13px] font-bold s-subtle tabular-nums" style="font-family: var(--font-display)">{{ String(index).padStart(2, '0') }}</span>
    <span class="w-16 h-16 rounded-md overflow-hidden s-surface-2 shrink-0">
      <img :src="service.image ? img(service.image, 160) : '/logo.png'" :alt="service.name" class="img-cover group-hover:scale-110 transition-transform duration-500" loading="lazy" decoding="async" width="64" height="64" />
    </span>
    <span class="flex-1 min-w-0">
      <span class="block h-card !text-[16px] group-hover:text-[var(--s-accent-text)] transition-colors">{{ service.name }}</span>
      <span v-if="service.short_summary" class="mt-1 text-[13.5px] s-muted line-clamp-1">{{ service.short_summary }}</span>
    </span>
    <span v-if="service.from_price" class="badge-price shrink-0 hidden sm:inline-flex"><span class="text-[11px] font-medium s-subtle">from</span> S${{ groupDigits(service.from_price) }}</span>
    <span class="w-9 h-9 rounded-full border s-border flex items-center justify-center shrink-0 group-hover:bg-[var(--s-accent)] group-hover:border-[var(--s-accent)] group-hover:text-white transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
    </span>
  </Link>
</template>

<script setup>
import { groupDigits } from '@/utils/fmt';
import { Link } from '@inertiajs/vue3';
import { img } from '@/utils/img';

// One line of the "service menu": photo, name, one-line summary, arrow.
defineProps({ service: { type: Object, required: true }, index: { type: Number, default: 0 } });
</script>
