<?php

namespace App\Services\Documents;

class SimplePdfService
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const LEFT = 50;
    private const TOP = 790;
    private const LINE_HEIGHT = 16;
    private const BOTTOM = 55;

    /**
     * Build a small text-only PDF without requiring a rendering package.
     * This is intentionally simple; official formats can replace the line data later.
     *
     * @param  array<int, string>  $lines
     */
    public function make(string $title, array $lines): string
    {
        $pages = $this->paginate($title, $lines);
        $objects = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $pageRefs = [];
        $objectNumber = 3;

        foreach ($pages as $pageLines) {
            $pageObject = $objectNumber++;
            $contentObject = $objectNumber++;
            $pageRefs[] = "{$pageObject} 0 R";

            $objects[$pageObject] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> >> >> /Contents %d 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $contentObject,
            );

            $stream = $this->contentStream($pageLines);
            $objects[$contentObject] = "<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageRefs).'] /Count '.count($pageRefs).' >>';

        ksort($objects);

        return $this->assemble($objects);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array<int, array{0: string, 1: int}>>
     */
    private function paginate(string $title, array $lines): array
    {
        $page = [
            [$this->clean($title), 16],
            ['', 10],
        ];
        $pages = [];
        $y = self::TOP - (self::LINE_HEIGHT * 2);

        foreach ($lines as $line) {
            foreach ($this->wrap($line) as $wrapped) {
                if ($y < self::BOTTOM) {
                    $pages[] = $page;
                    $page = [
                        [$this->clean($title), 14],
                        ['', 10],
                    ];
                    $y = self::TOP - (self::LINE_HEIGHT * 2);
                }

                $page[] = [$this->clean($wrapped), 10];
                $y -= self::LINE_HEIGHT;
            }
        }

        $pages[] = $page;

        return $pages;
    }

    /**
     * @return array<int, string>
     */
    private function wrap(string $line): array
    {
        if (trim($line) === '') {
            return [''];
        }

        return explode("\n", wordwrap($line, 92, "\n", true));
    }

    /**
     * @param  array<int, array{0: string, 1: int}>  $lines
     */
    private function contentStream(array $lines): string
    {
        $stream = [];
        $y = self::TOP;

        foreach ($lines as [$line, $size]) {
            $font = $size >= 14 ? 'F2' : 'F1';
            $stream[] = sprintf(
                'BT /%s %d Tf %d %d Td (%s) Tj ET',
                $font,
                $size,
                self::LEFT,
                $y,
                $this->escape($line),
            );
            $y -= self::LINE_HEIGHT;
        }

        return implode("\n", $stream);
    }

    /**
     * @param  array<int, string>  $objects
     */
    private function assemble(array $objects): string
    {
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i < $size; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function clean(string $value): string
    {
        $value = str_replace(["\xE2\x82\xB9", "\xE2\x80\x93", "\xE2\x80\x94", "\xC2\xB7"], ['Rs.', '-', '-', '-'], $value);
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value) ?? '';

        return trim($value);
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
