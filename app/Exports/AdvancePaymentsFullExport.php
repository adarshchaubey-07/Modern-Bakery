<?php

namespace App\Exports;

use App\Models\Agent_Transaction\AdvancePayment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AdvancePaymentsFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function collection()
    {
        $rows = [];
        $query = AdvancePayment::with(['companyBank', 'agent']);
          if ($this->uuid) 
        {
            $query->where('uuid', $this->uuid); 
        }
        $payments = $query->get();
        foreach ($payments as $payment) {
            $rows[] = [
                'OSA Code'             => (string)($payment->osa_code ?? ''),
                'Payment Type'         => match($payment->payment_type) {
                    1 => 'Cash',
                    2 => 'Cheque',
                    3 => 'Transfer',
                    default => '-',
                },
                'Company Bank Name'    => (string)($payment->companyBank->bank_name ?? ''),
                'Company Account No'   => (string)($payment->companyBank->account_number ?? ''),
                'Company Branch'       => (string)($payment->companyBank->branch ?? ''),
                'Agent Bank Name'      => (string)($payment->agent->bank_name ?? ''),
                'Agent Account Number' => (string)($payment->agent->bank_account_number ?? ''),
                'Amount'               => (float)($payment->amount ?? 0),
                'Receipt No'           => (string)($payment->recipt_no ?? ''),
                'Receipt Date'         => (string)($payment->recipt_date ? $payment->recipt_date->format('Y-m-d') : ''),
                'Cheque No'            => (string)($payment->cheque_no ?? ''),
                'Cheque Date'          => (string)($payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : ''),
                // 'Receipt Image'        => (string)($payment->recipt_image ?? '-'),
                'Status'               => (string)($payment->status == 1 ? 'Active' : 'Inactive'),
            ];
        }

        return new Collection($rows);
    }

    /**
     * Define the Excel column headings.
     */
    public function headings(): array
    {
        return [
            'OSA Code',
            'Payment Type',
            'Company Bank Name',
            'Company Account No',
            'Company Branch',
            'Agent Bank Name',
            'Agent Account Number',
            'Amount',
            'Receipt No',
            'Receipt Date',
            'Cheque No',
            'Cheque Date',
            'Receipt Image',
            'Status',
        ];
    }

    /**
     * Style the Excel sheet after creation.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // Style header row
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'], // Burgundy red
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
