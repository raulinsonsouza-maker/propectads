#!/usr/bin/env python3
"""Remove todos os links internos /blog/ dos artigos 21-30 (mantém texto âncora)."""
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
FILES = [
    ROOT / "scripts/blog-articles/articles-21-25.php",
    ROOT / "scripts/blog-articles/articles-26-30.php",
]
LINK_RE = re.compile(r'<a\s+href="/blog/[a-z0-9-]+/">([^<]+)</a>', re.IGNORECASE)
PHP = Path(
    r"C:\Users\Raulinson\AppData\Local\Microsoft\WinGet\Packages"
    r"\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
)


def main() -> None:
    total = 0
    for path in FILES:
        text = path.read_text(encoding="utf-8")
        new, n = LINK_RE.subn(r"\1", text)
        if n:
            path.write_text(new, encoding="utf-8")
            print(f"{path.name}: {n} links removidos")
            total += n
    print(f"Total: {total}")

    if PHP.is_file():
        subprocess.run(
            [str(PHP), str(ROOT / "scripts/sync-blog-articles-from-php.php"), "21-30"],
            cwd=ROOT,
            check=True,
        )
    else:
        print("Rode: php scripts/sync-blog-articles-from-php.php 21-30")


if __name__ == "__main__":
    main()
