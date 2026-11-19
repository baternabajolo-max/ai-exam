# pip install torch torchvision --index-url https://download.pytorch.org/whl/cu121
"""Convert a math-heavy PDF to LaTeX-aware Markdown with Marker on CUDA."""

from __future__ import annotations

import argparse
import gc
import os
import sys
import tempfile
from pathlib import Path
from typing import Any


def convert_pdf_to_markdown(pdf_path: Path) -> str:
    """Convert a PDF one page at a time with low-memory Marker settings."""
    os.environ.update({
        "TORCH_DEVICE": "cuda",
        "DETECTOR_BATCH_SIZE": "1",
        "OCR_ERROR_BATCH_SIZE": "1",
        "FAST_LAYOUT_BATCH_SIZE": "1",
        "SURYA_INFERENCE_PARALLEL": "1",
        "DISABLE_TQDM": "1",
    })

    try:
        import torch
        from marker.config.parser import ConfigParser
        from marker.converters.pdf import PdfConverter
        from marker.models import create_model_dict, shutdown_models
        from marker.output import text_from_rendered
        from pypdfium2 import PdfDocument
    except ImportError as error:
        raise RuntimeError(
            "Marker and PyTorch are required. Install Marker, then install the CUDA "
            "PyTorch build using the command at the top of this script."
        ) from error

    if not torch.cuda.is_available():
        raise RuntimeError(
            "CUDA is unavailable. Verify that an NVIDIA driver is installed and that "
            "PyTorch was installed with the CUDA 12.1 build."
        )

    mode = os.environ.get("MATH_PDF_MODE", "fast").strip().lower()
    if mode not in {"fast", "balanced"}:
        raise ValueError("MATH_PDF_MODE must be either 'fast' or 'balanced'.")

    pdf_document = PdfDocument(str(pdf_path))
    try:
        page_count = len(pdf_document)
    finally:
        pdf_document.close()

    if page_count < 1:
        raise RuntimeError("The PDF contains no pages.")

    model_dict: dict[str, Any] | None = None
    page_markdown: list[str] = []
    try:
        model_dict = create_model_dict()
        config = ConfigParser({
            "output_format": "markdown",
            "mode": mode,
        }).generate_config_dict()

        config["pdftext_workers"] = 1
        config["cache_pdftext_pages"] = False

        for page_number in range(page_count):
            print(
                f"PAGE_PROGRESS page={page_number + 1}/{page_count} started",
                flush=True,
            )
            page_config = {**config, "page_range": [page_number]}
            converter = PdfConverter(artifact_dict=model_dict, config=page_config)
            rendered_document = converter(str(pdf_path))
            markdown, _, _ = text_from_rendered(rendered_document)
            if isinstance(markdown, str):
                page_markdown.append(markdown.strip())

            print(
                f"PAGE_PROGRESS page={page_number + 1}/{page_count} completed",
                flush=True,
            )
            del converter, rendered_document, markdown
            gc.collect()
            torch.cuda.empty_cache()
    finally:
        if model_dict is not None:
            shutdown_models(model_dict)
        gc.collect()
        torch.cuda.empty_cache()

    combined_markdown = "\n\n".join(markdown for markdown in page_markdown if markdown)
    if not combined_markdown:
        raise RuntimeError("Marker returned no readable text or equations.")

    return combined_markdown


def process_pdf(pdf_path: Path, output_path: Path) -> None:
    """Convert the complete PDF and atomically save its Markdown output."""
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
        markdown = convert_pdf_to_markdown(pdf_path)
        with temporary_path.open("w", encoding="utf-8", newline="\n") as output:
            output.write(markdown + "\n")
        temporary_path.replace(output_path)
    except Exception:
        temporary_path.unlink(missing_ok=True)
        raise


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Convert a math-heavy PDF to LaTeX Markdown using Marker on CUDA."
    )
    parser.add_argument("pdf", type=Path, help="Source PDF path")
    parser.add_argument("--output", required=True, type=Path, help="Output Markdown path")
    args = parser.parse_args()

    try:
        process_pdf(args.pdf, args.output)
    except (OSError, RuntimeError, ValueError) as error:
        print(f"Math PDF processing failed: {error}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
