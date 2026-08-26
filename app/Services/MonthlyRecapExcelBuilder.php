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

class MonthlyRecapExcelBuilder
{
    protected const HEADERS = ['Pegawai', 'Jumlah Hadir', 'Jumlah Telat', 'Total Menit Telat', 'Jumlah Pulang Cepat'];

    /**
     * Bangun laporan Excel formal (judul, header tebal, border) dan simpan ke file sementara.
     * Mengembalikan path file yang harus dihapus pemanggil setelah selesai dikirim.
     */
    public function build(Collection $rows, string $bulanNama, int $tahun): string
    {
        $options = new Options;
        $options->mergeCells(0, 1, count(self::HEADERS) - 1, 1);

        $writer = new Writer($options);

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_').'.xlsx';
        $writer->openToFile($tempPath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidthForRange(28, 0, 0);
        $sheet->setColumnWidthForRange(18, 1, count(self::HEADERS) - 1);

        $border = new Border(
            new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::RIGHT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
        );

        $titleStyle = (new Style)
            ->setFontBold()
            ->setFontSize(14)
            ->setCellAlignment(CellAlignment::CENTER);

        $headerStyle = (new Style)
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('465FFF')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setBorder($border);

        $dataStyle = (new Style)->setBorder($border);

        $writer->addRow(Row::fromValues(
            ['REKAP BULANAN ABSENSI - '.strtoupper($bulanNama).' '.$tahun],
            $titleStyle
        ));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(self::HEADERS, $headerStyle));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues([
                $row->employee?->name ?? '-',
                (int) $row->total_hadir,
                (int) $row->jumlah_telat,
                (int) $row->total_menit_telat,
                (int) $row->jumlah_pulang_cepat,
            ], $dataStyle));
        }

        $writer->close();

        return $tempPath;
    }
}
