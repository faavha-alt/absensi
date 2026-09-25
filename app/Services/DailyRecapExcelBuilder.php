<?php

namespace App\Services;

use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class DailyRecapExcelBuilder
{
    protected const HEADERS = ['No', 'Tanggal', 'Pegawai', 'Jam Masuk', 'Jam Pulang', 'Telat (menit)', 'Pulang Cepat (menit)', 'Status'];

    /**
     * Bangun laporan Excel rekap harian. Mengembalikan path file sementara
     * (pemanggil bertanggung jawab menghapusnya setelah dikirim).
     */
    public function build(Collection $rows, string $periode): string
    {
        $options = new Options;
        $options->mergeCells(0, 1, count(self::HEADERS) - 1, 1);

        $writer = new Writer($options);

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_harian_').'.xlsx';
        $writer->openToFile($tempPath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidthForRange(6, 0, 0);
        $sheet->setColumnWidthForRange(14, 1, 1);
        $sheet->setColumnWidthForRange(28, 2, 2);
        $sheet->setColumnWidthForRange(16, 3, 7);

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

        $writer->addRow(Row::fromValues(['REKAP HARIAN ABSENSI - '.$periode], $titleStyle));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(self::HEADERS, $headerStyle));

        foreach ($rows as $i => $row) {
            $writer->addRow(Row::fromValues([
                $i + 1,
                $row->date?->format('d/m/Y') ?? '-',
                $row->employee?->name ?? '-',
                $row->check_in?->format('H:i:s') ?? '-',
                $row->check_out?->format('H:i:s') ?? '-',
                (int) $row->late_minutes,
                (int) $row->early_leave_minutes,
                $row->status()['label'],
            ], $dataStyle));
        }

        $writer->close();

        return $tempPath;
    }
}
