<template>
  <section class="relative overflow-hidden text-white" style="background: linear-gradient(100deg, #125d96, #1670b3 55%, #2b8fd6)">
    <div class="absolute inset-0 pointer-events-none" style="background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1.4px, transparent 1.6px); background-size: 22px 22px"></div>
    <div class="relative container-app py-12 sm:py-14 grid lg:grid-cols-[1fr_auto] gap-8 items-center">
      <div class="flex items-start gap-5 max-w-2xl">
        <span class="hidden sm:flex w-14 h-14 shrink-0 rounded-xl bg-white/15 text-[#ffc21a] items-center justify-center">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" /></svg>
        </span>
        <div>
          <h2 class="h-section !text-white">{{ heading }}</h2>
          <p v-if="body" class="mt-3 text-[1.02rem] leading-relaxed text-white/80">{{ body }}</p>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-3">
        <WhatsAppButton size="lg" :topic="topic" green>WhatsApp for a free quote</WhatsAppButton>
        <a v-if="company.tel" :href="'tel:' + company.tel" class="btn btn-lg bg-white !text-[#0b1b30] hover:bg-[#f3f6fa]">
          <svg class="w-4 h-4 text-[#1670b3]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68l1.5 4.5a1 1 0 01-.5 1.2l-2.26 1.13a11 11 0 005.52 5.52l1.13-2.26a1 1 0 011.2-.5l4.5 1.5a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.7 21 3 14.3 3 6V5z" /></svg>
          Call {{ company.phone }}
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import WhatsAppButton from '@/Components/Site/WhatsAppButton.vue';

// Texts come from Admin → Website text. "page" picks that page's banner (services, projects,
// locations); empty fields fall back to the general banner.
const props = defineProps({
  page: { type: String, default: '' },
  title: String,
  text: String,
  topic: { type: String, default: '' },
});
const company = computed(() => usePage().props.company || {});
const t = computed(() => company.value.texts || {});
const heading = computed(() => props.title || (props.page && t.value[`cta_${props.page}_title`]) || t.value.cta_title);
const body = computed(() => props.text || (props.page && t.value[`cta_${props.page}_text`]) || t.value.cta_text);
</script>
