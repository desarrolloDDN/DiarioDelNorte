#!/usr/bin/env python3
"""Genera theme/screenshot.png (1200x900) para Diario del Norte."""
from PIL import Image, ImageDraw, ImageFont

W, H = 1200, 900
RED = (191, 2, 2)
INK = (17, 17, 17)
HEAD = (26, 26, 26)
SLATE = (110, 110, 110)
SAND = (185, 176, 160)
SAND2 = (176, 182, 165)
RULE = (224, 224, 224)
BODY = (206, 206, 206)
PAPER = (255, 255, 255)

import os
HERE = os.path.dirname(os.path.abspath(__file__))
FONTS = "/System/Library/Fonts/Supplemental"
ARI = f"{FONTS}/Arial.ttf"
ARIB = f"{FONTS}/Arial Bold.ttf"
GEOB = f"{FONTS}/Georgia Bold.ttf"
LOGO = f"{HERE}/../../theme/assets/img/logo.png"
OUT = f"{HERE}/../../theme/screenshot.png"

img = Image.new("RGB", (W, H), PAPER)
d = ImageDraw.Draw(img)


def tracked(draw, xy, text, font, fill, spacing=2, center_x=None, measure=False):
    ws = [draw.textlength(ch, font=font) + spacing for ch in text]
    total = sum(ws) - spacing
    if measure:
        return total
    x, y = xy
    if center_x is not None:
        x = center_x - total / 2
    for ch, w in zip(text, ws):
        draw.text((x, y), ch, font=font, fill=fill)
        x += w
    return total


def wrap(draw, text, font, max_w):
    words, lines, cur = text.split(), [], ""
    for wd in words:
        t = (cur + " " + wd).strip()
        if draw.textlength(t, font=font) <= max_w:
            cur = t
        else:
            lines.append(cur)
            cur = wd
    if cur:
        lines.append(cur)
    return lines


# filete negro superior
d.rectangle([0, 0, W, 3], fill=INK)

# logotipo
logo = Image.open(LOGO).convert("RGBA")
lw = 540
lh = round(logo.height * lw / logo.width)
logo = logo.resize((lw, lh), Image.LANCZOS)
img.paste(logo, ((W - lw) // 2, 58), logo)

# fecha
tracked(d, (0, 58 + lh + 24), "RIOHACHA · 6 DE SEPTIEMBRE DE 2026",
        ImageFont.truetype(ARI, 15), SLATE, spacing=3, center_x=W / 2)

# barra roja del menú
MX = 70
nav_y = 58 + lh + 72
nav_h = 54
d.rectangle([MX, nav_y, W - MX, nav_y + nav_h], fill=RED)
f_nav = ImageFont.truetype(ARIB, 13)
items = ["LA GUAJIRA", "POLÍTICA", "JUDICIALES", "CARIBE", "NACIÓN", "MUNDO",
         "OPINIÓN", "EDITORIAL", "SOCIALES", "MÁS"]
gap = 22
seg = [tracked(d, (0, 0), it, f_nav, RED, spacing=1, measure=True) for it in items]
x = W / 2 - (sum(seg) + gap * (len(items) - 1)) / 2
for it, sw in zip(items, seg):
    tracked(d, (x, nav_y + (nav_h - 13) / 2 - 1), it, f_nav, PAPER, spacing=1)
    x += sw + gap

# apertura
top = nav_y + nav_h + 40
cw = W - MX * 2
left_w = round(cw * 0.62)
gutter = 34
rx = MX + left_w + gutter
rw = cw - left_w - gutter

# imagen destacada
ih = round(left_w * 10 / 16)
d.rectangle([MX, top, MX + left_w, top + ih], fill=SAND)
f_tag = ImageFont.truetype(ARIB, 13)
tw = d.textlength("DESTACADO", font=f_tag) + 22
d.rectangle([MX + 20, top + 20, MX + 20 + tw, top + 48], fill=RED)
d.text((MX + 31, top + 26), "DESTACADO", font=f_tag, fill=PAPER)

# titular + resumen del destacado
f_h1 = ImageFont.truetype(GEOB, 27)
f_body = ImageFont.truetype(ARI, 14)
ty = top + ih + 20
for ln in wrap(d, "Junior anuncia a Sebastián Viera como nuevo director técnico", f_h1, left_w):
    d.text((MX, ty), ln, font=f_h1, fill=HEAD)
    ty += 34
ty += 6
d.line([MX, ty, MX + left_w, ty], fill=RULE, width=1)
ty += 16
for w in (left_w - 6, left_w - 40, left_w - 210):
    d.rounded_rectangle([MX, ty, MX + w, ty + 10], radius=3, fill=BODY)
    ty += 18

# columna derecha
f_kick = ImageFont.truetype(ARIB, 12)
f_h2 = ImageFont.truetype(GEOB, 17)
notes = [("JUDICIALES", "Envían a la cárcel a alias ‘La Máquina’ por homicidio en Riohacha", SAND),
         ("CARIBE", "Comerciantes de San Andrés advierten por la caída del turismo", SAND2)]
ry = top
for kick, head, col in notes:
    d.rectangle([rx, ry, rx + rw, ry + round(rw * 2 / 3)], fill=col)
    by = ry + round(rw * 2 / 3) + 14
    tracked(d, (rx, by), kick, f_kick, RED, spacing=1)
    by += 22
    for ln in wrap(d, head, f_h2, rw):
        d.text((rx, by), ln, font=f_h2, fill=HEAD)
        by += 22
    ry = by + 24

img.save(OUT, "PNG")
print("wrote", OUT, img.size)
