"""Render PDF pages and transcribe their text and equations with a vision model.

Install the Python dependency:
    Windows: py -3.12 -m pip install -r requirements.txt
    macOS/Linux: python3 -m pip install -r requirements.txt

Install Poppler separately and make its `bin` directory available on PATH, or
set PDF_POPPLER_PATH to that directory.

Extract every page into Markdown:
    Windows: py -3.12 scripts/extract_pdf_text.py textbook.pdf --output textbook.md
    macOS/Linux: python3 scripts/extract_pdf_text.py textbook.pdf --output textbook.md
"""

from __future__ import annotations

import argparse
import base64
import json
import math
import os
import re
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Iterator

BLOCK_MATH_ENVIRONMENT = re.compile(
    r"\\begin\{(?:aligned|align|gathered|gather|cases|array|matrix|pmatrix|bmatrix)\}"
)
INLINE_MATH = re.compile(r"(?<!\$)\$(?!\$)(.+?)(?<!\$)\$(?!\$)", re.DOTALL)
TRANSCRIPTION_PROMPT = (
    "You are an expert mathematics transcriber. Look at this textbook page image and "
    "transcribe all text and equations. For every equation, formula, matrix, or symbol, "
    "write it out in pristine LaTeX syntax (`$...$` for inline and `$$...$$` for block "
    "equations). Ensure mathematical operators, subscripts, and vectors are structurally "
    "accurate. Return only the transcription as Markdown, with no prefatory explanation "
    "or enclosing code fence."
)


@dataclass(frozen=True)
class VisionConfig:
    provider: str
    model: str
    endpoint: str
    timeout: float
    api_key: str | None = None


def load_vision_config() -> VisionConfig:
    """Load the selected vision provider settings from the environment."""
    provider = os.getenv("PDF_VLM_PROVIDER", "ollama").strip().lower()
    timeout_value = os.getenv("PDF_VLM_TIMEOUT_SECONDS", "180")

    try:
        timeout = float(timeout_value)
    except ValueError as error:
        raise ValueError("PDF_VLM_TIMEOUT_SECONDS must be a positive number.") from error

    if not math.isfinite(timeout) or timeout <= 0:
        raise ValueError("PDF_VLM_TIMEOUT_SECONDS must be a positive number.")

    if provider == "ollama":
        model = os.getenv("PDF_VLM_OLLAMA_MODEL", "qwen2.5vl:7b").strip()
        base_url = os.getenv("OLLAMA_BASE_URL", "http://127.0.0.1:11434").rstrip("/")
        endpoint = f"{base_url}/api/chat"
        api_key = None
    elif provider == "openai":
        api_key = os.getenv("PDF_VLM_OPENAI_API_KEY", "").strip()
        model = os.getenv("PDF_VLM_OPENAI_MODEL", "gpt-4o-mini").strip()
        base_url = os.getenv(
            "PDF_VLM_OPENAI_BASE_URL", "https://api.openai.com/v1"
        ).rstrip("/")
        endpoint = f"{base_url}/chat/completions"
        if not api_key:
            raise ValueError("PDF_VLM_OPENAI_API_KEY is required for the OpenAI provider.")
    elif provider == "gemini":
        api_key = os.getenv("PDF_VLM_GEMINI_API_KEY", "").strip()
        model = os.getenv("PDF_VLM_GEMINI_MODEL", "gemini-2.5-flash").strip()
        if not api_key:
            raise ValueError("PDF_VLM_GEMINI_API_KEY is required for the Gemini provider.")
        endpoint = (
            "https://generativelanguage.googleapis.com/v1beta/models/"
            f"{urllib.parse.quote(model, safe='')}:generateContent?"
            f"{urllib.parse.urlencode({'key': api_key})}"
        )
    else:
        raise ValueError("PDF_VLM_PROVIDER must be one of: ollama, openai, gemini.")

    if not model:
        raise ValueError(f"A vision model name must be set for the {provider} provider.")
    if provider != "gemini" and not base_url.startswith(("http://", "https://")):
        raise ValueError(f"The base URL for the {provider} provider must use HTTP or HTTPS.")

    return VisionConfig(provider, model, endpoint, timeout, api_key)


