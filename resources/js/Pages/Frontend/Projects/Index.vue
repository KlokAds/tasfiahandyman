<template>
  <FrontendLayout>
    <PageHero :title="breadcrumb?.p_bread_name || 'Recent projects'" eyebrow="Our work" lead="Real jobs from Singapore homes and businesses. Tap a photo to see it full size."
      :image="breadcrumb?.p_bread_image ? '/' + breadcrumb.p_bread_image : null" :crumbs="[{ label: 'Projects' }]">
      <template #below>
        <form @submit.prevent="search" class="mt-7 max-w-lg" role="search">
          <label for="proj-q" class="sr-only">Search projects</label>
          <div class="relative">
            <input id="proj-q" v-model="q" type="search" placeholder="Search projects, e.g. kitchen, door, HDB…" class="input input-dark !py-3 !pl-11 !text-[15px]" />
            <svg class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-5.2-5.2M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
          </div>
        </form>
      </template>
    </PageHero>

    <section class="section-y s-bg">
      <div class="container-app">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
          <p class="text-[15px] s-muted"><strong class="s-heading text-[1.4rem] mr-1" style="font-family: var(--font-display)">{{ projects.total }}</strong> {{ filters.q ? `result(s) for “${filters.q}”` : 'completed jobs' }}
            <Link v-if="filters.q || filters.service" href="/projects" class="link ml-2">Clear</Link>
          </p>
          <div v-if="services.length" class="flex gap-2 overflow-x-auto pb-1" style="scrollbar-width: none">
            <Link href="/projects" :class="['chip shrink-0', !filters.service && 'chip-on']" preserve-scroll>All</Link>
            <Link v-for="s in services" :key="s.id" :href="`/projects?service=${s.slug}`" :class="['chip shrink-0', filters.service === s.slug && 'chip-on']" preserve-scroll>{{ s.name }}</Link>
          </div>
        </div>

        <div v-if="projects.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
          <ProjectCard v-for="p in projects.data" :key="p.id" :project="p" />
        </div>
        <p v-else class="s-muted">No projects to show yet.</p>

        <SitePagination :meta="projects" />
      </div>
    </section>

    <CtaBand page="projects" />
  </FrontendLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import PageHero from '@/Components/Site/PageHero.vue';
import ProjectCard from '@/Components/Site/ProjectCard.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

const props = defineProps({ projects: Object, services: { type: Array, default: () => [] }, filters: { type: Object, default: () => ({}) }, breadcrumb: Object });
const q = ref(props.filters?.q || '');
function search() {
  router.get('/projects', { ...(q.value.trim() ? { q: q.value.trim() } : {}), ...(props.filters.service ? { service: props.filters.service } : {}) }, { preserveState: true, preserveScroll: true });
}
</script>
