"""
Rewrite the Tasfia websites' blog articles with Claude, as drafts for review.

Reads the published articles from each site's SQLite database, puts the ones that most need work
first (thin, no headings, pasted chat leftovers, no service link), takes the sites in turns, asks
Claude to rewrite each article into a complete 1200-1500 word guide (SEO, answer engines, AI search,
E-E-A-T) and appends the result to that site's storage/rewrites/<site>-rewrites.jsonl. Nothing
changes on a website from here: the files are imported as "changes waiting for approval", and each
one goes live only when someone approves it in Admin.

Run:  python tools/rewrite_blogs.py --sites door,engineering,plumbing   (asks for the API key once; not shown or saved)
      python tools/rewrite_blogs.py --sites handyman
      add --dry-run to see what would be rewritten without calling the API

The budget (default $95) covers ALL sites together, including earlier runs; the script stops before
it. Ctrl+C stops it; running it again continues where it left off.
"""

import argparse
import getpass
import html
import json
import os
import re
import sqlite3
import sys
import time
from concurrent.futures import FIRST_COMPLETED, ThreadPoolExecutor, wait
from pathlib import Path

# The Anthropic library is installed next to this script (tools/pylib), so it works from any window.
sys.path.insert(0, str(Path(__file__).resolve().parent / "pylib"))

import anthropic  # noqa: E402

MODEL = "claude-opus-5-5"
# Claude Opus 5.5 prices, US$ per million tokens.
PRICE_IN, PRICE_OUT, PRICE_CACHE_READ, PRICE_CACHE_WRITE = 4.00, 20.00, 0.20, 5.00

COMMON = """- Office: 30 Roberts Lane, #02-01, Singapore 218309.
- Works in HDB flats, condominiums, landed homes and offices across Singapore.
- The work and the price are explained and agreed before any work starts; customers can send a photo or video on WhatsApp first for a quote."""

SITES = {
    "handyman": {
        "root": "C:/Websites/tasfiahandyman",
        "brand": "Handyman Service Singapore",
        "domain": "handymanservice-sg.com",
        "focus": "general handyman work: plumbing, doors, electrical, painting, carpentry, furniture assembly and home repairs",
        "facts": "- Phone and WhatsApp: +65 9811 2405. Email: hndymanservice.sg@gmail.com.\n- Available 24 hours, 7 days a week.\n- Technicians have 10+ years of experience with home repairs in Singapore.",
        "own_names": ["Handyman Service Singapore"],
    },
    "door": {
        "root": "C:/Websites/tasfiadoorrepair",
        "brand": "Tasfia Door Repair Singapore",
        "domain": "tasfiadoorrepairsg.com",
        "focus": "door repair: sliding, glass, wooden and wardrobe doors, hinges, locks, rollers, floor springs and door closers",
        "facts": "- Phone and WhatsApp: +65 9373 0360. Email: tasfiadoorsg@gmail.com.",
        "own_names": ["Tasfia Door Repair"],
    },
    "engineering": {
        "root": "C:/Websites/tasfiaengineering",
        "brand": "Tasfia Engineering",
        "domain": "tasfiaengineering.com",
        "focus": "renovation, repair and engineering work for homes and businesses",
        "facts": "- Company: Tasfia Engineering & Construction Pte. Ltd.\n- Phone and WhatsApp: +65 9373 0360. Email: tasfiaeng24@gmail.com.\n- Open 8am to 8pm, every day.",
        "own_names": ["Tasfia Engineering"],
    },
    "plumbing": {
        "root": "C:/Websites/tasfiaplumbing",
        "brand": "Tasfia Plumbing",
        "domain": "tasfiaplumbing.sg",
        "focus": "plumbing: leaks, pipes, taps, toilets, water heaters, choked drains and bathroom fittings",
        "facts": "- Phone and WhatsApp: +65 9373 0360. Email: tasfiaplumbing@gmail.com.",
        "own_names": ["Tasfia Plumbing"],
    },
}
OTHER_BRANDS = ["Tasfia Engineering", "Tasfia Door Repair", "Tasfia Plumbing", "Handyman Service Singapore", "Tasfia Handyman"]