def render_pdf_pages(pdf_path: Path, image_dir: Path, dpi: int) -> Iterator[tuple[int, Path]]:
    """Render each PDF page to a high-resolution PNG, yielding one page at a time."""
    try:
        from pdf2image import convert_from_path, pdfinfo_from_path
        from pdf2image.exceptions import (
            PDFInfoNotInstalledError,
            PDFPageCountError,
            PDFPopplerTimeoutError,
            PDFSyntaxError,
        )
    except ImportError as error:
        raise RuntimeError(
            "pdf2image is not installed. Install the dependencies with "
            "'py -3.12 -m pip install -r requirements.txt' on Windows or "
            "'python3 -m pip install -r requirements.txt' on macOS/Linux."
        ) from error

    poppler_path = os.getenv("PDF_POPPLER_PATH") or None

    try:
        page_count = int(
            pdfinfo_from_path(
                str(pdf_path),
                poppler_path=poppler_path,
                timeout=60,
            )["Pages"]
        )
    except (PDFInfoNotInstalledError, PDFPageCountError, PDFPopplerTimeoutError, PDFSyntaxError) as error:
        raise RuntimeError(f"Could not read the PDF page count: {error}") from error
    except (KeyError, TypeError, ValueError) as error:
        raise RuntimeError("The PDF page count could not be determined.") from error

    if page_count < 1:
        raise ValueError("The PDF does not contain any pages.")

    for page_number in range(1, page_count + 1):
        image_path = image_dir / f"page_{page_number:04d}.png"

        try:
            page_images = convert_from_path(
                str(pdf_path),
                dpi=dpi,
                first_page=page_number,
                last_page=page_number,
                fmt="png",
                poppler_path=poppler_path,
                thread_count=1,
                timeout=120,
            )
        except (PDFInfoNotInstalledError, PDFPageCountError, PDFPopplerTimeoutError, PDFSyntaxError) as error:
            raise RuntimeError(f"Could not render PDF page {page_number}: {error}") from error

        if not page_images:
            raise RuntimeError(f"No image was rendered for PDF page {page_number}.")

        page_image = page_images[0]
        try:
            page_image.save(image_path, format="PNG")
        finally:
            page_image.close()

        yield page_number, image_path


def _post_json(
    config: VisionConfig,
    payload: dict[str, Any],
    headers: dict[str, str] | None = None,
) -> dict[str, Any]:
    request_headers = {"Content-Type": "application/json"}
    request_headers.update(headers or {})
    request = urllib.request.Request(
        config.endpoint,
        data=json.dumps(payload).encode("utf-8"),
        headers=request_headers,
        method="POST",
    )

    try:
        with urllib.request.urlopen(request, timeout=config.timeout) as response:
            result = json.loads(response.read().decode("utf-8"))
    except urllib.error.HTTPError as error:
        raise RuntimeError(
            f"The {config.provider} vision request failed with HTTP {error.code}."
        ) from error
    except urllib.error.URLError as error:
        raise RuntimeError(
            f"Could not reach the {config.provider} vision service: {error.reason}"
        ) from error
    except (UnicodeDecodeError, json.JSONDecodeError) as error:
        raise RuntimeError(
            f"The {config.provider} vision service returned invalid JSON."
        ) from error

    if not isinstance(result, dict):
        raise RuntimeError(f"The {config.provider} vision service returned an invalid response.")

    return result


def _response_text(response: dict[str, Any], provider: str) -> str:
    """Extract transcription text from a vision provider response."""
    if provider == "ollama":
        message = response.get("message")
        text = message.get("content") if isinstance(message, dict) else None
    elif provider == "openai":
        choices = response.get("choices")
        choice = choices[0] if isinstance(choices, list) and choices else None
        message = choice.get("message") if isinstance(choice, dict) else None
        content = message.get("content") if isinstance(message, dict) else None
        if isinstance(content, list):
            text = "\n".join(
                item["text"]
                for item in content
                if isinstance(item, dict) and isinstance(item.get("text"), str)
            )
        else:
            text = content
    else:
        candidates = response.get("candidates")
        candidate = candidates[0] if isinstance(candidates, list) and candidates else None
        content = candidate.get("content") if isinstance(candidate, dict) else None
        parts = content.get("parts") if isinstance(content, dict) else None
        text = "\n".join(
            part["text"]
            for part in parts
            if isinstance(part, dict) and isinstance(part.get("text"), str)
        ) if isinstance(parts, list) else None

    if not isinstance(text, str) or not text.strip():
        raise RuntimeError(f"The {provider} vision service returned no transcription text.")

    return text.strip()


