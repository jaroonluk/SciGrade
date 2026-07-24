# -*- coding: utf-8 -*-
"""Capture SciGrade manual screenshots with Playwright."""

from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent
HTML = ROOT / "html" / "ui-mock.html"
IMG = ROOT / "img"
IMG.mkdir(parents=True, exist_ok=True)

SHOTS = [
    ("login", "http://127.0.0.1:8080/login", None),
    ("roles", HTML.as_uri() + "?page=roles", None),
    ("instructor_home", HTML.as_uri() + "?page=instructor_home", None),
    ("dept_home", HTML.as_uri() + "?page=dept_home", None),
    ("faculty_home", HTML.as_uri() + "?page=faculty_home", None),
]


def main() -> None:
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={"width": 1280, "height": 900}, device_scale_factor=1.5)
        for name, url, _ in SHOTS:
            page.goto(url, wait_until="networkidle", timeout=60000)
            page.wait_for_timeout(800)
            out = IMG / f"{name}.png"
            page.screenshot(path=str(out), full_page=True)
            print(f"saved {out}")
        browser.close()


if __name__ == "__main__":
    main()
