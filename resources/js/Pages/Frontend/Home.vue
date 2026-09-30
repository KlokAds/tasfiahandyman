<template>
  <FrontendLayout>
    <!-- ============ Hero: centred headline + "what needs fixing?" tile picker ============ -->
    <section class="relative s-dark overflow-hidden">
      <img v-if="hero.image" :src="img(hero.image, 1280)" :srcset="srcset(hero.image, 1920)" sizes="100vw" alt="" class="absolute inset-0 img-cover" fetchpriority="high" decoding="async" />
      <div class="absolute inset-0 grid-bg opacity-80"></div>
      <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(11,31,56,0.93) 0%, rgba(11,31,56,0.84) 55%, rgba(11,31,56,0.97) 100%)"></div>

      <div class="relative container-app pt-14 pb-10 lg:pt-20 text-center">
        <p v-if="hero.eyebrow" class="eyebrow !text-[#ffc21a] justify-center max-[400px]:!text-[0.64rem] max-[400px]:!tracking-[0.06em]">{{ hero.eyebrow }}</p>
        <h1 class="h-display !text-white mt-4 max-w-4xl mx-auto">{{ hero.title || `Handyman services in Singapore` }}</h1>
        <p v-if="hero.subtitle" class="mt-5 text-[1.12rem] leading-relaxed text-white/75 max-w-2xl mx-auto">{{ hero.subtitle }}</p>

        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
          <a v-if="company.tel" :href="'tel:' + tel" class="btn btn-primary btn-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="phoneIcon" /></svg>
            Call {{ company.phone }}
          </a>
          <WhatsAppButton size="lg" green>WhatsApp a photo</WhatsAppButton>
        </div>

        <ul v-if="hero.badges?.length" class="mt-6 flex flex-wrap justify-center gap-x-6 gap-y-2">
          <li v-for="b in hero.badges" :key="b" class="flex items-center gap-2 text-[14px] font-medium text-white/85">
            <svg class="w-4 h-4 text-[#ffc21a]" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            {{ b }}
          </li>
        </ul>

        <div v-if="reviews.google" class="mt-5 inline-flex items-center gap-3 text-sm text-white/80">
          <Stars :value="reviews.google.rating" />
          <span><strong class="text-white">{{ reviews.google.rating?.toFixed(1) }}</strong> from {{ reviews.google.total }} Google reviews</span>
        </div>

      </div>
      <div class="h-24 sm:h-28"></div>
    </section>

    <!-- ============ "What needs fixing?" panel overlapping the hero ============ -->
    <section v-if="tiles.length" class="relative z-10 -mt-20 sm:-mt-24">
      <div class="container-app">
        <div class="card p-5 sm:p-7" style="box-shadow: var(--s-shadow-lg)">
          <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-5">
            <div>
              <p class="eyebrow">Quick pick</p>
              <h2 class="h-card !text-[1.35rem] mt-1.5">What needs fixing?</h2>
            </div>
            <component :is="hero.btn_link && !isInternal(hero.btn_link) ? 'a' : Link" :href="hero.btn_link || '/services'" class="link text-[14px] inline-flex items-center gap-1.5">
              {{ hero.btn_name || `See all ${allServicesCount} services` }}
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
            </component>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <Link v-for="t in tiles" :key="t.href" :href="t.href" class="group rounded-lg border s-border hover:border-[var(--s-accent)] hover:bg-[var(--s-accent-soft)] px-4 py-3.5 flex items-center gap-3 transition-colors">
              <span class="w-11 h-11 rounded-md bg-[var(--s-accent-soft)] group-hover:bg-[var(--s-accent)] text-[var(--s-accent-text)] group-hover:text-white flex items-center justify-center shrink-0 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="t.icon" /></svg>
              </span>
              <span class="min-w-0">
                <span class="block text-[15px] font-bold s-heading leading-tight" style="font-family: var(--font-display)">{{ t.label }}</span>
                <span class="block text-[12.5px] s-subtle truncate">{{ t.name }}</span>
              </span>
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ Services as a menu list ============ -->
    <section v-if="services.length" class="section-y s-bg">
      <div class="container-app">
        <div class="grid lg:grid-cols-[1fr_auto] gap-6 items-end mb-10">
          <div class="max-w-2xl">
            <p class="eyebrow">Service menu</p>
            <h2 class="h-section mt-3">{{ homeStatic?.h_s_title || 'Services for Singapore homes and businesses' }}</h2>
            <p class="lead mt-3">{{ homeStatic?.h_s_subtitle || 'One experienced team for plumbing, doors, electrical, painting and carpentry, with prices you agree before we start.' }}</p>
          </div>
          <Link href="/services" class="btn btn-navy shrink-0">All {{ allServicesCount }} services</Link>
        </div>

        <div v-if="categories.length" class="flex flex-wrap gap-2 mb-6">
          <Link v-for="c in categories.filter(c => c.services_count)" :key="c.id" :href="`/services/${c.slug}`" class="chip">{{ c.name }} <span class="s-subtle">{{ c.services_count }}</span></Link>
        </div>

        <ul class="grid lg:grid-cols-2 gap-x-10 border-t-2 border-[var(--s-heading)]">
          <li v-for="(s, i) in services" :key="s.id" class="border-b s-border">
            <Link :href="`/service/${s.slug}`" class="group flex items-center gap-4 py-4">
              <span class="hidden sm:block w-7 text-[13px] font-bold s-subtle tabular-nums" style="font-family: var(--font-display)">{{ String(i + 1).padStart(2, '0') }}</span>
              <span class="w-16 h-16 rounded-md overflow-hidden s-surface-2 shrink-0">
                <img :src="s.image ? img(s.image, 160) : '/logo.png'" :alt="s.name" class="img-cover group-hover:scale-110 transition-transform duration-500" loading="lazy" decoding="async" width="64" height="64" />
              </span>
              <span class="flex-1 min-w-0">
                <span class="block h-card !text-[16px] group-hover:text-[var(--s-accent-text)] transition-colors">{{ s.name }}</span>
                <span v-if="s.short_summary" class="mt-1 text-[13.5px] s-muted line-clamp-1">{{ s.short_summary }}</span>
              </span>
              <span v-if="s.from_price" class="badge-price shrink-0 hidden sm:inline-flex"><span class="text-[11px] font-medium s-subtle">from</span> S${{ Number(s.from_price).toLocaleString() }}</span>
              <span class="w-9 h-9 rounded-full border s-border flex items-center justify-center shrink-0 group-hover:bg-[#ffc21a] group-hover:border-[#ffc21a] group-hover:text-[#0b1b30] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
              </span>
            </Link>
          </li>
        </ul>
      </div>
    </section>

    <!-- ============ Why choose us: photo with stats + numbered reasons ============ -->
    <section class="section-y s-bg-alt relative">
      <div class="absolute inset-0 pegboard pointer-events-none"></div>
      <div class="relative container-app grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <div class="relative">
          <div class="absolute -top-4 -left-4 w-28 h-28 pegboard-accent rounded-lg hidden sm:block"></div>
          <div class="relative aspect-[4/5] sm:aspect-[5/4] lg:aspect-[4/5] rounded-lg overflow-hidden shadow-xl">
            <img :src="img(whyPhoto, 960)" :srcset="srcset(whyPhoto, 1280)" sizes="(min-width: 1024px) 560px, 100vw" alt="Handyman at work in a Singapore home" class="img-cover" loading="lazy" decoding="async" />
          </div>
          <ul v-if="stats.length" class="absolute -bottom-6 right-4 sm:-right-4 grid grid-cols-2 w-[17rem] sm:w-[19rem] rounded-lg overflow-hidden s-dark shadow-2xl">
            <li v-for="(c, i) in stats" :key="c.id" :class="['px-4 py-4', i % 2 && 'border-l border-white/10', i > 1 && 'border-t border-white/10']">
              <p class="text-[1.5rem] font-extrabold text-[#ffc21a] leading-none tabular-nums" style="font-family: var(--font-display); font-stretch: 110%">{{ c.value }}</p>
              <p class="mt-1.5 text-[12px] leading-tight text-white/70">{{ c.label }}</p>
            </li>
          </ul>
        </div>

        <div class="pt-8 lg:pt-0">
          <p class="eyebrow">Why choose us</p>
          <h2 class="h-section mt-3">{{ homeStatic?.h_n_title || 'Why homeowners call us first' }}</h2>
          <p v-if="homeStatic?.h_n_subtitle" class="lead mt-3">{{ homeStatic.h_n_subtitle }}</p>
          <ol v-if="skills.length" class="mt-8 space-y-6">
            <li v-for="(k, i) in skills" :key="k.id" class="flex gap-4">
              <span class="num-badge shrink-0">{{ String(i + 1).padStart(2, '0') }}</span>
              <div>
                <h3 class="h-card !text-[1.08rem]">{{ k.s_title }}</h3>
                <p class="mt-1 text-[14.5px] s-muted leading-relaxed">{{ k.s_subtitle }}</p>
              </div>
            </li>
          </ol>
        </div>
      </div>
    </section>

    <!-- ============ How it works ============ -->
    <section v-if="steps.length" class="section-y s-bg">
      <div class="container-app">
        <div class="max-w-2xl">
          <p class="eyebrow">How it works</p>
          <h2 class="h-section mt-3">{{ texts.steps_title }}</h2>
        </div>
        <div class="relative mt-12">
          <div class="hidden md:block absolute left-0 right-0 top-[1.3rem] h-3 tape-rule" aria-hidden="true"></div>
          <ol class="relative grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-6">
            <li v-for="(step, i) in steps" :key="step.title" class="flex md:block gap-4">
              <span class="num-badge relative shrink-0 ring-8 ring-[var(--s-bg)]">{{ String(i + 1).padStart(2, '0') }}</span>
              <div class="md:mt-5 md:pr-6">
                <h3 class="h-card !text-[1.15rem]">{{ step.title }}</h3>
                <p class="mt-2 text-[14.5px] s-muted leading-relaxed">{{ step.text }}</p>
              </div>
            </li>
          </ol>
        </div>
      </div>
    </section>

    <!-- ============ Quote: contact panel + form ============ -->
    <section id="quote" class="pb-16 lg:pb-24 s-bg">
      <div class="container-app">
        <div class="grid lg:grid-cols-[0.9fr_1.1fr] rounded-xl overflow-hidden border s-border" style="box-shadow: var(--s-shadow-lg)">
          <div class="s-dark relative p-7 sm:p-10">
            <div class="absolute inset-0 grid-bg opacity-70"></div>
            <div class="relative">
              <p class="eyebrow !text-[#ffc21a]">Free quote</p>
              <h2 class="h-section !text-white mt-3">{{ homeStatic?.h_n_c_title || 'Need a handyman today?' }}</h2>
              <p class="mt-3 text-white/70 leading-relaxed">{{ homeStatic?.h_n_c_desc || texts.cta_text }}</p>
              <ul class="mt-8 space-y-4 text-[15px]">
                <li v-if="company.tel">
                  <a :href="'tel:' + tel" class="flex items-center gap-3 font-bold text-white hover:text-[#ffc21a]">
                    <span class="w-10 h-10 rounded-md bg-white/10 text-[#ffc21a] flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="phoneIcon" /></svg></span>
                    {{ company.phone }}
                  </a>
                </li>
                <li v-if="company.email">
                  <a :href="'mailto:' + company.email" class="flex items-center gap-3 text-white/80 hover:text-white break-all">
                    <span class="w-10 h-10 rounded-md bg-white/10 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-[#ffc21a]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.9 5.3a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></span>
                    {{ company.email }}
                  </a>
                </li>
                <li v-if="company.hours" class="flex items-center gap-3 text-white/80">
                  <span class="w-10 h-10 rounded-md bg-white/10 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-[#ffc21a]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span>
                  Open {{ company.hours }}
                </li>
              </ul>
              <WhatsAppButton class="mt-8" green>Send photos on WhatsApp</WhatsAppButton>
            </div>
          </div>
          <div class="s-surface p-7 sm:p-10">
            <template v-if="hero.show_quote_form">
              <h3 class="h-card !text-[1.25rem]">What needs fixing?</h3>
              <p v-if="texts.quote_intro" class="text-[13.5px] s-subtle mt-1 mb-6">{{ texts.quote_intro }}</p>
              <QuoteForm :services="serviceOptions" subject="Quote request · Homepage" id-prefix="home" />
            </template>
            <div v-else class="h-full flex flex-col justify-center">
              <h3 class="h-card !text-[1.25rem]">Tell us about the job</h3>
              <p class="mt-2 s-muted">Send a photo or a short description and we reply with a clear price range.</p>
              <Link href="/contact" class="btn btn-primary mt-6 self-start">Contact us</Link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ Projects: photo wall ============ -->
    <section v-if="projects.length" class="section-y s-bg-alt">
      <div class="container-app">
        <div class="grid lg:grid-cols-[1fr_auto] gap-6 items-end mb-10">
          <div class="max-w-2xl">
            <p class="eyebrow">Recent work</p>
            <h2 class="h-section mt-3">{{ homeStatic?.h_p_title || 'Recent projects' }}</h2>
            <p v-if="homeStatic?.h_p_subtitle" class="lead mt-3">{{ homeStatic.h_p_subtitle }}</p>
          </div>
          <Link href="/projects" class="btn btn-navy shrink-0">See all {{ projectsCount || '' }} projects</Link>
        </div>
        <!-- One large photo + four small, all cropped to the same shape so the grid stays even -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <Link v-for="(p, i) in gallery" :key="p.id" href="/projects"
            :class="['group relative block rounded-lg overflow-hidden s-surface-2', i === 0 ? 'col-span-2 lg:row-span-2 aspect-[4/3] lg:aspect-auto' : 'aspect-[4/3]']">
            <img :src="img(p.image, i === 0 ? 960 : 480)" :srcset="srcset(p.image, i === 0 ? 1280 : 720)" :sizes="i === 0 ? '(min-width: 1024px) 620px, 100vw' : '(min-width: 1024px) 300px, 50vw'" :alt="p.name"
              class="absolute inset-0 img-cover group-hover:scale-[1.04] transition-transform duration-500" loading="lazy" decoding="async" />
            <span class="absolute inset-0 bg-gradient-to-t from-[#0b1f38]/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></span>
            <span v-if="p.service" class="absolute left-3 bottom-3 right-3 text-white text-[13px] font-semibold truncate opacity-0 group-hover:opacity-100 transition-opacity">{{ p.service.name }}</span>
            <!-- Last tile: how many more jobs there are -->
            <span v-if="i === gallery.length - 1 && moreProjects > 0" class="absolute inset-0 bg-[#0b1f38]/75 group-hover:bg-[#0b1f38]/85 flex flex-col items-center justify-center text-white transition-colors">
              <span class="text-[1.9rem] font-extrabold leading-none" style="font-family: var(--font-display); font-stretch: 110%">+{{ moreProjects }}</span>
              <span class="mt-1.5 text-[13px] font-semibold text-white/80">more jobs →</span>
            </span>
          </Link>
        </div>
      </div>
    </section>

    <!-- ============ Reviews: one featured quote + list ============ -->
    <section v-if="reviews.items.length" class="section-y s-dark relative overflow-hidden">
      <div class="absolute inset-0 grid-bg opacity-70 pointer-events-none"></div>
      <div class="relative container-app">
        <div class="grid lg:grid-cols-[1fr_auto] gap-6 items-end mb-10">
          <div class="max-w-2xl">
            <p class="eyebrow !text-[#ffc21a]">Reviews</p>
            <h2 class="h-section !text-white mt-3">{{ homeStatic?.h_test_title || 'What our customers say' }}</h2>
            <p v-if="reviews.google" class="mt-3 text-white/70 text-[1.05rem]">Rated <strong class="text-white">{{ reviews.google.rating?.toFixed(1) }} out of 5</strong> from {{ reviews.google.total }} Google reviews.</p>
          </div>
          <Link href="/reviews" class="btn btn-light shrink-0">Read all reviews</Link>
        </div>
        <div class="grid lg:grid-cols-[1.25fr_1fr] gap-6">
          <figure class="relative rounded-xl bg-white/[0.05] border border-white/10 p-8 sm:p-10">
            <span class="absolute -top-5 left-8 w-12 h-12 rounded-md bg-[#1670b3] text-white flex items-center justify-center text-[2.4rem] leading-none font-black" style="font-family: var(--font-display)" aria-hidden="true">“</span>
            <Stars :value="featured.rating" class="mt-2" />
            <blockquote class="mt-4 text-[1.2rem] sm:text-[1.35rem] leading-relaxed text-white font-medium">{{ featured.text }}</blockquote>
            <figcaption class="mt-6 flex items-center gap-3">
              <img v-if="featured.photo" :src="featured.photo" :alt="featured.name" class="w-12 h-12 rounded-full object-cover" loading="lazy" referrerpolicy="no-referrer" />
              <div>
                <p class="font-bold text-white">{{ featured.name }}</p>
                <p class="text-[13px] text-white/55">{{ [featured.job, featured.location, featured.when].filter(Boolean).join(' · ') || 'Singapore' }}</p>
              </div>
            </figcaption>
          </figure>
          <ul class="space-y-3">
            <li v-for="(r, i) in reviews.items.slice(1)" :key="i" class="rounded-xl bg-white/[0.04] border border-white/10 p-5">
              <div class="flex items-center justify-between gap-3">
                <p class="font-bold text-white text-[14.5px] truncate">{{ r.name }}</p>
                <Stars :value="r.rating" />
              </div>
              <p class="mt-2 text-[14px] text-white/70 leading-relaxed line-clamp-3">{{ r.text }}</p>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- ============ Partners ============ -->
    <section v-if="partners.length" class="py-12 s-bg border-b s-border">
      <div class="container-app">
        <p class="text-center text-[11px] font-bold uppercase tracking-[0.14em] s-subtle mb-7">Trusted by</p>
        <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-6">
          <div v-for="p in partners" :key="p.id" class="h-10 w-28 flex items-center justify-center opacity-60 hover:opacity-100 transition dark:invert dark:hue-rotate-180">
            <img :src="'/' + p.image" alt="" class="max-h-full max-w-full object-contain grayscale" loading="lazy" />
          </div>
        </div>
      </div>
    </section>

    <!-- ============ Areas ============ -->
    <section v-if="topLocations.length" class="section-y s-bg">
      <div class="container-app grid lg:grid-cols-[1fr_1.4fr] gap-10 items-start">
        <div>
          <p class="eyebrow">Areas we serve</p>
          <h2 class="h-section mt-3">{{ texts.areas_title }}</h2>
          <p v-if="texts.areas_lead" class="lead mt-3">{{ texts.areas_lead }}</p>
          <Link href="/locations" class="btn btn-secondary mt-6">All areas</Link>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link v-for="l in topLocations" :key="l.href" :href="l.href" class="chip !py-2 !px-4 !text-[14px]">
            <svg class="w-3.5 h-3.5 s-accent" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11.5A7 7 0 0112 2.5a7 7 0 017 7C19 14.8 12 21 12 21z" /></svg>
            {{ l.name }}
          </Link>
        </div>
      </div>
    </section>

    <!-- ============ Articles: featured + list ============ -->
    <section v-if="latestBlogs.length" class="section-y s-bg">
      <div class="container-app">
        <div class="grid lg:grid-cols-[1fr_auto] gap-6 items-end mb-10">
          <div class="max-w-2xl">
            <p class="eyebrow">Tips & guides</p>
            <h2 class="h-section mt-3">{{ homeStatic?.h_b_title || 'Know the price before you call' }}</h2>
            <p v-if="homeStatic?.h_b_subtitle" class="lead mt-3">{{ homeStatic.h_b_subtitle }}</p>
          </div>
          <Link href="/blogs" class="btn btn-navy shrink-0">All articles</Link>
        </div>
        <div class="grid lg:grid-cols-[1.2fr_1fr] gap-8">
          <Link :href="`/blogs/${latestBlogs[0].slug}`" class="group block">
            <div class="aspect-[16/9] rounded-lg overflow-hidden s-surface-2">
              <img :src="latestBlogs[0].image ? img(latestBlogs[0].image, 960) : '/logo.png'" :alt="latestBlogs[0].name" class="img-cover group-hover:scale-[1.03] transition-transform duration-500" loading="lazy" decoding="async" />
            </div>
            <p class="mt-4 text-[12.5px] s-subtle">{{ date(latestBlogs[0].published_at) }}</p>
            <h3 class="h-section !text-[1.5rem] mt-1 group-hover:text-[var(--s-accent-text)] transition-colors">{{ latestBlogs[0].name }}</h3>
            <p v-if="latestBlogs[0].excerpt" class="mt-2 s-muted line-clamp-2">{{ latestBlogs[0].excerpt }}</p>
          </Link>
          <ul class="divide-y s-divide border-y s-border">
            <li v-for="b in latestBlogs.slice(1)" :key="b.id">
              <Link :href="`/blogs/${b.slug}`" class="group flex gap-4 py-4">
                <span class="w-28 h-20 rounded-md overflow-hidden s-surface-2 shrink-0">
                  <img :src="b.image ? img(b.image, 240) : '/logo.png'" :alt="b.name" class="img-cover" loading="lazy" decoding="async" />
                </span>
                <span class="min-w-0">
                  <span class="block text-[12px] s-subtle">{{ date(b.published_at) }}</span>
                  <span class="h-card !text-[15px] mt-1 line-clamp-2 group-hover:text-[var(--s-accent-text)] transition-colors">{{ b.name }}</span>
                </span>
              </Link>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <CtaBand />
  </FrontendLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import QuoteForm from '@/Components/Site/QuoteForm.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';