def system_prompt(site):
    return f"""You rewrite blog articles for {site['brand']} ({site['domain']}), a Singapore company specialising in {site['focus']}. Each article must become the most helpful, trustworthy page on its topic for a Singapore homeowner or business, and be easy for search engines, answer engines and AI assistants to understand and quote.

Facts about the business (use only these; never invent others):
{site['facts']}
{COMMON}

Length and format
1. Aim for about 1300 words in the article HTML (the FAQs are separate and do not count): at least 1200, and never more than 1400. Count before you finish; if you are over, cut repetition. Plain, clear English for Singapore readers (HDB, condo, landed, local climate and humidity where relevant). Short paragraphs of 2 to 4 sentences. Write like an experienced local technician talking to a homeowner: everyday words, short sentences, no filler. Never use typical AI phrases such as delve, crucial, seamless, comprehensive, robust, elevate, navigate, landscape, realm, leverage, tailored, meticulous, top-notch, hassle-free, peace of mind, rest assured, look no further, in today's, it is important to note, furthermore, moreover, in conclusion; do not use em dashes; do not repeat the same phrase across sections.
2. HTML only with <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <a>, and the original <img> tags. No <h1>, inline styles, classes, tables or scripts.

SEO
3. Keep the article's topic and main keyword (taken from its title). Use the keyword naturally in the opening paragraph, in at least two <h2> headings and in the closing section. Use related terms people search for; no keyword stuffing.
4. Keep every <img> from the original exactly as it is (same src and alt), placed where it fits. Keep every link that starts with "/" (this website), with descriptive link text; link the related service page once if one is given. Do not link to other websites.
5. If the original ends with a paragraph marked data-related-service="1", keep it exactly as the last paragraph.

Answer engines (AEO)
6. Start with an opening paragraph (no heading) whose first two sentences directly answer the main question the title asks, in under 60 words.
7. Phrase most <h2> headings as the questions people ask (What causes..., How much does... depend on, When should you call...), each answered in its first sentence.

AI search (GEO)
8. Name {site['brand']} and Singapore plainly where relevant, so the page is clearly about this company in Singapore. Prefer specific, verifiable statements over vague ones.
9. Include a <h2>Key takeaways</h2> section near the end with 4 to 6 short bullet points.

E-E-A-T (experience, expertise, trust)
10. Show expertise: explain causes, how to check them safely, what a professional does and why, which parts are usually repaired or replaced, and how to prevent the problem.
11. Show trust: be honest about what a homeowner can safely do and when to call a professional; for electrical, gas, structural or height work, advise a qualified professional and never give dangerous instructions.
12. Never invent prices or price ranges, statistics, years, warranty periods, guarantees, response times, awards, reviews, customer stories or regulations with specific numbers. When cost comes up, explain what affects it and invite the reader to send a photo for a clear quote.
13. Mention {site['brand']} where it helps the reader (for example when to call a professional and in the closing section with the phone number), not in every paragraph. Never mention any other company.

Also return
14. faqs: 4 questions people really ask about this topic, each answered in 1 to 3 sentences that start with the direct answer, following the same rules.
15. excerpt: one plain-text sentence of at most 155 characters saying what the reader will learn.
16. meta_desc: plain text, 130 to 155 characters, with the main keyword near the start and a reason to read or call.
17. change_note: one short sentence in plain English saying what you improved."""


SCHEMA = {
    "type": "object",
    "properties": {
        "desc": {"type": "string"},
        "excerpt": {"type": "string"},
        "meta_desc": {"type": "string"},
        "faqs": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {"question": {"type": "string"}, "answer": {"type": "string"}},
                "required": ["question", "answer"],
                "additionalProperties": False,
            },
        },
        "change_note": {"type": "string"},
    },
    "required": ["desc", "excerpt", "meta_desc", "faqs", "change_note"],
    "additionalProperties": False,
}


def plain(text):
    return re.sub(r"\s+", " ", html.unescape(re.sub(r"<[^>]+>", " ", text or ""))).strip()


def words(text):
    return len(plain(text).split())


def need(desc, n):
    """How much an article needs work (higher first)."""
    score = 0
    score += 4 if n < 600 else 2 if n < 900 else 1 if n < 1200 else 0
    score += 2 if not re.search(r"<h2[\s>]", desc or "", re.I) else 0
    score += 1 if re.search(r'data-start=|\[object Object\]|isSelectedEnd|ng-star-inserted', desc or "") else 0
    score += 1 if (desc or "").count("style=") > 20 else 0
    score += 1 if "/service/" not in (desc or "") else 0
    return score


