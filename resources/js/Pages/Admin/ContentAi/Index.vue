<template>
  <AdminLayout title="Content AI">
    <PageHeader title="Content AI" description="Photos of a finished job and a short note become a draft article and social media captions. Nothing is published by itself: the article waits in Articles as a draft for you to check and publish." />

    <p v-if="!hasKey" class="a-alert a-alert-warning text-sm mb-5">No Anthropic API key is saved yet. Add it in <Link href="/admin/system/settings?tab=keys" class="underline font-semibold">System → Settings → API keys</Link>.</p>

    <section class="admin-card p-4 sm:p-5 mb-6">
      <form @submit.prevent="submit" class="grid gap-4">
        <div class="grid sm:grid-cols-2 gap-4">
          <label class="text-sm font-semibold">Service
            <select v-model="form.service_id" class="admin-input mt-1.5 block w-full">
              <option :value="null">Let the AI work it out from the photos</option>
              <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </label>
          <label class="text-sm font-semibold">Area (optional)
            <input v-model="form.area" type="text" maxlength="100" placeholder="e.g. Tampines, HDB 4-room" class="admin-input mt-1.5 block w-full" />
          </label>
        </div>
        <label class="text-sm font-semibold">What was the problem and what did you do?
          <textarea v-model="form.note" rows="4" maxlength="3000" required class="admin-input mt-1.5 block w-full" placeholder="e.g. Kitchen sliding door was hard to push and jumped off the track. Both rollers were worn flat. Replaced 2 rollers and cleaned the track, took about 1 hour."></textarea>
        </label>
        <p class="text-[13px] a-muted -mt-2">Only facts you write here or that show in the photos are used. Prices come only from the approved price list.</p>

        <div>
          <p class="text-sm font-semibold mb-1.5">Photos and videos <span class="a-muted font-normal">(up to {{ maxPhotos }} pictures; 3 pictures are taken from each video)</span></p>
          <label class="admin-btn-secondary cursor-pointer inline-flex">
            <input type="file" accept="image/*,video/*" multiple class="sr-only" @change="addFiles" :disabled="busy || preparing" />
            Add photos or videos
          </label>
          <span v-if="preparing" class="ml-3 text-sm a-muted">Preparing pictures…</span>
          <div v-if="pictures.length" class="mt-3 grid grid-cols-3 sm:grid-cols-6 gap-2">
            <div v-for="(p, i) in pictures" :key="p.url" class="relative">
              <img :src="p.url" alt="" class="w-full aspect-square object-cover rounded-lg border a-border" />
              <span v-if="p.fromVideo" class="absolute left-1 bottom-1 text-[11px] px-1.5 rounded bg-black/70 text-white">video</span>
              <button type="button" @click="removePicture(i)" class="absolute right-1 top-1 w-7 h-7 rounded-full bg-black/70 text-white text-sm leading-none" :aria-label="`Remove picture ${i + 1}`">×</button>
            </div>
          </div>
          <p class="text-[13px] a-muted mt-2">Pictures are made smaller and their location data is removed before upload. Hide faces, unit numbers and addresses before you publish.</p>
          <p v-if="form.errors.photos || form.errors['photos.0']" class="text-sm a-danger mt-1">{{ form.errors.photos || form.errors['photos.0'] }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <button type="submit" class="admin-btn-primary" :disabled="busy || preparing || !pictures.length || !hasKey || usedToday >= dailyLimit">
            {{ busy ? 'Writing… (about a minute)' : 'Write the draft' }}
          </button>
          <span class="text-[13px] a-muted">{{ usedToday }} of {{ dailyLimit }} drafts today · AI cost this month ${{ monthCost.toFixed(2) }}</span>
        </div>
        <p v-if="form.errors.note" class="text-sm a-danger">{{ form.errors.note }}</p>
      </form>
    </section>

    <section class="grid gap-4">
      <p v-if="!jobs.length" class="text-sm a-muted">No jobs yet.</p>
      <article v-for="j in jobs" :key="j.id" class="admin-card p-4 sm:p-5">
        <header class="flex flex-wrap items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="text-[13px] a-muted">{{ when(j.created_at) }}<span v-if="j.service"> · {{ j.service }}</span><span v-if="j.area"> · {{ j.area }}</span> · ${{ j.cost.toFixed(3) }}</p>
            <h3 v-if="j.blog" class="font-semibold mt-0.5">{{ j.blog.name }}</h3>
            <p v-else class="font-semibold mt-0.5 a-danger">Not written: {{ j.error }}</p>
          </div>
          <Link v-if="j.blog" :href="`/admin/blogs?tab=mine&edit=${j.blog.id}`" class="admin-btn-primary a-btn-sm">
            {{ j.blog.status === 'published' ? 'Open article' : 'Check and publish' }}
          </Link>
        </header>

        <div v-if="j.checks?.privacy?.length" class="a-alert a-alert-warning text-sm mt-3">
          <div><p class="font-semibold">Hide before publishing:</p><ul class="list-disc pl-5"><li v-for="w in j.checks.privacy" :key="w">{{ w }}</li></ul></div>
        </div>
        <div v-if="j.checks?.questions?.length" class="a-alert a-alert-info text-sm mt-3">
          <div><p class="font-semibold">Add these facts if you know them:</p><ul class="list-disc pl-5"><li v-for="q in j.checks.questions" :key="q">{{ q }}</li></ul></div>
        </div>

        <div v-if="j.social" class="grid md:grid-cols-3 gap-3 mt-4">
          <div v-for="(label, key) in networks" :key="key" class="rounded-lg border a-border p-3 flex flex-col">
            <div class="flex items-center justify-between gap-2 mb-1.5">
              <p class="text-sm font-semibold">{{ label }}</p>
              <button type="button" class="a-btn-ghost a-btn-sm" @click="copy(j.social[key], j.id + key)">{{ copied === j.id + key ? 'Copied' : 'Copy' }}</button>
            </div>
            <p class="text-[13.5px] whitespace-pre-line leading-relaxed">{{ j.social[key] }}</p>
          </div>
        </div>
      </article>
    </section>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';

const props = defineProps({
  services: Array,
  jobs: Array,
  hasKey: Boolean,
  usedToday: Number,
  dailyLimit: Number,
  maxPhotos: Number,
  monthCost: Number,
});

const networks = { facebook: 'Facebook', instagram: 'Instagram', tiktok: 'TikTok' };
const form = useForm({ service_id: null, area: '', note: '', photos: [] });
const pictures = ref([]); // { blob, url, fromVideo }
const preparing = ref(false);
const busy = ref(false);
const copied = ref(null);

const MAX_SIDE = 1600;

/** Draws an image or video frame on a canvas: smaller, and without the camera's location data. */
function toJpeg(source, w, h) {
  const scale = Math.min(1, MAX_SIDE / Math.max(w, h));
  const canvas = document.createElement('canvas');
  canvas.width = Math.round(w * scale);
  canvas.height = Math.round(h * scale);
  canvas.getContext('2d').drawImage(source, 0, 0, canvas.width, canvas.height);
  return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
}

async function fromImage(file) {
  const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
  const blob = await toJpeg(bitmap, bitmap.width, bitmap.height);
  bitmap.close?.();
  return [blob];
}

/** Three pictures from a video (at 20%, 50% and 80%), since the AI reads pictures, not video. */
function fromVideo(file) {
  return new Promise((resolve) => {
    const video = document.createElement('video');
    const url = URL.createObjectURL(file);
    const out = [];
    const marks = [0.2, 0.5, 0.8];
    const done = () => { URL.revokeObjectURL(url); resolve(out); };
    video.muted = true;
    video.playsInline = true;
    video.preload = 'auto';
    video.onerror = done;
    video.onloadedmetadata = () => { video.currentTime = video.duration * marks[0]; };
    video.onseeked = async () => {
      out.push(await toJpeg(video, video.videoWidth, video.videoHeight));
      if (out.length < marks.length) video.currentTime = video.duration * marks[out.length];
      else done();
    };
    video.src = url;
  });
}

async function addFiles(e) {
  const files = [...e.target.files];
  e.target.value = '';
  preparing.value = true;
  try {
    for (const file of files) {
      if (pictures.value.length >= props.maxPhotos) break;
      const isVideo = file.type.startsWith('video/');
      const blobs = await (isVideo ? fromVideo(file) : fromImage(file)).catch(() => []);
      for (const blob of blobs.filter(Boolean)) {
        if (pictures.value.length >= props.maxPhotos) break;
        pictures.value.push({ blob, url: URL.createObjectURL(blob), fromVideo: isVideo });
      }
    }
  } finally {
    preparing.value = false;
  }
}

function removePicture(i) {
  URL.revokeObjectURL(pictures.value[i].url);
  pictures.value.splice(i, 1);
}

function submit() {
  form.photos = pictures.value.map((p, i) => new File([p.blob], `photo-${i + 1}.jpg`, { type: 'image/jpeg' }));
  busy.value = true;
  form.post('/admin/content-ai', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      pictures.value.forEach((p) => URL.revokeObjectURL(p.url));
      pictures.value = [];
      form.reset();
    },
    onFinish: () => { busy.value = false; },
  });
}

async function copy(text, key) {
  try {
    await navigator.clipboard.writeText(text);
    copied.value = key;
    setTimeout(() => { if (copied.value === key) copied.value = null; }, 2000);
  } catch (e) {
    // Clipboard blocked: the text can still be selected by hand.
  }
}

const when = (iso) => new Date(iso).toLocaleString('en-SG', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
</script>