import Stars from '@/Components/Site/Stars.vue';
import WhatsAppButton from '@/Components/Site/WhatsAppButton.vue';
import { img, srcset } from '@/utils/img';
import { statsFrom } from '@/utils/stats';

const props = defineProps({
  hero: { type: Object, default: () => ({}) },
  homeStatic: Object,
  services: { type: Array, default: () => [] },
  allServicesCount: Number,
  serviceOptions: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
  projectsCount: { type: Number, default: 0 },
  counters: { type: Array, default: () => [] },
  reviews: { type: Object, default: () => ({ items: [], google: null }) },
  skills: { type: Array, default: () => [] },
  latestBlogs: { type: Array, default: () => [] },
  partners: { type: Array, default: () => [] },
});

const stats = computed(() => statsFrom(props.counters));
// Completed work: 1 large + 4 small; the last tile shows how many more there are.
const gallery = computed(() => props.projects.filter((p) => p.image).slice(0, 5));
const moreProjects = computed(() => Math.max(0, props.projectsCount - gallery.value.length + 1));
const featured = computed(() => props.reviews.items[0] || {});
const whyPhoto = computed(() => props.projects[1]?.image || props.projects[0]?.image || props.hero.image || '/logo.png');

const page = usePage();
const company = computed(() => page.props.company || {});
const tel = computed(() => company.value.tel || '');
const topLocations = computed(() => page.props.topLocations || []);
const isInternal = href => !href || href.startsWith('/');
const date = d => (d ? new Date(d).toLocaleDateString('en-SG', { day: 'numeric', month: 'short', year: 'numeric' }) : '');

