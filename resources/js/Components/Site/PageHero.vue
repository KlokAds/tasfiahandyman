<template>
  <section class="relative s-dark overflow-hidden">
    <div class="absolute inset-0 grid-bg opacity-70"></div>
    <div class="absolute inset-y-0 right-0 w-1/2 hidden lg:block" style="background: linear-gradient(90deg, transparent, rgba(22, 112, 179, 0.18))"></div>
    <div :class="['relative container-app', compact ? 'py-10 sm:py-12' : 'py-12 sm:py-16']">
      <nav v-if="crumbs.length" class="crumbs !text-white/55 mb-5" aria-label="Breadcrumb">
        <Link href="/" class="hover:!text-white">Home</Link>
        <template v-for="c in crumbs" :key="c.label">
          <span class="sep">/</span>
          <Link v-if="c.href" :href="c.href" class="hover:!text-white">{{ c.label }}</Link>
          <span v-else class="text-white/85" aria-current="page">{{ c.label }}</span>
        </template>
      </nav>
      <div :class="['grid gap-8 lg:gap-12', image ? 'lg:grid-cols-[1fr_22rem] items-center' : 'lg:grid-cols-[1fr_auto] items-end']">
        <div class="max-w-3xl">
          <p v-if="eyebrow" class="eyebrow !text-[#ffc21a]">{{ eyebrow }}</p>
          <h1 class="h-page !text-white mt-3">{{ title }}</h1>
          <p v-if="lead" class="mt-4 text-[1.05rem] leading-relaxed text-white/70 max-w-2xl">{{ lead }}</p>
          <slot name="below" />
        </div>
        <!-- Optional photo: framed like a work photo pinned to the board -->
        <div v-if="image" class="hidden lg:block relative">
          <div class="absolute -top-3 -left-3 w-20 h-20 rounded-md" style="background-image: radial-gradient(rgba(255,194,26,0.7) 1.6px, transparent 1.8px); background-size: 11px 11px"></div>
          <div class="relative aspect-[4/3] rounded-lg overflow-hidden border-4 border-white/95 shadow-2xl">
            <img :src="img(image, 640)" :srcset="srcset(image, 960)" sizes="352px" alt="" class="img-cover" fetchpriority="high" decoding="async" />
          </div>
        </div>
        <slot />
      </div>
    </div>
    <div class="tape relative"></div>
  </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { img, srcset } from '@/utils/img';

defineProps({
  title: { type: String, required: true },
  eyebrow: String,
  lead: String,
  image: String,
  crumbs: { type: Array, default: () => [] },
  compact: Boolean,
});
</script>
