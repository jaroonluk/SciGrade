# -*- coding: utf-8 -*-
"""SciGrade — Flow การทำงานของระบบ (PDF, Thai Sarabun New)."""

from pathlib import Path

from reportlab.lib.colors import HexColor, Color, white, black
from reportlab.lib.pagesizes import A4, landscape
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas

OUT = Path(__file__).resolve().parent / "SciGrade_System_Flow.pdf"
FONT_DIR = Path(r"C:\Users\Administrator\AppData\Local\Microsoft\Windows\Fonts")

# Brand
BROWN = HexColor("#5C2E1F")
AMBER = HexColor("#8B4513")
CREAM = HexColor("#FDF8F3")
WARM = HexColor("#F5E6D8")
SOFT = HexColor("#FFFCF9")
MUTED = HexColor("#7A4A3A")
DARK = HexColor("#2D2A26")
LINE = HexColor("#E0C9B4")
GREEN = HexColor("#2F6B4F")
GREEN_BG = HexColor("#E8F3EC")
BLUE = HexColor("#1F4E79")
BLUE_BG = HexColor("#E8F0F8")
ORANGE = HexColor("#B85C38")
ORANGE_BG = HexColor("#F8EDE6")
RED = HexColor("#9B2C2C")
RED_BG = HexColor("#F8E8E8")
GOLD = HexColor("#C4A35A")


def register_fonts():
    regular = FONT_DIR / "THSarabunNew.ttf"
    bold = FONT_DIR / "THSarabunNew Bold.ttf"
    if not regular.exists():
        regular = FONT_DIR / "Sarabun-Regular.ttf"
        bold = FONT_DIR / "Sarabun-Bold.ttf"
    pdfmetrics.registerFont(TTFont("THSarabun", str(regular)))
    pdfmetrics.registerFont(TTFont("THSarabun-Bold", str(bold if bold.exists() else regular)))


def rounded_rect(c, x, y, w, h, r, fill=None, stroke=None, stroke_w=1):
    c.saveState()
    if fill:
        c.setFillColor(fill)
    if stroke:
        c.setStrokeColor(stroke)
        c.setLineWidth(stroke_w)
    p = c.beginPath()
    p.moveTo(x + r, y)
    p.lineTo(x + w - r, y)
    p.arcTo(x + w - 2 * r, y, x + w, y + 2 * r, -90, 90)
    p.lineTo(x + w, y + h - r)
    p.arcTo(x + w - 2 * r, y + h - 2 * r, x + w, y + h, 0, 90)
    p.lineTo(x + r, y + h)
    p.arcTo(x, y + h - 2 * r, x + 2 * r, y + h, 90, 90)
    p.lineTo(x, y + r)
    p.arcTo(x, y, x + 2 * r, y + 2 * r, 180, 90)
    p.close()
    if fill and stroke:
        c.drawPath(p, fill=1, stroke=1)
    elif fill:
        c.drawPath(p, fill=1, stroke=0)
    else:
        c.drawPath(p, fill=0, stroke=1)
    c.restoreState()


def draw_arrow(c, x1, y1, x2, y2, color=AMBER, w=1.6):
    c.saveState()
    c.setStrokeColor(color)
    c.setFillColor(color)
    c.setLineWidth(w)
    c.setLineCap(1)
    c.line(x1, y1, x2, y2)
    # arrow head
    import math

    angle = math.atan2(y2 - y1, x2 - x1)
    size = 7
    ax = x2 - size * math.cos(angle - 0.4)
    ay = y2 - size * math.sin(angle - 0.4)
    bx = x2 - size * math.cos(angle + 0.4)
    by = y2 - size * math.sin(angle + 0.4)
    p = c.beginPath()
    p.moveTo(x2, y2)
    p.lineTo(ax, ay)
    p.lineTo(bx, by)
    p.close()
    c.drawPath(p, fill=1, stroke=0)
    c.restoreState()


def text_center(c, text, x, y, size=14, bold=False, color=DARK):
    c.setFont("THSarabun-Bold" if bold else "THSarabun", size)
    c.setFillColor(color)
    c.drawCentredString(x, y, text)


def text_left(c, text, x, y, size=14, bold=False, color=DARK):
    c.setFont("THSarabun-Bold" if bold else "THSarabun", size)
    c.setFillColor(color)
    c.drawString(x, y, text)


def wrap_center(c, text, x, y, size=12, bold=False, color=DARK, leading=14):
    c.setFont("THSarabun-Bold" if bold else "THSarabun", size)
    c.setFillColor(color)
    lines = text.split("\n")
    for i, line in enumerate(lines):
        c.drawCentredString(x, y - i * leading, line)