def transcribe_page(image_path: Path, config: VisionConfig) -> str:
    """Send one rendered page to the configured vision-language model."""
    encoded_image = base64.b64encode(image_path.read_bytes()).decode("ascii")

    if config.provider == "ollama":
        response = _post_json(
            config,
            {
                "model": config.model,
                "messages": [
                    {
                        "role": "user",
                        "content": TRANSCRIPTION_PROMPT,
                        "images": [encoded_image],
                    }
                ],
                "stream": False,
                "options": {"temperature": 0},
            },
        )
    elif config.provider == "openai":
        response = _post_json(
            config,
            {
                "model": config.model,
                "messages": [
                    {
                        "role": "user",
                        "content": [
                            {"type": "text", "text": TRANSCRIPTION_PROMPT},
                            {
                                "type": "image_url",
                                "image_url": {"url": f"data:image/png;base64,{encoded_image}"},
                            },
                        ],
                    }
                ],
                "temperature": 0,
            },
            {"Authorization": f"Bearer {config.api_key}"},
        )
    else:
        response = _post_json(
            config,
            {
                "contents": [
                    {
                        "role": "user",
                        "parts": [
                            {"text": TRANSCRIPTION_PROMPT},
                            {
                                "inlineData": {
                                    "mimeType": "image/png",
                                    "data": encoded_image,
                                }
                            },
                        ],
                    }
                ],
                "generationConfig": {"temperature": 0},
            },
        )

    return _response_text(response, config.provider)


def normalize_block_math(markdown_text: str) -> str:
    """Use display delimiters for block-only LaTeX environments."""

    def format_formula(match: re.Match[str]) -> str:
        formula = match.group(1).strip()
        if BLOCK_MATH_ENVIRONMENT.search(formula):
            return f"$${formula}$$"

        return match.group(0)

    return INLINE_MATH.sub(format_formula, markdown_text)


def combine_page_markdown(page_texts: list[str]) -> str:
    """Join page Markdown in reading order and remove excessive blank lines."""
    pages = []
    for page_number, page_text in enumerate(page_texts, start=1):
        cleaned_text = re.sub(r"\n[ \t]*\n(?:[ \t]*\n)+", "\n\n", page_text.strip())
        if cleaned_text:
            pages.append(f"## Page {page_number}\n\n{cleaned_text}")

    return "\n\n---\n\n".join(pages).strip() + "\n"


def extract_pdf_to_markdown(pdf_path: Path, output_path: Path, dpi: int = 300) -> None:
    """Render, transcribe, and atomically save one complete PDF as Markdown."""
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

    vision_config = load_vision_config()
    output_path.parent.mkdir(parents=True, exist_ok=True)
    file_descriptor, temporary_name = tempfile.mkstemp(
        prefix=f".{output_path.name}.",
        suffix=".tmp",
        dir=output_path.parent,
    )
    temporary_path = Path(temporary_name)
    os.close(file_descriptor)

    try:
        page_texts = []
        with tempfile.TemporaryDirectory(prefix="lemu-pdf-pages-") as image_directory:
            for page_number, image_path in render_pdf_pages(
                pdf_path, Path(image_directory), dpi
            ):
                try:
                    page_text = transcribe_page(image_path, vision_config)
                except (OSError, RuntimeError) as error:
                    raise RuntimeError(
                        f"Vision transcription failed for PDF page {page_number}: {error}"
                    ) from error
                finally:
                    image_path.unlink(missing_ok=True)

                page_texts.append(normalize_block_math(page_text))

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
        description="Transcribe ordered text and LaTeX equations from every PDF page with a vision model."
    )
    parser.add_argument("pdf", type=Path, help="Source PDF path")
    parser.add_argument("--output", required=True, type=Path, help="Output Markdown path")
    parser.add_argument(
        "--dpi",
        type=int,
        default=300,
        help="Resolution used to render every page (default: 300)",
    )
    args = parser.parse_args()

    try:
        extract_pdf_to_markdown(args.pdf, args.output, args.dpi)
    except (OSError, RuntimeError, ValueError) as error:
        print(f"PDF vision transcription failed: {error}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