def cost_of(usage):
    return (
        (usage.input_tokens or 0) * PRICE_IN
        + (usage.output_tokens or 0) * PRICE_OUT
        + (getattr(usage, "cache_read_input_tokens", 0) or 0) * PRICE_CACHE_READ
        + (getattr(usage, "cache_creation_input_tokens", 0) or 0) * PRICE_CACHE_WRITE
    ) / 1_000_000


def out_path(key):
    return Path(SITES[key]["root"]) / "storage" / "rewrites" / f"{key}-rewrites.jsonl"


def rows_of(path):
    if not path.exists():
        return []
    rows = []
    for line in path.read_text(encoding="utf-8").splitlines():
        try:
            rows.append(json.loads(line))
        except ValueError:
            continue
    return rows


def articles(key):
    con = sqlite3.connect(Path(SITES[key]["root"]) / "database" / "database.sqlite")
    con.row_factory = sqlite3.Row
    rows = con.execute(
        """select b.id, b.slug, b.name, b.desc, s.name as service, s.slug as service_slug
           from blog_details b left join service_details s on s.id = b.primary_service_id
           where b.status = 'published'"""
    ).fetchall()
    con.close()
    items = []
    for r in rows:
        n = words(r["desc"])
        items.append(dict(r) | {"site": key, "words": n, "need": need(r["desc"], n)})
    return sorted(items, key=lambda a: (-a["need"], a["words"], a["id"]))


AI_WORDS = ["delve", "crucial", "seamless", "comprehensive", "robust", "elevate", "landscape", "realm",
            "leverage", "tailored", "meticulous", "top-notch", "hassle-free", "peace of mind", "rest assured",
            "look no further", "in today's", "it is important to note", "furthermore", "moreover", "in conclusion"]


def check(site, original, new):
    """Problems a reviewer should know about (empty list when fine)."""
    problems = []
    for src in re.findall(r'<img[^>]+src="([^"]+)"', original or ""):
        if src not in new:
            problems.append(f"photo missing: {src}")
    if 'data-related-service="1"' in (original or "") and 'data-related-service="1"' not in new:
        problems.append("related service paragraph missing")
    if re.search(r"(S?\$\s?\d|\d+\s?(?:SGD|dollars))", plain(new)):
        problems.append("mentions a price; check it")
    for other in OTHER_BRANDS:
        if other.lower() in new.lower() and not any(own.lower() in other.lower() or other.lower() in own.lower() for own in site["own_names"]):
            problems.append(f"mentions {other}")
    if "Key takeaways" not in new:
        problems.append("no Key takeaways section")
    found = [w for w in AI_WORDS if re.search(r"\b" + re.escape(w) + r"\b", plain(new), re.I)]
    if "—" in new:
        found.append("em dash")
    if found:
        problems.append("AI-style words: " + ", ".join(found))
    n = words(new)
    if not 1150 <= n <= 1550:
        problems.append(f"{n} words")
    return problems