def step_card(c, x, y, w, h, num, title, lines, accent, bg):
    rounded_rect(c, x, y, w, h, 10, fill=bg, stroke=accent, stroke_w=1.2)
    # number badge
    badge_r = 12
    cx, cy = x + 18, y + h - 18
    c.setFillColor(accent)
    c.circle(cx, cy, badge_r, fill=1, stroke=0)
    text_center(c, str(num), cx, cy - 5, 13, True, white)
    text_left(c, title, x + 36, y + h - 23, 14, True, accent)
    yy = y + h - 42
    for line in lines:
        text_left(c, line, x + 14, yy, 12, False, DARK)
        yy -= 15


def lane_header(c, x, y, w, h, label, color):
    rounded_rect(c, x, y, w, h, 8, fill=color, stroke=None)
    text_center(c, label, x + w / 2, y + h / 2 - 6, 13, True, white)


def page_background(c, width, height):
    c.setFillColor(SOFT)
    c.rect(0, 0, width, height, fill=1, stroke=0)
    # soft top band
    c.setFillColor(CREAM)
    c.rect(0, height - 78, width, 78, fill=1, stroke=0)
    c.setFillColor(AMBER)
    c.rect(0, height - 6, width, 6, fill=1, stroke=0)


def header(c, width, height, subtitle):
    text_left(c, "SciGrade", 36, height - 32, 26, True, BROWN)
    text_left(c, "ระบบรายงานผลการสอบ  •  คณะวิทยาศาสตร์", 36, height - 50, 13, False, MUTED)
    text_left(c, subtitle, 36, height - 68, 12, False, AMBER)


def footer(c, width, page, total):
    c.setStrokeColor(LINE)
    c.setLineWidth(0.6)
    c.line(36, 28, width - 36, 28)
    text_left(c, "เอกสารภาพรวม Flow การทำงานของระบบ", 36, 14, 10, False, MUTED)
    text_left(c, f"{page}/{total}", width - 50, 14, 10, False, MUTED)


def draw_page1_main_flow(c):
    width, height = landscape(A4)
    page_background(c, width, height)
    header(c, width, height, "Flow หลัก: จากอาจารย์ → สาขา → คณะ")

    # Intro
    rounded_rect(c, 36, height - 118, width - 72, 32, 8, fill=WARM, stroke=LINE, stroke_w=0.8)
    text_center(
        c,
        "ภาพรวมการทำงานทั้งสายงาน — เริ่มจากอาจารย์ส่งรายงานผลสอบ จนถึงการอนุมัติระดับคณะและพิมพ์เอกสาร",
        width / 2,
        height - 108,
        13,
        False,
        DARK,
    )

    # 4 big steps
    steps = [
        (1, "อาจารย์ผู้สอน", ["สร้างหรืออัปโหลด", "รายงานผลการสอบ", "สถานะ: บันทึกแล้ว (0)"], ORANGE, ORANGE_BG),
        (2, "Admin สาขา", ["ตรวจสอบรายวิชา", "อนุมัติระดับสาขา", "สถานะ: สาขาอนุมัติ (1)"], BLUE, BLUE_BG),
        (3, "สาขา → คณะ", ["ส่งชุดเอกสารให้คณะ", "คณะรับเอกสาร", "พร้อมเข้าสู่การอนุมัติ"], AMBER, HexColor("#F3E8DC")),
        (4, "Admin กลาง", ["อนุมัติระดับคณะ", "พิมพ์ / ส่งออก PDF·Word", "สถานะ: คณะอนุมัติ (2)"], GREEN, GREEN_BG),
    ]

    card_w, card_h = 180, 118
    gap = 28
    total_w = 4 * card_w + 3 * gap
    start_x = (width - total_w) / 2
    y = height - 280

    for i, (num, title, lines, accent, bg) in enumerate(steps):
        x = start_x + i * (card_w + gap)
        step_card(c, x, y, card_w, card_h, num, title, lines, accent, bg)
        if i < 3:
            draw_arrow(c, x + card_w + 2, y + card_h / 2, x + card_w + gap - 4, y + card_h / 2, AMBER, 2)

    # Send-back loop callout
    loop_y = y - 95
    rounded_rect(c, 36, loop_y, width - 72, 70, 10, fill=RED_BG, stroke=RED, stroke_w=1)
    text_left(c, "กรณีส่งกลับแก้ไข (สถานะ −1)", 52, loop_y + 48, 14, True, RED)
    text_left(
        c,
        "สาขาหรือคณะสามารถส่งกลับให้อาจารย์แก้ไข → อาจารย์แก้ไขแล้วกด «ส่งการแก้ไข» → กลับเข้าสู่การตรวจสอบของสาขาอีกครั้ง",
        52,
        loop_y + 28,
        12,
        False,
        DARK,
    )
    text_left(
        c,
        "ทำให้มีวงจรปรับปรุงคุณภาพข้อมูลก่อนการรับรองผลสอบขั้นสุดท้าย",
        52,
        loop_y + 12,
        12,
        False,
        MUTED,
    )

    # Bottom status chips
    text_left(c, "สรุปสถานะรายงานผลสอบ", 36, 95, 13, True, BROWN)
    chips = [
        ("0  บันทึกแล้ว / รอสาขา", ORANGE),
        ("1  ผ่านระดับสาขา", BLUE),
        ("2  ผ่านระดับคณะ", GREEN),
        ("−1  ส่งกลับแก้ไข", RED),
    ]
    chip_w = 180
    sx = 36
    for i, (label, col) in enumerate(chips):
        x = sx + i * (chip_w + 12)
        rounded_rect(c, x, 52, chip_w, 28, 8, fill=white, stroke=col, stroke_w=1.4)
        # dot
        c.setFillColor(col)
        c.circle(x + 14, 66, 5, fill=1, stroke=0)
        text_left(c, label, x + 26, 60, 12, True, DARK)

    footer(c, width, 1, 2)


