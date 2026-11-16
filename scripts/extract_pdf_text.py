"""Render PDF pages and convert their text and equations into ordered Markdown.

Install the local OCR dependencies:
    Windows: py -3.12 -m pip install -r requirements.txt
    macOS/Linux: python3 -m pip install -r requirements.txt

Extract every page, including image-based text and formulas:
    Windows: py -3.12 scripts/extract_pdf_text.py textbook.pdf --output textbook.md
    macOS/Linux: python3 scripts/extract_pdf_text.py textbook.pdf --output textbook.md
"""

from __future__ import annotations

import argparse
import os
import re
import sys
import tempfile
from pathlib import Path
from typing import Any


def render_pdf_pages(pdf_path: Path, image_dir: Path, dpi: int) -> list[Path]:
    """Render every PDF page to a high-resolution PNG in page order."""
    try:
        import fitz
    except ImportError as error:
        raise RuntimeError(
            "PyMuPDF is not installed. Install the dependencies with "
            "'py -3.12 -m pip install -r requirements.txt' on Windows or "
            "'python3 -m pip install -r requirements.txt' on macOS/Linux."
        ) from error

    rendered_pages = []

    with fitz.open(pdf_path) as document:
        if document.needs_pass:
            raise ValueError("This PDF is password-protected.")
        if document.page_count == 0:
            raise ValueError("The PDF does not contain any pages.")

        for page_index in range(document.page_count):
            page = document.load_page(page_index)
            image_path = image_dir / f"page_{page_index + 1:04d}.png"
            page.get_pixmap(dpi=dpi, alpha=False).save(image_path)
            rendered_pages.append(image_path)

    return rendered_pages


def create_ocr_pipeline() -> Any:
    """Create the local PaddleOCR pipeline with formula recognition enabled."""
    try:
        import paddle
        from paddleocr import PPStructureV3
    except ImportError as error:
        raise RuntimeError(
            "PaddleOCR is not installed. Install the dependencies with "
            "'py -3.12 -m pip install -r requirements.txt' on Windows or "
            "'python3 -m pip install -r requirements.txt' on macOS/Linux."
        ) from error

    has_cuda = paddle.is_compiled_with_cuda() and paddle.device.cuda.device_count() > 0
    device = "gpu:0" if has_cuda else "cpu"

    return PPStructureV3(
        use_doc_orientation_classify=False,
        use_doc_unwarping=False,
        use_textline_orientation=False,
        use_seal_recognition=False,
        use_table_recognition=False,
        use_formula_recognition=True,
        use_chart_recognition=False,
        use_region_detection=False,
        text_detection_model_name="PP-OCRv5_mobile_det",
        text_recognition_model_name="en_PP-OCRv5_mobile_rec",
        formula_recognition_model_name="PP-FormulaNet_plus-S",
        device=device,
        enable_mkldnn=device == "cpu" and sys.platform != "win32",
    )


def markdown_text_from_result(result: Any) -> str:
    """Read the layout-ordered Markdown text from one PaddleOCR result."""
    markdown = result.markdown
    if not isinstance(markdown, dict):
        raise RuntimeError("The local OCR model returned an invalid Markdown result.")

    markdown_text = markdown.get("markdown_texts")
    if isinstance(markdown_text, str):
        return markdown_text.strip()
    if isinstance(markdown_text, list):
        return "\n\n".join(
            item.strip() for item in markdown_text if isinstance(item, str) and item.strip()
        )

    raise RuntimeError("The local OCR model did not return Markdown text.")


def extract_page_markdown(ocr_pipeline: Any, image_path: Path) -> str:
    """Extract regular text and LaTeX formulas from one rendered page."""
    results = list(ocr_pipeline.predict(str(image_path)))
    if not results:
        raise RuntimeError(f"The local OCR model returned no result for {image_path.name}.")

    return "\n\n".join(
        text for text in (markdown_text_from_result(result) for result in results) if text
    )


def combine_page_markdown(page_texts: list[str]) -> str:
    """Join page Markdown in reading order and remove excessive blank lines."""
    pages = []
    for page_number, page_text in enumerate(page_texts, start=1):
        cleaned_text = re.sub(r"\n[ \t]*\n(?:[ \t]*\n)+", "\n\n", page_text.strip())
        if cleaned_text:
            pages.append(f"## Page {page_number}\n\n{cleaned_text}")

    return "\n\n---\n\n".join(pages).strip() + "\n"


def extract_pdf_to_markdown(pdf_path: Path, output_path: Path, dpi: int = 240) -> None:
    """Render, OCR, and atomically save a single Markdown document."""
    pdf_path = pdf_path.resolve()
    output_path = output_path.resolve()

    if not pdf_path.is_file():
        raise FileNotFoundError(f"PDF not found: {pdf_path}")
    if pdf_path == output_path:
        raise ValueError("The output path must not overwrite the source PDF.")
    if output_path.suffix.lower() != ".md":
        raise ValueError("The output file must use the .md extension.")
    if not 72 <= dpi <= 600:
        raise ValueError("DPI must be between 72 and 600.")

    output_path.parent.mkdir(parents=True, exist_ok=True)
    file_descriptor, temporary_name = tempfile.mkstemp(
        prefix=f".{output_path.name}.",
        suffix=".tmp",
        dir=output_path.parent,
    )
    temporary_path = Path(temporary_name)
    os.close(file_descriptor)

    try:
        with tempfile.TemporaryDirectory(prefix="lemu-pdf-pages-") as image_directory:
            page_images = render_pdf_pages(pdf_path, Path(image_directory), dpi)
            ocr_pipeline = create_ocr_pipeline()
            page_texts = [
                extract_page_markdown(ocr_pipeline, page_image)
                for page_image in page_images
            ]
            markdown = combine_page_markdown(page_texts)
            if not markdown.strip():
                raise RuntimeError("No readable text or equations were found in the PDF.")

        with temporary_path.open("w", encoding="utf-8", newline="\n") as output:
            output.write(markdown)
        temporary_path.replace(output_path)
    except Exception:
        temporary_path.unlink(missing_ok=True)
        raise


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Extract ordered text and LaTeX equations from every PDF page."
    )
    parser.add_argument("pdf", type=Path, help="Source PDF path")
    parser.add_argument("--output", required=True, type=Path, help="Output Markdown path")
    parser.add_argument(
        "--dpi",
        type=int,
        default=240,
        help="Resolution used to render every page (default: 240)",
    )
    args = parser.parse_args()

    try:
        extract_pdf_to_markdown(args.pdf, args.output, args.dpi)
    except (OSError, RuntimeError, ValueError) as error:
        print(f"PDF OCR extraction failed: {error}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
