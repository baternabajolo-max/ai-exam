import base64
import json
import os
import sys
import tempfile
import types
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / "scripts"))

import extract_pdf_text


class FakeResponse:
    def __init__(self, response: dict[str, object]) -> None:
        self.response = json.dumps(response).encode("utf-8")

    def __enter__(self) -> "FakeResponse":
        return self

    def __exit__(self, *_: object) -> None:
        return None

    def read(self) -> bytes:
        return self.response


class VisionTranscriptionTest(unittest.TestCase):
    def test_pdf_pages_are_rendered_one_at_a_time_as_pngs(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            pdf_path = Path(directory) / "book.pdf"
            image_directory = Path(directory) / "pages"
            image_directory.mkdir()
            pdfinfo_from_path = Mock(return_value={"Pages": "2"})
            saved_formats = []

            class FakeImage:
                def save(self, path: Path, format: str) -> None:
                    saved_formats.append(format)
                    Path(path).write_bytes(b"rendered-page")

                def close(self) -> None:
                    pass

            convert_from_path = Mock(side_effect=[[FakeImage()], [FakeImage()]])
            pdf2image_module = types.ModuleType("pdf2image")
            pdf2image_module.convert_from_path = convert_from_path
            pdf2image_module.pdfinfo_from_path = pdfinfo_from_path
            exceptions_module = types.ModuleType("pdf2image.exceptions")
            for exception_name in (
                "PDFInfoNotInstalledError",
                "PDFPageCountError",
                "PDFPopplerTimeoutError",
                "PDFSyntaxError",
            ):
                setattr(
                    exceptions_module,
                    exception_name,
                    type(exception_name, (Exception,), {}),
                )

            with patch.dict(
                sys.modules,
                {"pdf2image": pdf2image_module, "pdf2image.exceptions": exceptions_module},
            ):
                rendered_pages = list(
                    extract_pdf_text.render_pdf_pages(pdf_path, image_directory, dpi=300)
                )

            self.assertEqual([1, 2], [page_number for page_number, _ in rendered_pages])
            self.assertEqual(b"rendered-page", rendered_pages[0][1].read_bytes())
            self.assertEqual(2, convert_from_path.call_count)
            self.assertEqual(["PNG", "PNG"], saved_formats)
            self.assertEqual(300, convert_from_path.call_args_list[0].kwargs["dpi"])
            self.assertEqual(1, convert_from_path.call_args_list[0].kwargs["first_page"])
            self.assertEqual(2, convert_from_path.call_args_list[1].kwargs["first_page"])

    def test_ollama_receives_page_image_and_returns_markdown(self) -> None:
        environment = {
            "PDF_VLM_PROVIDER": "ollama",
            "OLLAMA_BASE_URL": "http://localhost:11434",
            "PDF_VLM_OLLAMA_MODEL": "qwen2.5vl:7b",
        }

        with tempfile.TemporaryDirectory() as directory:
            image_path = Path(directory) / "page.png"
            image_path.write_bytes(b"test-png-image")

            with patch.dict(os.environ, environment, clear=True):
                config = extract_pdf_text.load_vision_config()
                with patch(
                    "extract_pdf_text.urllib.request.urlopen",
                    return_value=FakeResponse({"message": {"content": "Formula: $x^2$."}}),
                ) as urlopen:
                    transcription = extract_pdf_text.transcribe_page(image_path, config)

            request = urlopen.call_args.args[0]
            payload = json.loads(request.data)

        self.assertEqual("Formula: $x^2$.", transcription)
        self.assertEqual("http://localhost:11434/api/chat", request.full_url)
        self.assertEqual(
            base64.b64encode(b"test-png-image").decode("ascii"),
            payload["messages"][0]["images"][0],
        )
        self.assertIn(extract_pdf_text.TRANSCRIPTION_PROMPT, payload["messages"][0]["content"])

    def test_openai_receives_vision_prompt_and_api_key(self) -> None:
        environment = {
            "PDF_VLM_PROVIDER": "openai",
            "PDF_VLM_OPENAI_API_KEY": "test-key",
        }

        with tempfile.TemporaryDirectory() as directory:
            image_path = Path(directory) / "page.png"
            image_path.write_bytes(b"test-png-image")

            with patch.dict(os.environ, environment, clear=True):
                config = extract_pdf_text.load_vision_config()
                with patch(
                    "extract_pdf_text.urllib.request.urlopen",
                    return_value=FakeResponse({
                        "choices": [{"message": {"content": "The answer is $x=1$."}}]
                    }),
                ) as urlopen:
                    transcription = extract_pdf_text.transcribe_page(image_path, config)

            request = urlopen.call_args.args[0]
            payload = json.loads(request.data)

        self.assertEqual("The answer is $x=1$.", transcription)
        self.assertEqual("Bearer test-key", request.get_header("Authorization"))
        self.assertIn(
            extract_pdf_text.TRANSCRIPTION_PROMPT,
            payload["messages"][0]["content"][0]["text"],
        )
        self.assertTrue(
            payload["messages"][0]["content"][1]["image_url"]["url"].startswith(
                "data:image/png;base64,"
            )
        )

    def test_gemini_receives_inline_image_and_returns_markdown(self) -> None:
        environment = {
            "PDF_VLM_PROVIDER": "gemini",
            "PDF_VLM_GEMINI_API_KEY": "test-key",
        }

        with tempfile.TemporaryDirectory() as directory:
            image_path = Path(directory) / "page.png"
            image_path.write_bytes(b"test-png-image")

            with patch.dict(os.environ, environment, clear=True):
                config = extract_pdf_text.load_vision_config()
                with patch(
                    "extract_pdf_text.urllib.request.urlopen",
                    return_value=FakeResponse({
                        "candidates": [{
                            "content": {"parts": [{"text": "Matrix: $$A=B$$."}]}
                        }]
                    }),
                ) as urlopen:
                    transcription = extract_pdf_text.transcribe_page(image_path, config)

            request = urlopen.call_args.args[0]
            payload = json.loads(request.data)
        self.assertEqual("Matrix: $$A=B$$.", transcription)
        self.assertIn("key=test-key", request.full_url)
        self.assertEqual(
            base64.b64encode(b"test-png-image").decode("ascii"),
            payload["contents"][0]["parts"][1]["inlineData"]["data"],
        )

    def test_hosted_provider_requires_its_api_key(self) -> None:
        with patch.dict(os.environ, {"PDF_VLM_PROVIDER": "openai"}, clear=True):
            with self.assertRaisesRegex(ValueError, "PDF_VLM_OPENAI_API_KEY is required"):
                extract_pdf_text.load_vision_config()


if __name__ == "__main__":
    unittest.main()