def draw_page2_swimlane(c):
    width, height = landscape(A4)
    page_background(c, width, height)
    header(c, width, height, "Flow แบบ Swimlane — ใครทำอะไรในแต่ละขั้น")

    # columns: บทบาท | ขั้นที่ 1-4
    margin = 28
    left_w = 110
    col_w = (width - margin * 2 - left_w) / 4
    top = height - 100
    row_h = 95
    roles = [
        ("อาจารย์", ORANGE, [
            "สร้าง / อัปโหลด\nรายงานผลสอบ\nแนบไฟล์ (ถ้ามี)",
            "รอสาขาตรวจสอบ\n(สถานะ 0)",
            "หากถูกส่งกลับ\n→ แก้ไขแล้วส่งใหม่",
            "พิมพ์รายงาน\nรายวิชาของตนเอง",
        ]),
        ("Admin สาขา", BLUE, [
            "ดูรายการรอตรวจ\nตามสาขาที่ดูแล",
            "อนุมัติ / ส่งกลับ\nระดับสาขา",
            "ส่งชุดเอกสาร\nให้คณะรับ",
            "พิมพ์รายงานสาขา\nPDF / Word",
        ]),
        ("Admin กลาง", GREEN, [
            "ตั้งค่าภาค–ปี\nจัดการรายวิชา REG",
            "รับเอกสารจากสาขา",
            "อนุมัติระดับคณะ\n(รายวิชา / หลายรายการ)",
            "รายงานทุกสาขา\nพิมพ์ / ส่งออก",
        ]),
    ]

    # column headers
    headers = ["1. ส่งรายงาน", "2. อนุมัติสาขา", "3. ส่งเอกสาร", "4. อนุมัติคณะ"]
    header_y = top - 28
    rounded_rect(c, margin, header_y, left_w - 6, 26, 6, fill=BROWN)
    text_center(c, "บทบาท", margin + (left_w - 6) / 2, header_y + 8, 12, True, white)
    for i, h in enumerate(headers):
        x = margin + left_w + i * col_w
        rounded_rect(c, x + 3, header_y, col_w - 6, 26, 6, fill=AMBER)
        text_center(c, h, x + col_w / 2, header_y + 8, 12, True, white)

    for r, (role, color, cells) in enumerate(roles):
        y = header_y - (r + 1) * (row_h + 8)
        lane_header(c, margin, y, left_w - 6, row_h, role, color)
        for i, cell in enumerate(cells):
            x = margin + left_w + i * col_w
            rounded_rect(c, x + 3, y, col_w - 6, row_h, 8, fill=white, stroke=LINE, stroke_w=0.9)
            # accent strip
            c.setFillColor(color)
            c.rect(x + 3, y, 4, row_h, fill=1, stroke=0)
            wrap_center(c, cell, x + col_w / 2, y + row_h - 28, 12, False, DARK, 14)

    # Bottom architecture strip
    box_y = 42
    rounded_rect(c, margin, box_y, width - margin * 2, 72, 10, fill=CREAM, stroke=LINE, stroke_w=0.8)
    text_left(c, "การเชื่อมต่อข้อมูล", margin + 14, box_y + 52, 13, True, BROWN)
    text_left(
        c,
        "ผู้ใช้ (Google Login)  →  SciGrade  →  ฐาน SciGrad (รายงาน / สิทธิ์ / เอกสาร)  +  ฐาน REG (รายวิชา อ่านอย่างเดียว)",
        margin + 14,
        box_y + 32,
        12,
        False,
        DARK,
    )
    text_left(
        c,
        "เอกสารผลลัพธ์: พิมพ์บนเว็บ  •  PDF  •  Word (.docx)   |   REG ใช้เพื่อติดตามรายวิชา ไม่มีการเขียนกลับเข้า REG",
        margin + 14,
        box_y + 14,
        12,
        False,
        MUTED,
    )

    footer(c, width, 2, 2)


def main():
    register_fonts()
    c = canvas.Canvas(str(OUT), pagesize=landscape(A4))
    draw_page1_main_flow(c)
    c.showPage()
    draw_page2_swimlane(c)
    c.save()
    print(OUT)
    print("bytes", OUT.stat().st_size)


if __name__ == "__main__":
    main()
