"""Extract selectable text from PDF reviewers with PyMuPDF."""

from __future__ import annotations

import argparse
import os
import sys
import tempfile
from pathlib import Path


def convert_pdf_to_markdown(pdf_path: Path) -> str:
    """Extract text in reading order, processing one page at a time."""
    try:
        import fitz
    except ImportError as error:
        raise RuntimeError(
            "PyMuPDF is required. Install the dependencies with "
            "'py -m pip install -r requirements.txt' on Windows or "
            "'python3 -m pip install -r requirements.txt' on macOS/Linux."
        ) from error

    page_text: list[str] = []
    with fitz.open(str(pdf_path)) as document:
        if document.needs_pass:
            raise RuntimeError("The PDF is password-protected and cannot be read.")

        page_count = document.page_count
        if page_count < 1:
            raise RuntimeError("The PDF contains no pages.")

        for page_number in range(page_count):
            print(
                f"PAGE_PROGRESS page={page_number + 1}/{page_count} started",
                flush=True,
            )
            text = document.load_page(page_number).get_text("text", sort=True).strip()
            if text:
                page_text.append(text)
            print(
                f"PAGE_PROGRESS page={page_number + 1}/{page_count} completed",
                flush=True,
            )

    combined_text = "\n\n".join(page_text)
    if not combined_text:
        raise RuntimeError("No selectable text was found in the PDF.")

    return combined_text


def process_pdf(pdf_path: Path, output_path: Path) -> None:
    """Extract PDF text and atomically save it as Markdown."""
    pdf_path = pdf_path.resolve()
    output_path = output_path.resolve()

    if not pdf_path.is_file():
        raise FileNotFoundError(f"PDF not found: {pdf_path}")
    if pdf_path == output_path:
        raise ValueError("The output path must not overwrite the source PDF.")
    if output_path.suffix.lower() != ".md":
        raise ValueError("The output file must use the .md extension.")

    output_path.parent.mkdir(parents=True, exist_ok=True)
    file_descriptor, temporary_name = tempfile.mkstemp(
        prefix=f".{output_path.name}.",
        suffix=".tmp",
        dir=output_path.parent,
    )
    temporary_path = Path(temporary_name)
    os.close(file_descriptor)

    try:
        extracted_text = convert_pdf_to_markdown(pdf_path)
        with temporary_path.open("w", encoding="utf-8", newline="\n") as output:
            output.write(extracted_text + "\n")
        temporary_path.replace(output_path)
    except Exception:
        temporary_path.unlink(missing_ok=True)
        raise


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Extract selectable text from a PDF using PyMuPDF."
    )
    parser.add_argument("pdf", type=Path, help="Source PDF path")
    parser.add_argument("--output", required=True, type=Path, help="Output Markdown path")
    args = parser.parse_args()

    try:
        process_pdf(args.pdf, args.output)
    except (OSError, RuntimeError, ValueError) as error:
        print(f"PDF text extraction failed: {error}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
