# -*- coding: utf-8 -*-
"""Generate SciGrade role manuals as PDF (TH Sarabun New)."""

from __future__ import annotations

import re
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
    HRFlowable,
    Image,
)
from PIL import Image as PILImage

OUT_DIR = Path(__file__).resolve().parent
FONT_DIR = Path(r"C:\Users\Administrator\AppData\Local\Microsoft\Windows\Fonts")

BROWN = colors.HexColor("#5C2E1F")
AMBER = colors.HexColor("#8B4513")
CREAM = colors.HexColor("#FDF8F3")
WARM = colors.HexColor("#F5E6D8")
MUTED = colors.HexColor("#7A4A3A")
LINE = colors.HexColor("#E0C9B4")
SOFT = colors.HexColor("#FFFCF9")


def register_fonts() -> None:
    regular = FONT_DIR / "THSarabunNew.ttf"
    bold = FONT_DIR / "THSarabunNew Bold.ttf"
    if not regular.exists():
        raise FileNotFoundError(f"TH Sarabun New not found: {regular}")
    pdfmetrics.registerFont(TTFont("THSarabunNew", str(regular)))
    pdfmetrics.registerFont(TTFont("THSarabunNew-Bold", str(bold if bold.exists() else regular)))
    pdfmetrics.registerFontFamily(
        "THSarabunNew",
        normal="THSarabunNew",
        bold="THSarabunNew-Bold",
    )


def styles():
    base = getSampleStyleSheet()
    return {
        "title": ParagraphStyle(
            "TitleTH",
            parent=base["Normal"],
            fontName="THSarabunNew-Bold",
            fontSize=22,
            leading=28,
            textColor=BROWN,
            alignment=TA_CENTER,
            spaceAfter=4,
        ),
        "subtitle": ParagraphStyle(
            "SubtitleTH",
            parent=base["Normal"],
            fontName="THSarabunNew",
            fontSize=13,
            leading=18,
            textColor=MUTED,
            alignment=TA_CENTER,
            spaceAfter=10,
        ),
        "h2": ParagraphStyle(
            "H2TH",
            parent=base["Normal"],
            fontName="THSarabunNew-Bold",
            fontSize=16,
            leading=22,
            textColor=AMBER,
            spaceBefore=10,
            spaceAfter=4,
        ),
        "h3": ParagraphStyle(
            "H3TH",
            parent=base["Normal"],
            fontName="THSarabunNew-Bold",
            fontSize=14,
            leading=20,
            textColor=BROWN,
            spaceBefore=6,
            spaceAfter=2,
        ),
        "body": ParagraphStyle(
            "BodyTH",
            parent=base["Normal"],
            fontName="THSarabunNew",
            fontSize=13,
            leading=18,
            textColor=colors.HexColor("#2D2A26"),
            alignment=TA_LEFT,
            spaceAfter=2,
        ),
        "bullet": ParagraphStyle(
            "BulletTH",
            parent=base["Normal"],
            fontName="THSarabunNew",
            fontSize=13,
            leading=18,
            textColor=colors.HexColor("#2D2A26"),
            leftIndent=12,
            spaceAfter=1,
        ),
        "cell": ParagraphStyle(
            "CellTH",
            parent=base["Normal"],
            fontName="THSarabunNew",
            fontSize=12,
            leading=16,
            textColor=colors.HexColor("#2D2A26"),
        ),
        "cell_head": ParagraphStyle(
            "CellHeadTH",
            parent=base["Normal"],
            fontName="THSarabunNew-Bold",
            fontSize=12,
            leading=16,
            textColor=BROWN,
        ),
        "footer": ParagraphStyle(
            "FooterTH",
            parent=base["Normal"],
            fontName="THSarabunNew",
            fontSize=10,
            leading=12,
            textColor=MUTED,
            alignment=TA_CENTER,
        ),
        "caption": ParagraphStyle(
            "CaptionTH",
            parent=base["Normal"],
            fontName="THSarabunNew",
            fontSize=11,
            leading=15,
            textColor=MUTED,
            alignment=TA_CENTER,
            spaceBefore=2,
            spaceAfter=8,
        ),
        "note": ParagraphStyle(
            "NoteTH",
            parent=base["Normal"],
            fontName="THSarabunNew",
            fontSize=11,
            leading=15,
            textColor=MUTED,
            leftIndent=4,
            spaceBefore=4,
            spaceAfter=4,
        ),
    }


