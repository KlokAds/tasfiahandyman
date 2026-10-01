<template>
  <Link :href="`/blogs/${article.slug}`" class="group flex gap-4 py-4">
    <span class="w-28 sm:w-36 aspect-[4/3] rounded-md overflow-hidden s-surface-2 shrink-0">
      <img :src="article.image ? img(article.image, 320) : '/logo.png'" :alt="article.name" class="img-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" decoding="async" width="144" height="108" />
    </span>
    <span class="min-w-0 flex flex-col">
      <span class="text-[12px] s-subtle">
        <span v-if="article.primary_service" class="s-accent font-semibold">{{ article.primary_service.name }} · </span>
        <time v-if="article.published_at" :datetime="article.published_at">{{ date(article.published_at) }}</time>
      </span>
      <span class="h-card !text-[15.5px] mt-1 line-clamp-2 group-hover:text-[var(--s-accent-text)] transition-colors">{{ cleanTitle(article.name) }}</span>
      <span v-if="article.excerpt && !compact" class="mt-1.5 text-[13.5px] s-muted line-clamp-2 leading-relaxed">{{ article.excerpt }}</span>
    </span>
  </Link>
</template>

<script setup>
import { shortDate } from '@/utils/fmt';
import { Link } from '@inertiajs/vue3';
import { img } from '@/utils/img';
import { cleanTitle } from '@/utils/cleanTitle';

// An article as a list row: thumbnail left, date + title (+ excerpt) right.
defineProps({ article: { type: Object, required: true }, compact: Boolean });
const date = d => shortDate(d);
</script>
