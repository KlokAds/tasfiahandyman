<template>
  <FrontendLayout>
    <PageHero title="Customer reviews" eyebrow="Reviews" lead="What homeowners and businesses across Singapore say about our work." :crumbs="[{ label: 'Reviews' }]" />

    <!-- Rating summary -->
    <section class="relative z-10 -mt-8">
      <div class="container-app">
        <div class="card grid sm:grid-cols-[auto_1fr_auto] items-center gap-6 p-6 sm:p-7" style="box-shadow: var(--s-shadow-lg)">
          <div class="flex items-center gap-4">
            <p class="text-[3rem] font-extrabold s-heading leading-none tabular-nums" style="font-family: var(--font-display); font-stretch: 110%">{{ average.toFixed(1) }}</p>
            <div>
              <Stars :value="average" size="w-5 h-5" />
              <p class="mt-1 text-[13px] s-subtle">{{ reviews.google ? `${reviews.google.total} Google reviews` : `${reviews.items.length} reviews` }}</p>
            </div>
          </div>
          <!-- Share of each star rating -->
          <ul class="space-y-1.5 sm:border-l sm:pl-6 s-border">
            <li v-for="n in [5, 4, 3, 2, 1]" :key="n" class="flex items-center gap-3 text-[12.5px]">
              <span class="w-8 s-muted tabular-nums">{{ n }} ★</span>
              <span class="flex-1 h-2 rounded-full s-surface-2 overflow-hidden"><span class="block h-full rounded-full bg-[var(--s-accent)]" :style="{ width: share(n) + '%' }"></span></span>
              <span class="w-9 text-right s-subtle tabular-nums">{{ share(n) }}%</span>
            </li>
          </ul>
          <div class="flex flex-col gap-2">
            <a v-if="reviews.google?.write_url" :href="reviews.google.write_url" target="_blank" rel="noopener" class="btn btn-primary">Write a review</a>
            <a v-if="reviews.google?.url" :href="reviews.google.url" target="_blank" rel="noopener" class="btn btn-secondary">See them on Google</a>
            <WhatsAppButton v-if="!reviews.google" green>Get a free quote</WhatsAppButton>
          </div>
        </div>
      </div>
    </section>

    <section class="section-y s-bg">
      <div class="container-app">
        <div v-if="reviews.items.length" class="columns-1 md:columns-2 lg:columns-3 gap-5">
          <div v-for="(r, i) in reviews.items.slice(0, shown)" :key="i" class="pt-3 mb-5 break-inside-avoid"><ReviewCard :review="r" /></div>
        </div>
        <div v-if="reviews.items.length > shown" class="mt-4 text-center">
          <button type="button" class="btn btn-secondary" @click="shown += 12">Show more reviews <span class="s-subtle font-normal">({{ reviews.items.length - shown }} more)</span></button>
        </div>
        <div v-else-if="!reviews.items.length" class="card p-10 text-center max-w-xl mx-auto">
          <h2 class="h-card">Reviews are on their way</h2>
          <p class="mt-2 s-muted">Worked with us recently? We would really appreciate a few words about your experience.</p>
          <a v-if="reviews.google?.write_url" :href="reviews.google.write_url" target="_blank" rel="noopener" class="btn btn-primary mt-5">Review us on Google</a>
          <Link v-else href="/contact" class="btn btn-primary mt-5">Contact us</Link>
        </div>
        <p v-if="reviews.google" class="mt-8 text-center text-[13px] s-subtle">
          Google reviews come straight from our Google Business Profile.
          <a :href="reviews.google.url" target="_blank" rel="noopener" class="link">See all {{ reviews.google.total }} on Google</a>
        </p>
      </div>
    </section>

    <CtaBand page="projects" />
  </FrontendLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import PageHero from '@/Components/Site/PageHero.vue';
import ReviewCard from '@/Components/Site/ReviewCard.vue';
import Stars from '@/Components/Site/Stars.vue';
import WhatsAppButton from '@/Components/Site/WhatsAppButton.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

const props = defineProps({ reviews: { type: Object, default: () => ({ items: [], google: null, count: 0 }) } });
const shown = ref(12);

// Google's own rating when connected, otherwise the average of the reviews shown.
const ratings = computed(() => props.reviews.items.map((r) => Number(r.rating) || 0).filter(Boolean));
const average = computed(() => props.reviews.google?.rating || (ratings.value.length ? ratings.value.reduce((a, b) => a + b, 0) / ratings.value.length : 0));
const share = (n) => (ratings.value.length ? Math.round((ratings.value.filter((r) => Math.round(r) === n).length / ratings.value.length) * 100) : 0);
</script>
