import contextlib
import io
import sys
import tempfile
import types
import unittest
from pathlib import Path
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / "scripts"))

import process_math_pdf


class FakePage:
    def __init__(self, text: str, page_calls: list[tuple[str, bool]]) -> None:
        self.text = text
        self.page_calls = page_calls

    def get_text(self, output_type: str, sort: bool) -> str:
        self.page_calls.append((output_type, sort))
        return self.text


class FakeDocument:
    def __init__(self, pages: list[str], needs_pass: bool = False) -> None:
        self.needs_pass = needs_pass
        self.page_calls: list[tuple[str, bool]] = []
        self.pages = [FakePage(text, self.page_calls) for text in pages]

    @property
    def page_count(self) -> int:
        return len(self.pages)

    def load_page(self, page_number: int) -> FakePage:
        return self.pages[page_number]

    def __enter__(self) -> "FakeDocument":
        return self

    def __exit__(self, *_: object) -> None:
        pass


class PyMuPDFPipelineTest(unittest.TestCase):
    def test_extracts_selectable_text_page_by_page_in_reading_order(self) -> None:
        document = FakeDocument(["First page text.", "  Second page text.  "])
        fitz_module = types.ModuleType("fitz")
        fitz_module.open = lambda source: document

        with patch.dict(sys.modules, {"fitz": fitz_module}):
            output = io.StringIO()
            with contextlib.redirect_stdout(output):
                text = process_math_pdf.convert_pdf_to_markdown(Path("review.pdf"))

        self.assertEqual("First page text.\n\nSecond page text.", text)
        self.assertEqual([("text", True), ("text", True)], document.page_calls)
        self.assertEqual(
            [
                "PAGE_PROGRESS page=1/2 started",
                "PAGE_PROGRESS page=1/2 completed",
                "PAGE_PROGRESS page=2/2 started",
                "PAGE_PROGRESS page=2/2 completed",
            ],
            output.getvalue().strip().splitlines(),
        )

    def test_rejects_encrypted_or_textless_pdfs(self) -> None:
        fitz_module = types.ModuleType("fitz")
        encrypted_document = FakeDocument([""], needs_pass=True)
        fitz_module.open = lambda source: encrypted_document

        with patch.dict(sys.modules, {"fitz": fitz_module}):
            with self.assertRaisesRegex(RuntimeError, "password-protected"):
                process_math_pdf.convert_pdf_to_markdown(Path("review.pdf"))

        textless_document = FakeDocument(["", "  "])
        fitz_module.open = lambda source: textless_document
        with patch.dict(sys.modules, {"fitz": fitz_module}):
            with self.assertRaisesRegex(RuntimeError, "No selectable text"):
                process_math_pdf.convert_pdf_to_markdown(Path("review.pdf"))

    def test_process_pdf_writes_extracted_text_to_output_file(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            pdf_path = Path(directory) / "review.pdf"
            output_path = Path(directory) / "review.md"
            pdf_path.write_bytes(b"%PDF-1.7")

            with patch(
                "process_math_pdf.convert_pdf_to_markdown",
                return_value="First page\n\nSecond page",
            ):
                process_math_pdf.process_pdf(pdf_path, output_path)

            self.assertEqual(
                "First page\n\nSecond page\n",
                output_path.read_text(encoding="utf-8"),
            )


if __name__ == "__main__":
    unittest.main()
