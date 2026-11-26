from pathlib import Path
import re

p = Path(r'c:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views\goal\contacts\import.blade.php')
text = p.read_text(encoding='utf-8')

# Remove modal CSS block from comment through end of mobile modal media query
pattern = r"\n/\*\n \* Beat global tw-overlays\.css.*?\.cg-modal \.modal-body\{max-height:none\}\n\}\n"
text2, n = re.subn(pattern, "\n", text, count=1, flags=re.S)
if n != 1:
    raise SystemExit(f'modal css block not removed n={n}')
text = text2

# Clean modal rules from media queries
text = re.sub(r"\n  /\* Tablet:.*?\n  \.cg-modal .*?\n  \.cg-modal .*?\n  \.cg-modal .*?\n  \.cg-modal .*?\n  \.cg-modal .*?\n", "\n", text)
text = re.sub(r"\n/\* Desktop: Upload \| Details\+Assign \| Summary strip \*/\n@media \(min-width:1100px\)\{\n  \.cg-page\{padding:1rem 1\.25rem 2rem\}\n  \.cg-modal[\s\S]*?\n\}\n\n@media \(min-width:1400px\)\{\n  \.cg-modal[\s\S]*?\n\}\n", "\n@media (min-width:1100px){\n  .cg-page{padding:1rem 1.25rem 2rem}\n  .cg-table{min-width:0}\n}\n", text)

p.write_text(text, encoding='utf-8')
print('cleaned css; modal refs left:', text.count('cg-modal'))