def md_inline(text: str) -> str:
    text = text.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
    text = re.sub(r"`([^`]+)`", r'<font face="Courier" size="10">\1</font>', text)
    text = re.sub(r"\*\*([^*]+)\*\*", r"<b>\1</b>", text)
    return text


def parse_table(lines: list[str]) -> list[list[str]]:
    rows = []
    for line in lines:
        if re.match(r"^\|?\s*-+", line):
            continue
        cells = [c.strip() for c in line.strip().strip("|").split("|")]
        if cells:
            rows.append(cells)
    return rows


def table_flowable(rows: list[list[str]], s: dict):
    data = []
    for i, row in enumerate(rows):
        style = s["cell_head"] if i == 0 else s["cell"]
        data.append([Paragraph(md_inline(c), style) for c in row])

    col_count = max(len(r) for r in data)
    usable = 170 * mm
    col_w = usable / col_count
    t = Table(data, colWidths=[col_w] * col_count, hAlign="LEFT")
    t.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), WARM),
                ("BACKGROUND", (0, 1), (-1, -1), SOFT),
                ("TEXTCOLOR", (0, 0), (-1, 0), BROWN),
                ("GRID", (0, 0), (-1, -1), 0.4, LINE),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 5),
                ("RIGHTPADDING", (0, 0), (-1, -1), 5),
                ("TOPPADDING", (0, 0), (-1, -1), 4),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
            ]
        )
    )
    return t


def image_flowable(rel_path: str, base_dir: Path, max_width=165 * mm, max_height=95 * mm):
    img_path = (base_dir / rel_path).resolve()
    if not img_path.exists():
        return Paragraph(f"[ไม่พบภาพ: {rel_path}]", styles()["note"])

    with PILImage.open(img_path) as im:
        w_px, h_px = im.size

    aspect = h_px / float(w_px)
    width = max_width
    height = width * aspect
    if height > max_height:
        height = max_height
        width = height / aspect

    img = Image(str(img_path), width=width, height=height)
    img.hAlign = "CENTER"
    return img


