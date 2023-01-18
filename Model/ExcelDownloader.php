<?php

namespace Musicjerm\Bundle\JermBundle\Model;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class ExcelDownloader
{
    protected ?Spreadsheet $workbook = null;

    protected ?XlsxReader $reader = null;

    /** @var Worksheet[] */
    protected array $sheets = [];

    protected array $sheetProperties = [];

    protected ?Xlsx $xlsxWriter = null;

    protected ?string $filePath = null;

    protected ?string $fileName = null;

    protected ?string $completeFileName = null;

    protected string $colorBlue = '337ab7';

    protected string $colorGreen = '00a65a';

    protected string $colorRed = 'dd4b39';

    protected string $fillGrey = 'f7f7f7';

    protected string $fillBlue = 'DCE6F1';

    protected string $fillYellow = 'fcf8e3';

    protected string $fillOrange = 'FDE9D9';

    protected string $fillPurple = 'E4DFEC';

    protected string $fillRed = 'F2DCDB';

    protected string $fillGreen = 'EBF1DE';

    protected string $accountingCode = '$#,##0.00_);[Red]($#,##0.00)';

    protected array $leftAlign = ['alignment' => ['horizontal' => 'left']];

    protected array $formatBorders = array(
        'horizontal' => array(
            'borderStyle' => 'thin',
            'color' => ['argb' => 'aaaaaa']
        ),
        'outline' => array(
            'borderStyle' => 'thin',
            'color' => ['argb' => '777777']
        )
    );

    /** @var array */
    protected array $formatHeaders = array(
        'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'f7f7f7']],
        'font' => ['bold' => true]
    );

    public function __construct()
    {
        $this->workbook = new Spreadsheet();
    }

    public function getColumnLetter(int $int): string
    {
        return Coordinate::stringFromColumnIndex($int);
    }

    public function setFile(string $path, string $name): void
    {
        $this->filePath = $path;
        $this->fileName = $name;
        $this->completeFileName = "$path/$name.xlsx";
    }

    public function getFile(): string
    {
        return $this->completeFileName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getFileName(): string
    {
        return $this->fileName . '.xlsx';
    }

    /** @throws Exception */
    public function setSheets(array $sheets): void
    {
        foreach ($sheets as $key => $sheetName){
            $this->sheets[$key] = $key === 0 ? $this->workbook->getSheet($key) : $this->workbook->createSheet($key);
            $this->sheets[$key]->setTitle($sheetName);
        }
    }

    /** @throws \PhpOffice\PhpSpreadsheet\Reader\Exception */
    public function openFile(string $filename, ?array $readSheets = null): void
    {
        $this->reader = new XlsxReader();

        if ($readSheets !== null){
            $this->reader->setLoadSheetsOnly($readSheets);
        }else{
            $this->reader->setLoadAllSheets();
        }

        $this->workbook = $this->reader->load($filename);
        $this->sheets = $this->workbook->getAllSheets();
    }

    public function setSheetData(int $sheetNumber, array $data): void
    {
        $this->sheets[$sheetNumber]->fromArray($data);
        $this->sheetProperties[$sheetNumber]['lastRow'] = \count($data);
        $this->sheetProperties[$sheetNumber]['lastCol'] = \count($data[0]);
    }

    /** @throws \PhpOffice\PhpSpreadsheet\Writer\Exception */
    public function saveFile(): void
    {
        // make sure directory exists
        !is_dir($this->filePath) && !mkdir($this->filePath) && !is_dir($this->filePath);

        // create writer and save file
        $writer = new Xlsx($this->workbook);
        $writer->save($this->completeFileName);
    }

    public function formatSheetHeaders(int $sheet, string $cellRange): void
    {
        $this->sheets[$sheet]->getStyle($cellRange)->applyFromArray($this->formatHeaders);
    }

    public function formatSheetBorders(int $sheet, string $cellRange): void
    {
        $this->sheets[$sheet]->getStyle($cellRange)->applyFromArray(array(
            'borders' => $this->formatBorders
        ));
    }

    public function formatSheetCellRangeBackground(int $sheet, string $cellRange, string $presetColor): void
    {
        $this->sheets[$sheet]->getStyle($cellRange)->applyFromArray(array(
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => $this->$presetColor]]
        ));
    }

    public function formatSheetCellRangeTextColor(int $sheet, string $cellRange, string $presetColor): void
    {
        $this->sheets[$sheet]->getStyle($cellRange)->applyFromArray(array(
            'font' => ['color' => ['argb' => $this->$presetColor]]
        ));
    }

    /** @throws Exception */
    public function freezeSheetPane(int $sheet, string $cell): void
    {
        $this->sheets[$sheet]->freezePane($cell);
    }

    public function enableSheetFilters(int $sheet, string $cellRange): void
    {
        $this->sheets[$sheet]->setAutoFilter($cellRange);
    }

    public function leftAlignSheetRange(int $sheet, string $cellRange): void
    {
        $this->sheets[$sheet]->getStyle($cellRange)->applyFromArray($this->leftAlign);
    }

    public function setSheetColAutoWidth(int $sheet, string $col): void
    {
        $this->sheets[$sheet]->getColumnDimension($col)->setAutoSize(true);
    }

    public function setSheetColWidth(int $sheet, string $col, int $width): void
    {
        $this->sheets[$sheet]->getColumnDimension($col)->setWidth($width);
    }

    public function setSheetCursor(int $sheet, string $cellRange = "A1"): void
    {
        $this->sheets[$sheet]->getStyle($cellRange);
    }

    public function num2alpha($n): string
    {
        for($r = ""; $n >= 0; $n = (int)($n / 26) - 1) {
            $r = chr($n % 26 + 0x41) . $r;
        }
        return $r;
    }

    public function setSheetCellValue(int $sheet, string $cell, $value): void
    {
        $this->sheets[$sheet]->setCellValue($cell, $value);
    }

    public function setExplicitTextValue(int $sheet, string $cell, mixed $value, string $formatCode): void
    {
        if ($formatCode === 'text'){
            $this->sheets[$sheet]->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
        }

        if ($formatCode === 'currency'){
            $this->sheets[$sheet]->setCellValueExplicit($cell, (float) $value, DataType::TYPE_NUMERIC);
        }
    }

    public function setSheetRangeFormatText(int $sheet, string $cellRange, string $formatCode): void
    {
        if ($formatCode === 'text'){
            $this->sheets[$sheet]->getStyle($cellRange)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }

        if ($formatCode === 'currency'){
            $this->sheets[$sheet]->getStyle($cellRange)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_ACCOUNTING_USD);
        }
    }
}