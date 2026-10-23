<?php

namespace App\Services;

use App\Models\KnowledgeDocument;
use RuntimeException;

class DocumentTextExtractionService
{
    public function __construct(
        private readonly PdfTextExtractor $pdfTextExtractor,
        private readonly KnowledgeTextNormalizer $knowledgeTextNormalizer,
    ) {}

    public function extract(KnowledgeDocument $document, string $path): ExtractedDocumentText
    {
        $extractors = [$this->pdfTextExtractor];

        foreach ($extractors as $extractor) {
            if ($extractor->supports($document->mime_type, $document->extension)) {
                return $this->knowledgeTextNormalizer->normalize($extractor->extract($path));
            }
        }

        throw new RuntimeException(
            'Text extraction is not yet supported for '.$document->extension.' documents.'
        );
    }
}
