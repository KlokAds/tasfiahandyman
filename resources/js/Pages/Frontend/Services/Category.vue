<template>
  <FrontendLayout>
    <PageHero :title="category.name" eyebrow="Services" :lead="category.intro" :image="category.image ? '/' + category.image : null"
      :crumbs="[{ label: 'Services', href: '/services' }, { label: category.name }]" />

    <section class="section-y s-bg">
      <div class="container-app grid lg:grid-cols-[minmax(0,1fr)_20rem] gap-10 lg:gap-14 items-start">
        <div class="min-w-0">
          <h2 class="h-section !text-[1.7rem] pb-4 border-b-2 border-[var(--s-heading)]">{{ services.length }} {{ services.length === 1 ? 'service' : 'services' }}</h2>
          <ul v-if="services.length" class="grid md:grid-cols-2 gap-x-10">
            <li v-for="s in services" :key="s.id" class="border-b s-border"><ServiceRow :service="s" /></li>
          </ul>
          <p v-else class="mt-6 s-muted">No services in this category yet.</p>

          <div v-if="category.description" class="prose-site mt-12" v-html="category.description"></div>
        </div>

        <aside class="lg:sticky lg:top-28 space-y-6">
          <div class="rounded-lg s-dark p-6">
            <p class="h-card !text-white">Need help with {{ category.name.toLowerCase() }}?</p>
            <p class="mt-2 text-[14px] text-white/70 leading-relaxed">Send a photo of the problem and get a clear price range, usually the same day.</p>
            <WhatsAppButton green class="mt-4 w-full" :topic="category.name">WhatsApp us</WhatsAppButton>
          </div>
          <div v-if="articles.length">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] s-subtle">Guides</p>
            <ul class="divide-y s-divide">
              <li v-for="a in articles" :key="a.id"><ArticleRow :article="a" compact /></li>
            </ul>
          </div>
        </aside>
      </div>
    </section>

    <CtaBand />
  </FrontendLayout>
</template>

<script setup>
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import PageHero from '@/Components/Site/PageHero.vue';
import ServiceRow from '@/Components/Site/ServiceRow.vue';
import ArticleRow from '@/Components/Site/ArticleRow.vue';
import WhatsAppButton from '@/Components/Site/WhatsAppButton.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

defineProps({ category: Object, services: Array, articles: Array });
</script>
