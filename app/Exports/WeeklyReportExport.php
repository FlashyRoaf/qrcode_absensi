<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class WeeklyReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    // WithStyles,
    WithTitle,
    WithColumnWidths,
    WithEvents
{
    public function __construct(
        private readonly Collection $reports,
        private readonly array      $filters = []
    ) {}

    // ── Data ──────────────────────────────────────────────────────────────────

    public function collection(): Collection
    {
        $data = $this->reports;

        // Terapkan filter pencarian
        if (!empty($this->filters['search'])) {
            $q = strtolower($this->filters['search']);
            $data = $data->filter(fn($r) =>
                str_contains((string) $r['user_id'], $q) ||
                str_contains(strtolower($r['user_name'] ?? ''), $q) ||
                str_contains((string) $r['week_start'], $q)
            );
        }

        // Terapkan filter status
        if (!empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $data = $data->filter(fn($r) => $r['status'] === $this->filters['status']);
        }

        if (!empty($this->filters['week_start'])) {
            $data = $data->filter(fn($r) => $r['week_start'] === $this->filters['week_start']);
        }

        // Reset array keys
        return $data->values();
    }

    public function headings(): array
    {
        return [
            'No',
            'User ID',
            'Nama User',
            'Minggu Mulai',
            'Total Menit',
            'Total Jam',
            'Status',
            'Progress (%)',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        $minutes  = (int) $row['total_minutes'];
        $hours    = floor($minutes / 60);
        $mins     = $minutes % 60;
        $progress = min(100, round(($minutes / 960) * 100));

        return [
            $no,
            $row['user_id'],
            $row['user_name'] ?? "User #{$row['user_id']}",
            $this->formatWeek($row['week_start']),
            $minutes,
            "{$hours}j {$mins}m",
            $row['status'] === 'memenuhi' ? 'Memenuhi' : 'Tidak Memenuhi',
            $progress,
        ];
    }

    // ── Styling ───────────────────────────────────────────────────────────────

    // public function styles(Worksheet $sheet): array
    // {
    //     return [
    //         // Baris header (baris 1 = summary, baris 2 = kolom header)
    //         3 => [
    //             'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    //             'fill' => [
    //                 'fillType'   => Fill::FILL_SOLID,
    //                 'startColor' => ['argb' => 'FF10B981'], // emerald-500
    //             ],
    //             'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    //         ],
    //     ];
    // }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 10,  // User ID
            'C' => 24,  // Nama
            'D' => 18,  // Minggu Mulai
            'E' => 14,  // Total Menit
            'F' => 12,  // Total Jam
            'G' => 18,  // Status
            'H' => 14,  // Progress
        ];
    }

    public function title(): string
    {
        return 'Weekly Report';
    }

    public function registerEvents(): array
    {
        $reports      = $this->reports;
        
        // Filter array data
        $memenuhi     = $reports->where('status', 'memenuhi')->count();
        $total        = $reports->count();
        $compliance   = $total ? round(($memenuhi / $total) * 100) : 0;
        $totalUsers   = $reports->pluck('user_id')->unique()->count();
        $totalWeeks   = $reports->pluck('week_start')->unique()->count();

        return [
            AfterSheet::class => function (AfterSheet $event) use (
                $memenuhi, $total, $compliance, $totalUsers, $totalWeeks
            ) {
                $sheet = $event->sheet->getDelegate();

                // ── Insert 2 baris di atas untuk summary ──────────────────────
                $sheet->insertNewRowBefore(1, 2);

                // Row 1: Judul
                $sheet->setCellValue('A1', '📋 Weekly Work Report — Admin Panel');
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF10B981']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF18181B']],
                ]);

                // Row 2: Summary stats
                $exportedAt = now()->setTimezone('Asia/Makassar')->format('d M Y, H:i') . ' WITA';
                $sheet->setCellValue('A2', "Total User: {$totalUsers}  |  Total Laporan: {$total}  |  Minggu: {$totalWeeks}  |  Memenuhi: {$memenuhi}  |  Kepatuhan: {$compliance}%  |  Diekspor: {$exportedAt}");
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 9, 'color' => ['argb' => 'FFA1A1AA']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF18181B']],
                ]);

                // ── Style per baris data (mulai baris 4) ──────────────────────
                $lastRow = $sheet->getHighestRow();

                if ($lastRow >= 4) {
                    for ($row = 4; $row <= $lastRow; $row++) {
                        $statusCell = $sheet->getCell("G{$row}")->getValue();
                        $isMemenuhi = $statusCell === 'Memenuhi';

                        // Warna kolom Status
                        $sheet->getStyle("G{$row}")->applyFromArray([
                            'font' => [
                                'bold'  => true,
                                'color' => ['argb' => $isMemenuhi ? 'FF10B981' : 'FFEF4444'],
                            ],
                        ]);

                        // Warna kolom Total Jam
                        $sheet->getStyle("F{$row}")->applyFromArray([
                            'font' => [
                                'bold'  => true,
                                'color' => ['argb' => $isMemenuhi ? 'FF10B981' : 'FFEF4444'],
                            ],
                        ]);

                        // Zebra striping
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF27272A']],
                                // 'font' => ['color' => ['argb' => 'E4E4E7']]
                            ]);
                            $sheet->getStyle("H{$row}")->applyFromArray([
                                'font' => ['color' => ['argb' => 'E4E4E7']]
                            ]);
                            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                                'font' => ['color' => ['argb' => 'E4E4E7']]
                            ]);

                        }
                    }

                    // ── Border seluruh tabel ───────────────────────────────────────
                    $sheet->getStyle("A3:H{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['argb' => 'FF3F3F46'],
                            ],
                        ],
                    ]);
                }

                // ── Freeze pane di bawah header ───────────────────────────────
                $sheet->freezePane('A4');

                // ── Auto filter ───────────────────────────────────────────────
                $sheet->setAutoFilter("A3:H3");

                // ── Alignment kolom tertentu ──────────────────────────────────
                $sheet->getStyle("A4:A{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E4:E{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H4:H{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function formatWeek(string $dateStr): string
    {
        return Carbon::parse($dateStr)
            ->locale('id')
            ->isoFormat('DD MMM YYYY');
    }
}