def md_to_flowables(md_text: str, s: dict, base_dir: Path):
    lines = md_text.splitlines()
    story = []
    i = 0
    title_done = False

    while i < len(lines):
        line = lines[i].rstrip()
        if not line.strip():
            i += 1
            continue

        if line.startswith("# "):
            story.append(Paragraph(md_inline(line[2:].strip()), s["title"]))
            title_done = True
            i += 1
            continue

        if (
            title_done
            and not line.startswith("#")
            and not line.startswith("|")
            and not line.startswith("-")
            and not line.startswith("!")
            and not line.startswith(">")
            and not line.startswith("*")
            and not re.match(r"^\d+\.", line)
        ):
            story.append(Paragraph(md_inline(line.strip()), s["subtitle"]))
            story.append(HRFlowable(width="100%", thickness=1, color=LINE, spaceBefore=2, spaceAfter=6))
            title_done = False
            i += 1
            continue

        if line.startswith("## "):
            story.append(Paragraph(md_inline(line[3:].strip()), s["h2"]))
            i += 1
            continue

        if line.startswith("### "):
            story.append(Paragraph(md_inline(line[4:].strip()), s["h3"]))
            i += 1
            continue

        img_match = re.match(r"!\[([^\]]*)\]\(([^)]+)\)", line.strip())
        if img_match:
            alt, rel = img_match.group(1), img_match.group(2)
            story.append(Spacer(1, 2 * mm))
            story.append(image_flowable(rel, base_dir))
            # optional italic caption on next line
            if i + 1 < len(lines) and lines[i + 1].strip().startswith("*") and lines[i + 1].strip().endswith("*"):
                cap = lines[i + 1].strip().strip("*").strip()
                story.append(Paragraph(md_inline(cap), s["caption"]))
                i += 2
            else:
                if alt:
                    story.append(Paragraph(md_inline(alt), s["caption"]))
                i += 1
            continue

        if line.strip().startswith(">"):
            story.append(Paragraph(md_inline(line.strip().lstrip(">").strip()), s["note"]))
            i += 1
            continue

        if line.strip().startswith("|"):
            table_lines = []
            while i < len(lines) and lines[i].strip().startswith("|"):
                table_lines.append(lines[i])
                i += 1
            rows = parse_table(table_lines)
            if rows:
                story.append(Spacer(1, 2 * mm))
                story.append(table_flowable(rows, s))
                story.append(Spacer(1, 3 * mm))
            continue

        if re.match(r"^\d+\.\s+", line.strip()):
            story.append(Paragraph(md_inline(line.strip()), s["bullet"]))
            i += 1
            continue

        if line.strip().startswith("- "):
            story.append(Paragraph("• " + md_inline(line.strip()[2:]), s["bullet"]))
            i += 1
            continue

        # skip standalone italic captions already consumed with images
        if line.strip().startswith("*") and line.strip().endswith("*"):
            story.append(Paragraph(md_inline(line.strip().strip("*").strip()), s["caption"]))
            i += 1
            continue

        story.append(Paragraph(md_inline(line.strip()), s["body"]))
        i += 1

    return story


def build_pdf(md_path: Path, pdf_path: Path) -> None:
    s = styles()
    md_text = md_path.read_text(encoding="utf-8")
    story = md_to_flowables(md_text, s, md_path.parent)
    story.append(Spacer(1, 8 * mm))
    story.append(HRFlowable(width="100%", thickness=0.6, color=LINE, spaceBefore=2, spaceAfter=4))
    story.append(
        Paragraph(
            "SciGrade — คณะวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น",
            s["footer"],
        )
    )

    doc = SimpleDocTemplate(
        str(pdf_path),
        pagesize=A4,
        leftMargin=18 * mm,
        rightMargin=18 * mm,
        topMargin=16 * mm,
        bottomMargin=16 * mm,
        title=md_path.stem,
        author="SciGrade",
    )

    def on_page(canvas, doc_):
        canvas.saveState()
        canvas.setFillColor(CREAM)
        canvas.rect(0, 0, A4[0], A4[1], fill=1, stroke=0)
        canvas.setStrokeColor(AMBER)
        canvas.setLineWidth(3)
        canvas.line(0, A4[1] - 2, A4[0], A4[1] - 2)
        canvas.setFillColor(MUTED)
        canvas.setFont("THSarabunNew", 10)
        canvas.drawCentredString(A4[0] / 2, 10 * mm, f"หน้า {doc_.page}")
        canvas.restoreState()

    doc.build(story, onFirstPage=on_page, onLaterPages=on_page)


def main() -> None:
    register_fonts()
    manuals = [
        ("คู่มืออาจารย์.md", "คู่มืออาจารย์.pdf"),
        ("คู่มือAdminสาขาวิชา.md", "คู่มือAdminสาขาวิชา.pdf"),
        ("คู่มือAdminกลาง.md", "คู่มือAdminกลาง.pdf"),
    ]
    for md_name, pdf_name in manuals:
        md_path = OUT_DIR / md_name
        pdf_path = OUT_DIR / pdf_name
        if not md_path.exists():
            raise FileNotFoundError(md_path)
        build_pdf(md_path, pdf_path)
        print(f"Created: {pdf_path}")


if __name__ == "__main__":
    main()
