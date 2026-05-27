#!/usr/bin/env python3
"""Valida links /blog/ nos artigos 21-30 contra slugs publicados."""
import re
import sqlite3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DB = ROOT / "data" / "leads.sqlite"
ARTICLE_FILES = [
    ROOT / "scripts/blog-articles/articles-21-25.php",
    ROOT / "scripts/blog-articles/articles-26-30.php",
]

conn = sqlite3.connect(DB)
published = {r[0] for r in conn.execute("SELECT slug FROM blog_posts WHERE status = 'published'")}
conn.close()

link_re = re.compile(r'href="/blog/([a-z0-9-]+)/"')
all_links: dict[str, set[str]] = {}

for path in ARTICLE_FILES:
    text = path.read_text(encoding="utf-8")
    for slug in link_re.findall(text):
        all_links.setdefault(slug, set()).add(path.name)

broken = {s: files for s, files in sorted(all_links.items()) if s not in published}
ok = {s for s in all_links if s in published}

print(f"Publicados no banco: {len(published)}")
print(f"Links únicos nos artigos 21-30: {len(all_links)}")
print(f"OK: {len(ok)} | Quebrados: {len(broken)}")
if broken:
    print("\n--- Links quebrados ---")
    for slug, files in broken.items():
        print(f"  /blog/{slug}/  (em {', '.join(sorted(files))})")
