<template>
  <FrontendLayout>
    <PageHero :title="breadcrumb?.s_bread_name || 'Our services'" eyebrow="Services" lead="Household repairs and installations for HDB flats, condos, landed homes and businesses across Singapore."
      :image="breadcrumb?.s_bread_image ? '/' + breadcrumb.s_bread_image : null" :crumbs="[{ label: 'Services' }]">
      <template #below>
        <!-- Search sits in the banner, so the list starts right below -->
        <div class="mt-7 max-w-lg relative">
          <label for="svc-q" class="sr-only">Find a service</label>
          <input id="svc-q" v-model="q" type="search" :placeholder="`Search ${total} services, e.g. leak, door, tiling…`" class="input input-dark !py-3 !pl-11 !text-[15px]" />
          <svg class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-5.2-5.2M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
        </div>
        <p v-if="q && !anyMatch" class="mt-3 text-[14px] text-white/75">No service matches “{{ q }}”. <Link :href="`/search?q=${encodeURIComponent(q)}`" class="underline text-white">Search the whole site</Link></p>
      </template>
    </PageHero>

    <section class="section-y s-bg">
      <div :class="['container-app grid grid-cols-1 gap-10 lg:gap-14 items-start', groups.length > 1 ? 'lg:grid-cols-[15rem_minmax(0,1fr)]' : 'lg:grid-cols-[minmax(0,1fr)_17rem]']">
        <!-- Category index (sticky on desktop, scrolling chips on phones) -->
        <nav :class="['min-w-0 lg:sticky lg:top-28', groups.length > 1 ? '' : 'order-2']" aria-label="Service categories">
          <template v-if="groups.length > 1">
            <p class="hidden lg:block text-[11px] font-bold uppercase tracking-[0.14em] s-subtle mb-3">Categories</p>
            <div class="flex lg:flex-col gap-2 lg:gap-0.5 overflow-x-auto pb-1 lg:pb-0" style="scrollbar-width: none">
              <a v-for="g in groups" v-show="filtered(g.services).length" :key="g.id" :href="`#${g.id}`"
                class="chip lg:!rounded-md lg:!border-0 lg:!bg-transparent lg:!px-3 lg:!py-2 lg:!text-[14px] lg:hover:!bg-[var(--s-surface-2)] shrink-0 lg:justify-between">
                {{ g.name }} <span class="s-subtle tabular-nums">{{ filtered(g.services).length }}</span>
              </a>
            </div>
          </template>
          <div :class="['rounded-lg s-dark p-5', groups.length > 1 ? 'hidden lg:block mt-6' : '']">
            <p class="text-[13.5px] text-white/75 leading-relaxed">Not sure what the problem is? Send us a photo and we will tell you what it needs.</p>
            <WhatsAppButton size="sm" green class="mt-3 w-full">WhatsApp us</WhatsAppButton>
            <Link v-if="$page.props.hasPrices" href="/pricing" class="mt-2 btn btn-sm btn-light w-full">See the price list</Link>
          </div>
        </nav>

        <div class="space-y-14 min-w-0">
          <div v-for="g in groups" v-show="filtered(g.services).length" :key="g.id" :id="g.id" class="scroll-mt-28">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 pb-4 border-b-2 border-[var(--s-heading)]">
              <div class="max-w-2xl">
                <h2 class="h-section !text-[1.7rem]">{{ g.name }}</h2>
                <p v-if="g.intro" class="mt-1.5 s-muted leading-relaxed">{{ g.intro }}</p>
              </div>
              <Link v-if="g.slug" :href="`/services/${g.slug}`" class="link text-sm shrink-0">About {{ g.name.toLowerCase() }} →</Link>
            </div>
            <ul class="grid md:grid-cols-2 gap-x-10">
              <li v-for="s in filtered(g.services)" :key="s.id" class="border-b s-border"><ServiceRow :service="s" /></li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <CtaBand page="services" />
  </FrontendLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import PageHero from '@/Components/Site/PageHero.vue';
import ServiceRow from '@/Components/Site/ServiceRow.vue';
import WhatsAppButton from '@/Components/Site/WhatsAppButton.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

const props = defineProps({
  categories: { type: Array, default: () => [] },
  uncategorised: { type: Array, default: () => [] },
  breadcrumb: Object,
});

// Categories plus a final group for services without one.
const groups = computed(() => [
  ...props.categories.map((c) => ({ id: c.slug, slug: c.slug, name: c.name, intro: c.intro, services: c.services })),
  ...(props.uncategorised.length ? [{ id: 'more', slug: null, name: props.categories.length ? 'More services' : 'All services', intro: null, services: props.uncategorised }] : []),
]);
const total = computed(() => groups.value.reduce((n, g) => n + g.services.length, 0));

const q = ref('');
const words = computed(() => q.value.toLowerCase().split(/\s+/).filter(Boolean));
const filtered = list => (!words.value.length ? list : list.filter(s => {
  const hay = `${s.name} ${s.short_summary || ''}`.toLowerCase();
  return words.value.every(w => hay.includes(w));
}));
const anyMatch = computed(() => groups.value.some(g => filtered(g.services).length));
</script>