const texts = computed(() => page.props.company?.texts || {});
// Admin → Website text → Homepage: How it works (a step without a title is hidden).
const steps = computed(() => [1, 2, 3].map((n) => ({ title: texts.value[`step${n}_title`], text: texts.value[`step${n}_text`] })).filter((s) => s.title));

const phoneIcon = 'M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68l1.5 4.5a1 1 0 01-.5 1.2l-2.26 1.13a11 11 0 005.52 5.52l1.13-2.26a1 1 0 011.2-.5l4.5 1.5a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.7 21 3 14.3 3 6V5z';

// "What needs fixing?" tiles: one live service per trade, matched by name.
const trades = [
  { label: 'Plumbing', prefer: /^plumbing/i, re: /plumb|sink|tap|toilet|heater|bathtub|pipe/i, icon: 'M12 3s6 6.5 6 11a6 6 0 01-12 0c0-4.5 6-11 6-11z' },
  { label: 'Electrical', prefer: /^electric/i, re: /electric|wiring|light|power/i, icon: 'M13 2L4 14h7l-1 8 9-12h-7l1-8z' },
  { label: 'Doors', prefer: /^door repair/i, re: /door|hinge|roller|frame/i, icon: 'M6 21V4a1 1 0 011-1h10a1 1 0 011 1v17M4 21h16M14 12h.01' },
  { label: 'Painting', prefer: /^painting/i, re: /paint|plaster/i, icon: 'M4 4h13v5H4zM17 6.5h2.5V12H11v3M10 15h2v6h-2z' },
  { label: 'Carpentry', prefer: /^furniture/i, re: /cabinet|drawer|furniture|wardrobe|wood/i, icon: 'M4 4h16v16H4zM4 12h16M10 8h4M10 16h4' },
  { label: 'Locksmith', prefer: /^locksmith/i, re: /lock/i, icon: 'M6 11h12v10H6zM8 11V7a4 4 0 118 0v4' },
  { label: 'Aircon', prefer: /^aircon/i, re: /aircon|air-con|air con/i, icon: 'M12 2v20M4.9 7l14.2 10M4.9 17L19.1 7M9 4l3 2 3-2M9 20l3-2 3 2' },
  { label: 'Tiling', prefer: /^tile/i, re: /tile|tiling|silicon/i, icon: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z' },
];
const tiles = computed(() => {
  const list = props.serviceOptions.filter((s) => s.slug);
  const used = new Set();
  return trades.map((t) => {
    const free = list.filter((x) => !used.has(x.id));
    const s = free.find((x) => t.prefer.test(x.name)) || free.find((x) => t.re.test(x.name));
    if (!s) return null;
    used.add(s.id);
    return { label: t.label, name: s.name, icon: t.icon, href: `/service/${s.slug}` };
  }).filter(Boolean);
});
</script>
