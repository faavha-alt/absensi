<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Builder Excel generik: judul + header + baris (array nilai).
 * Dipakai untuk laporan yang tidak butuh struktur khusus.
 */
class GenericExcelBuilder
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     * @param  array<int, int>  $widths  lebar kolom (opsional)
     */
    public function build(string $title, array $headers, iterable $rows, array $widths = []): string
    {
        $options = new Options;
        $options->mergeCells(0, 1, max(0, count($headers) - 1), 1);

        $writer = new Writer($options);

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_').'.xlsx';
        $writer->openToFile($tempPath);

        $sheet = $writer->getCurrentSheet();
        foreach ($headers as $i => $_) {
            $sheet->setColumnWidthForRange($widths[$i] ?? 18, $i, $i);
        }

        $border = new Border(
            new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::RIGHT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
        );

        $titleStyle = (new Style)->setFontBold()->setFontSize(14)->setCellAlignment(CellAlignment::CENTER);
        $headerStyle = (new Style)
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('4F46E5')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setBorder($border);
        $dataStyle = (new Style)->setBorder($border);

        $writer->addRow(Row::fromValues([$title], $titleStyle));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_values($row), $dataStyle));
        }

        $writer->close();

        return $tempPath;
    }
}
