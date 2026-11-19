import contextlib
import io
import os
import sys
import tempfile
import types
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / "scripts"))

import process_math_pdf


class MarkerPipelineTest(unittest.TestCase):
    def test_converter_requires_cuda_and_returns_marker_markdown(self) -> None:
        pdf_path = Path("math-review.pdf")
        model_dict = {"model": "marker"}
        shutdown_models = Mock()
        created_model_dict = Mock(return_value=model_dict)
        converted_pdf = Mock(return_value="rendered-document")
        converter_configs: list[dict[str, object]] = []
        empty_cache = Mock()

        class FakeConfigParser:
            def __init__(self, config: dict[str, str]) -> None:
                self.config = config

            def generate_config_dict(self) -> dict[str, str]:
                return dict(self.config)

        class FakePdfConverter:
            def __init__(
                self,
                artifact_dict: dict[str, str],
                config: dict[str, object],
            ) -> None:
                self.artifact_dict = artifact_dict
                self.config = config
                converter_configs.append(config)

            def __call__(self, source: str) -> str:
                self.source = source
                converted_pdf()
                return f"rendered-{self.config['page_range'][0]}"

        class FakePdfDocument:
            def __init__(self, source: str) -> None:
                self.source = source

            def __len__(self) -> int:
                return 2

            def close(self) -> None:
                pass

        marker_package = types.ModuleType("marker")
        marker_package.__path__ = []
        config_package = types.ModuleType("marker.config")
        config_package.__path__ = []
        converter_package = types.ModuleType("marker.converters")
        converter_package.__path__ = []
        config_parser_module = types.ModuleType("marker.config.parser")
        config_parser_module.ConfigParser = FakeConfigParser
        pdf_converter_module = types.ModuleType("marker.converters.pdf")
        pdf_converter_module.PdfConverter = FakePdfConverter
        models_module = types.ModuleType("marker.models")
        models_module.create_model_dict = created_model_dict
        models_module.shutdown_models = shutdown_models
        output_module = types.ModuleType("marker.output")
        output_module.text_from_rendered = lambda rendered: (
            f"## {rendered}\n\n$$x^2$$",
            None,
            None,
        )
        torch_module = types.ModuleType("torch")
        torch_module.cuda = types.SimpleNamespace(
            is_available=lambda: True,
            empty_cache=empty_cache,
        )
        pdfium_module = types.ModuleType("pypdfium2")
        pdfium_module.PdfDocument = FakePdfDocument

        modules = {
            "torch": torch_module,
            "pypdfium2": pdfium_module,
            "marker": marker_package,
            "marker.config": config_package,
            "marker.config.parser": config_parser_module,
            "marker.converters": converter_package,
            "marker.converters.pdf": pdf_converter_module,
            "marker.models": models_module,
            "marker.output": output_module,
        }

        with patch.dict(sys.modules, modules):
            with patch.dict(
                os.environ,
                {"TORCH_DEVICE": "cpu", "MATH_PDF_MODE": "fast"},
            ):
                output = io.StringIO()
                with contextlib.redirect_stdout(output):
                    markdown = process_math_pdf.convert_pdf_to_markdown(pdf_path)
                self.assertEqual("cuda", os.environ["TORCH_DEVICE"])
                self.assertEqual("1", os.environ["DETECTOR_BATCH_SIZE"])
                self.assertEqual("1", os.environ["OCR_ERROR_BATCH_SIZE"])
                self.assertEqual("1", os.environ["FAST_LAYOUT_BATCH_SIZE"])

        self.assertEqual(
            "## rendered-0\n\n$$x^2$$\n\n## rendered-1\n\n$$x^2$$",
            markdown,
        )
        self.assertEqual(
            [
                "PAGE_PROGRESS page=1/2 started",
                "PAGE_PROGRESS page=1/2 completed",
                "PAGE_PROGRESS page=2/2 started",
                "PAGE_PROGRESS page=2/2 completed",
            ],
            output.getvalue().strip().splitlines(),
        )
        self.assertEqual([[0], [1]], [
            config["page_range"] for config in converter_configs
        ])
        self.assertTrue(all(config["pdftext_workers"] == 1 for config in converter_configs))
        self.assertTrue(all(not config["cache_pdftext_pages"] for config in converter_configs))
        created_model_dict.assert_called_once_with()
        shutdown_models.assert_called_once_with(model_dict)
        self.assertGreaterEqual(empty_cache.call_count, 2)

    def test_converter_rejects_missing_cuda_instead_of_falling_back_to_cpu(self) -> None:
        marker_package = types.ModuleType("marker")
        marker_package.__path__ = []
        config_package = types.ModuleType("marker.config")
        config_package.__path__ = []
        converter_package = types.ModuleType("marker.converters")
        converter_package.__path__ = []
        torch_module = types.ModuleType("torch")
        torch_module.cuda = types.SimpleNamespace(is_available=lambda: False)
        modules = {
            "torch": torch_module,
            "pypdfium2": types.ModuleType("pypdfium2"),
            "marker": marker_package,
            "marker.config": config_package,
            "marker.config.parser": types.ModuleType("marker.config.parser"),
            "marker.converters": converter_package,
            "marker.converters.pdf": types.ModuleType("marker.converters.pdf"),
            "marker.models": types.ModuleType("marker.models"),
            "marker.output": types.ModuleType("marker.output"),
        }
        modules["marker.config.parser"].ConfigParser = object
        modules["marker.converters.pdf"].PdfConverter = object
        modules["marker.models"].create_model_dict = lambda: {}
        modules["marker.models"].shutdown_models = lambda _: None
        modules["marker.output"].text_from_rendered = lambda _: ("", None, None)
        modules["pypdfium2"].PdfDocument = Mock()

        with patch.dict(sys.modules, modules):
            with self.assertRaisesRegex(RuntimeError, "CUDA is unavailable"):
                process_math_pdf.convert_pdf_to_markdown(Path("math-review.pdf"))

    def test_process_pdf_writes_markdown_to_output_file(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            pdf_path = Path(directory) / "review.pdf"
            output_path = Path(directory) / "review.md"
            pdf_path.write_bytes(b"%PDF-1.7")

            with patch(
                "process_math_pdf.convert_pdf_to_markdown",
                return_value="## Equations\n\n$y=mx+b$",
            ):
                process_math_pdf.process_pdf(pdf_path, output_path)

            self.assertEqual(
                "## Equations\n\n$y=mx+b$\n",
                output_path.read_text(encoding="utf-8"),
            )


if __name__ == "__main__":
    unittest.main()
