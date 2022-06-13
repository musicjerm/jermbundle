<?php

namespace Musicjerm\Bundle\JermBundle\Model;

class CSVDataModel
{
    private array $columnNames;
    private array $data;

    /**
     * @param array $columnNames
     * @return CSVDataModel
     */
    public function setColumnNames(array $columnNames): self{
        $this->columnNames = $columnNames;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data): self{
        $this->data = $data;
        return $this;
    }

    public function buildCsv(){
        $output = fopen('php://temp', 'rb+');
        if (isset($this->columnNames)){
            fputcsv($output, $this->columnNames);
        }
        foreach($this->data as $line){
            fputcsv($output, $line);
        }
        rewind($output);
        $csv_dump = stream_get_contents($output);
        fclose($output);

        return $csv_dump;
    }
}