def rewrite(client, article):
    site = SITES[article["site"]]
    service = f'\nRelated service page: {article["service"]} (/service/{article["service_slug"]})' if article["service"] else ""
    prompt = (
        f'Rewrite this article.\n\nTitle: {article["name"]}\nURL: /blogs/{article["slug"]}{service}\n'
        f'Current length: {article["words"]} words.\n\nCurrent article HTML:\n{article["desc"]}'
    )
    response = client.beta.messages.create(
        model=MODEL,
        max_tokens=16000,
        betas=["server-side-fallback-2026-07-01"],
        fallbacks="default",
        output_config={"effort": "medium", "format": {"type": "json_schema", "schema": SCHEMA}},
        system=[{"type": "text", "text": system_prompt(site), "cache_control": {"type": "ephemeral"}}],
        messages=[{"role": "user", "content": prompt}],
    )
    cost = cost_of(response.usage)
    if response.stop_reason == "refusal":
        return None, cost, "declined by the model"
    if response.stop_reason == "max_tokens":
        return None, cost, "answer was cut off"
    text = next((b.text for b in response.content if b.type == "text"), "")
    return json.loads(text), cost, None


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--sites", default="door,engineering,plumbing", help="comma list of: " + ", ".join(SITES))
    parser.add_argument("--budget", type=float, default=95.0, help="stop before this many US$ for all sites together (default 95)")
    parser.add_argument("--workers", type=int, default=6, help="articles written at the same time (default 6)")
    parser.add_argument("--limit", type=int, default=0, help="rewrite at most this many in this run (default: no limit)")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    keys = [k.strip() for k in args.sites.split(",") if k.strip()]
    unknown = [k for k in keys if k not in SITES]
    if unknown:
        sys.exit(f"Unknown site(s): {', '.join(unknown)}. Use: {', '.join(SITES)}")

    # What every site has done before (all sites count against the one budget).
    spent_before = sum(r.get("cost", 0) for k in SITES for r in rows_of(out_path(k)))
    queues = {}
    for k in keys:
        done = {r["id"] for r in rows_of(out_path(k))}
        queues[k] = [a for a in articles(k) if a["id"] not in done]
        print(f"{SITES[k]['brand']}: {len(done)} done before, {len(queues[k])} to go")
    # Sites take turns, each with its neediest articles first.
    order = []
    while any(queues.values()):
        for k in keys:
            if queues[k]:
                order.append(queues[k].pop(0))
    if args.limit:
        order = order[: args.limit]
    print(f"Spent before this run: ${spent_before:.2f} of the ${args.budget:.2f} budget.")

    if args.dry_run:
        for a in order[:24]:
            print(f'  [{a["site"]:11}] need {a["need"]}  {a["words"]:5} words  {a["name"][:70]}')
        return

    key = os.environ.get("ANTHROPIC_API_KEY") or getpass.getpass("Paste your Claude API key and press Enter (it is not shown): ").strip()
    if not key:
        sys.exit("No key given.")
    client = anthropic.Anthropic(api_key=key, max_retries=8, timeout=600.0)

    spent, written, failed = spent_before, 0, 0
    per_article = 0.20  # replaced by the real average as articles finish
    started = time.time()

    def work(article):
        try:
            data, cost, error = rewrite(client, article)
        except anthropic.AuthenticationError:
            return article, None, 0.0, "AUTH"
        except anthropic.APIStatusError as e:
            return article, None, 0.0, f"API error {e.status_code}: {e.message}"
        except anthropic.APIConnectionError:
            return article, None, 0.0, "network error"
        except (ValueError, KeyError) as e:
            return article, None, 0.0, f"bad answer: {e}"
        return article, data, cost, error

    queue = list(order)
    running = set()
    run_cost = 0.0
    with ThreadPoolExecutor(max_workers=args.workers) as pool:
        try:
            while queue or running:
                while queue and len(running) < args.workers and spent + (len(running) + 1) * per_article < args.budget:
                    running.add(pool.submit(work, queue.pop(0)))
                if not running:
                    break
                finished_now, running = wait(running, return_when=FIRST_COMPLETED)
                for future in finished_now:
                    article, data, cost, error = future.result()
                    spent += cost
                    run_cost += cost
                    if error == "AUTH":
                        print("\nThe API key was refused. Check it and run again.")
                        queue.clear()
                        continue
                    if error or not data:
                        failed += 1
                        print(f"  ! [{article['site']}] {article['name'][:70]}: {error}")
                        continue
                    written += 1
                    per_article = max(0.05, run_cost / max(1, written + failed))
                    site = SITES[article["site"]]
                    row = {
                        "id": article["id"],
                        "slug": article["slug"],
                        "name": article["name"],
                        "words_before": article["words"],
                        "words_after": words(data["desc"]),
                        "problems": check(site, article["desc"], data["desc"]),
                        "cost": round(cost, 4),
                        "payload": {
                            "desc": data["desc"],
                            "excerpt": data["excerpt"][:160],
                            "meta_desc": data["meta_desc"][:160],
                            "faqs": data["faqs"][:5],
                        },
                        "note": data["change_note"],
                    }
                    path = out_path(article["site"])
                    path.parent.mkdir(parents=True, exist_ok=True)
                    with path.open("a", encoding="utf-8") as f:
                        f.write(json.dumps(row, ensure_ascii=False) + "\n")
                    mins = (time.time() - started) / 60
                    print(f"  {written:4} done  ${spent:6.2f} total  {mins:5.1f} min  [{article['site']}] {article['words']}->{row['words_after']} words  {article['name'][:50]}")
        except KeyboardInterrupt:
            print("\nStopping: the articles in progress finish first...")
            queue.clear()

    print(f"\nFinished: {written} rewritten, {failed} failed, ${spent:.2f} spent in total (all runs).")


if __name__ == "__main__":
    main()
