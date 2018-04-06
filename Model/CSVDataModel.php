<?php

namespace Musicjerm\Bundle\JermBundle\Model;

class CSVDataModel
{
    private $columnNames;
    private $data;

    /**
     * @param array $columnNames
     * @return CSVDataModel
     */
    public function setColumnNames($columnNames){
        strtolower($columnNames[0]) != 'id' ?: $columnNames[0] .= ' ';
        $this->columnNames = $columnNames;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData($data){
        $this->data = $data;
        return $this;
    }

    public function buildCsv(){
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $this->columnNames);
        foreach($this->data as $line){
            fputcsv($output, $line);
        }
        rewind($output);
        $csv_dump = stream_get_contents($output);
        fclose($output);

        return $csv_dump;
    }
}