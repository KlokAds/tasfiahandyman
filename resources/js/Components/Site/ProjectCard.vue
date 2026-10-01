<template>
  <figure class="group relative aspect-square rounded-lg overflow-hidden s-surface-2 m-0">
    <img :src="project.image ? img(project.image, 640) : '/logo.png'" :srcset="srcset(project.image, 960)" sizes="(min-width: 1024px) 300px, 50vw" :alt="project.name" loading="lazy" decoding="async" width="640" height="640"
      :data-full="project.image ? img(project.image, 1600) : null" data-zoom
      class="img-cover cursor-zoom-in group-hover:scale-[1.04] transition-transform duration-500" />
    <a v-if="project.video" :href="project.video" target="_blank" rel="noopener" class="absolute top-3 right-3 chip !bg-black/60 !text-white !border-white/20 backdrop-blur">▶ Video</a>
    <!-- Caption: always visible on phones, slides up on hover on desktop -->
    <figcaption v-if="title || details || (showService && project.service)" class="pointer-events-none absolute inset-x-0 bottom-0 p-3.5 pt-10 text-white bg-gradient-to-t from-[#0b1f38]/90 via-[#0b1f38]/50 to-transparent lg:translate-y-2 lg:opacity-0 lg:group-hover:translate-y-0 lg:group-hover:opacity-100 transition-all duration-300">
      <p v-if="showService && project.service" class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#ffc21a]">{{ project.service.name }}</p>
      <p v-if="title" class="text-[14px] font-semibold leading-snug line-clamp-2">{{ title }}</p>
      <p v-if="details" class="mt-0.5 text-[12px] text-white/70">{{ details }}</p>
    </figcaption>
  </figure>
</template>

<script setup>
import { monthYear } from '@/utils/fmt';
import { computed } from 'vue';
import { img, srcset } from '@/utils/img';

// A completed job as a square photo tile; click the photo to see it full size.
const props = defineProps({ project: { type: Object, required: true }, showService: { type: Boolean, default: true } });
// Imported jobs only have a placeholder name ("Handyman job #12"); don't show it as a caption.
const title = computed(() => (/^Handyman job #\d+$/i.test(props.project.name || '') ? '' : props.project.name));
const details = computed(() => [
  props.project.area || props.project.location?.name,
  props.project.property_type,
  props.project.completed_on && monthYear(props.project.completed_on),
].filter(Boolean).join(' · '));
</script>
