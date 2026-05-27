#!/usr/bin/env python3
import sqlite3
from collections import defaultdict
from pathlib import Path

DB = Path(__file__).resolve().parents[1] / "data" / "leads.sqlite"
c = sqlite3.connect(DB)
c.row_factory = sqlite3.Row

print("=== CATEGORIAS ===")
cats = list(c.execute("SELECT * FROM blog_categories ORDER BY name"))
for cat in cats:
    n = c.execute(
        "SELECT COUNT(*) FROM blog_posts WHERE category_id=? AND status='published'",
        (cat["id"],),
    ).fetchone()[0]
    print(f"{cat['id']:2} | {cat['name']:32} | {cat['slug']:22} | posts={n}")

print("\n=== POSTS POR CATEGORIA ===")
rows = c.execute(
    """
    SELECT c.name, c.slug, p.slug AS post_slug, p.title
    FROM blog_posts p
    LEFT JOIN blog_categories c ON c.id = p.category_id
    WHERE p.status = 'published'
    ORDER BY c.slug, p.slug
    """
).fetchall()
by = defaultdict(list)
for r in rows:
    by[r["slug"] or "SEM_CATEGORIA"].append(r)
for slug in sorted(by.keys()):
    print(f"\n[{slug}] ({len(by[slug])})")
    for r in by[slug]:
        print(f"  - {r['post_slug']}")

total = c.execute("SELECT COUNT(*) FROM blog_posts WHERE status='published'").fetchone()[0]
print(f"\nTotal publicados: {total}")

# Expected mapping for articles 21-30 from PHP files
expected_21_30 = {
    "melhorar-pagina-produto-ecommerce": "conversao",
    "erros-checkout-ecommerce": "conversao",
    "calcular-cac-ecommerce": "ecommerce",
    "ltv-ecommerce": "ecommerce",
    "email-marketing-ecommerce": "conversao",
    "seo-ecommerce": "ecommerce",
    "aumentar-recompra-ecommerce": "estrategia",
    "frete-gratis-ecommerce-margem": "ecommerce",
    "taxa-conversao-ecommerce": "conversao",
    "metricas-ecommerce": "estrategia",
}
key_to_slug = {
    "conversao": "conversao-vendas",
    "ecommerce": "e-commerce",
    "estrategia": "estrategia-crescimento",
    "trafego": "trafego-midia",
}
print("\n=== CHECAGEM ARTIGOS 21-30 (category_key → slug esperado) ===")
slug_to_id = {cat["slug"]: cat["id"] for cat in cats}
for post_slug, key in expected_21_30.items():
    row = c.execute(
        "SELECT p.slug, c.slug AS cat_slug FROM blog_posts p "
        "LEFT JOIN blog_categories c ON c.id=p.category_id WHERE p.slug=?",
        (post_slug,),
    ).fetchone()
    exp = key_to_slug[key]
    ok = row and row["cat_slug"] == exp
    status = "OK" if ok else f"ERRO (atual: {row['cat_slug'] if row else '?'})"
    print(f"  {post_slug}: {key} → {exp} [{status}]")

c.close()
