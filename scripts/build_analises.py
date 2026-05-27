# -*- coding: utf-8 -*-
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

def write(rel, content):
    path = ROOT / rel.replace("/", "\\")
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content.lstrip("\n"), encoding="utf-8")
    print("wrote", rel)

write("analises/_template/helpers.php", open(__file__).read().split("HELPERS_PHP")[1].split("END_HELPERS")[0])
