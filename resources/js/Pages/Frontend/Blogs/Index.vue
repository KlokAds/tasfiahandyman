<template>
  <FrontendLayout>
    <PageHero :title="breadcrumb?.b_bread_name || 'Guides & cost advice'" eyebrow="Tips & guides" lead="Straight answers to the questions our customers ask most: what it costs in Singapore, what causes the problem and when to call a professional."
      :image="breadcrumb?.b_bread_image ? '/' + breadcrumb.b_bread_image : null" :crumbs="[{ label: 'Articles' }]">
      <template #below>
        <form @submit.prevent="search" class="mt-7 max-w-lg" role="search">
          <label for="blog-q" class="sr-only">Search articles</label>
          <div class="relative">
            <input id="blog-q" v-model="q" type="search" :placeholder="`Search ${blogs.total} guides…`" class="input input-dark !py-3 !pl-11 !text-[15px]" />
            <svg class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-5.2-5.2M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
          </div>
        </form>
      </template>
    </PageHero>

    <section class="section-y s-bg">
      <div class="container-app">
        <div v-if="services.length" class="flex gap-2 mb-8 overflow-x-auto pb-1" style="scrollbar-width: none">
          <Link href="/blogs" :class="['chip shrink-0', !filters.service && 'chip-on']">All topics</Link>
          <Link v-for="s in services" :key="s.id" :href="`/blogs?service=${s.slug}`" :class="['chip shrink-0', filters.service === s.slug && 'chip-on']">{{ s.name }}</Link>
        </div>

        <p v-if="filters.search" class="mb-6 s-muted">{{ blogs.total }} result(s) for “{{ filters.search }}” · <Link href="/blogs" class="link">Clear</Link></p>

        <template v-if="blogs.data.length">
          <!-- First article large, the rest as a two-column list -->
          <Link v-if="lead" :href="`/blogs/${lead.slug}`" class="group grid md:grid-cols-[1.15fr_1fr] gap-6 md:gap-10 items-center pb-10 mb-2 border-b-2 border-[var(--s-heading)]">
            <div class="aspect-[16/10] rounded-lg overflow-hidden s-surface-2">
              <img :src="lead.image ? img(lead.image, 960) : '/logo.png'" :srcset="srcset(lead.image, 1280)" sizes="(min-width: 768px) 620px, 100vw" :alt="lead.name" class="img-cover group-hover:scale-[1.03] transition-transform duration-500" decoding="async" />
            </div>
            <div>
              <p class="eyebrow">Latest guide</p>
              <h2 class="h-section !text-[1.7rem] mt-3 group-hover:text-[var(--s-accent-text)] transition-colors">{{ lead.name }}</h2>
              <p v-if="lead.excerpt" class="mt-3 s-muted leading-relaxed line-clamp-3">{{ lead.excerpt }}</p>
              <p class="mt-4 text-[13px] s-subtle">{{ date(lead.published_at) }}</p>
            </div>
          </Link>
          <ul class="grid md:grid-cols-2 gap-x-10">
            <li v-for="b in rest" :key="b.id" class="border-b s-border"><ArticleRow :article="b" /></li>
          </ul>
        </template>
        <div v-else class="card p-10 text-center">
          <p class="h-card">No articles found</p>
          <p class="mt-2 s-muted">Try another word, or ask us directly.</p>
          <WhatsAppButton green class="mt-5">Ask us on WhatsApp</WhatsAppButton>
        </div>

        <SitePagination :meta="blogs" />
      </div>
    </section>
  </FrontendLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import PageHero from '@/Components/Site/PageHero.vue';
import WhatsAppButton from '@/Components/Site/WhatsAppButton.vue';
import ArticleRow from '@/Components/Site/ArticleRow.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { img, srcset } from '@/utils/img';

const props = defineProps({ blogs: Object, filters: { type: Object, default: () => ({}) }, services: { type: Array, default: () => [] }, breadcrumb: Object });
const q = ref(props.filters?.search || '');
// Only the first page gets a large lead article.
const lead = computed(() => ((props.blogs.current_page || 1) === 1 && !props.filters.search ? props.blogs.data[0] : null));
const rest = computed(() => (lead.value ? props.blogs.data.slice(1) : props.blogs.data));
const date = d => (d ? new Date(d).toLocaleDateString('en-SG', { day: 'numeric', month: 'short', year: 'numeric' }) : '');
function search() {
  router.get('/blogs', { ...(q.value ? { search: q.value } : {}), ...(props.filters.service ? { service: props.filters.service } : {}) }, { preserveState: true });
}
</script>
