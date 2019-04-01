<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\tblProductData;

class ImportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {file_name} {mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import CSV is a micro application allows user to import files using CMD';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "@app-message: Processing...\n";

        $file_name  = $this->argument('file_name'); // first parameter of the command
        $mode       = $this->argument('mode');      // second parameter of the command test/prod
        $file_path  = Storage::disk('local')->path($file_name);
        $file       = fopen($file_path, "r");

        while ( ($data = fgetcsv($file, 200, ",")) !==FALSE ) 
        {   
            $stock_level  = (isset($data[3]) && !empty($data[3]) && is_numeric($data[3]) ) ? $data[3] : null;
            $cost         = (isset($data[4]) && !empty($data[4]) && is_numeric($data[4])) ? $data[4] : null;
            $discontinued = (isset($data[5]) && !empty($data[5]) && $data[5]=='yes' ) ? "yes" : "no";

            if( 
                ($cost<5 && $stock_level<10.00) || 
                ( $cost>1000 ) || 
                $discontinued == "no" ||
                is_null($stock_level) ||
                is_null($cost)
            ) {
                $not_recorded = [
                    'strProductCode' => $data[0],
                    'strProductName' => $data[1],
                    'strProductDesc' => $data[2],
                    'dtmAdded' => date('Y-m-d H:i:s'),
                    'discontinuedRecord' => $discontinued,
                    'intStockLevel' => $stock_level,
                    'dcmCost' => $cost
                ];

                $notRecordedData[] = $not_recorded;
                
            } else {

                $record = [
                    'strProductCode' => $data[0],
                    'strProductName' => $data[1],
                    'strProductDesc' => $data[2],
                    'dtmAdded' => date('Y-m-d H:i:s'),
                    'dtmDiscontinued' => ( $discontinued == 'yes' ) ? date('Y-m-d H:i:s') : null,
                    'intStockLevel' => $stock_level,
                    'dcmCost' => $cost
                ];

                $recordData[] = $record; 
            }
        }

        fclose($file);

        $total_successful_items = count($recordData);
        $total_skipped_items = count($notRecordedData);
        $total_processed_items = $total_skipped_items + $total_successful_items;

        switch($mode)
        {
            case 'prod': 
                    $status = tblProductData::processCsv($recordData);
            break;
            
            case 'test':
                    echo "@app-message: You are in test mode therfore no actual data will be imported.";
                    $status = true;
            break;

            default:
                    echo "@app-message: Invalid mode use test/prod only.";
                    $status = false;
            break;
        }

        if(!$status) 
        {
            $total_processed_items = 0;
            $total_successful_items = 0;
            $total_skipped_items = 0;
        } else {
            echo "\n\n------------------------------------------------------------------------------------------------------------\n";
            echo "| Skipped Item List                                                                                        |\n";
            echo "------------------------------------------------------------------------------------------------------------\n";

            foreach($notRecordedData as $value)
            {
                echo "|".str_pad($value['strProductCode'],15)."|".str_pad(str_replace('”', '', $value['strProductName']), 20)."|".str_pad(str_replace('”', '', $value['strProductDesc']), 40)."|".str_pad($value['discontinuedRecord'], 5)."|".str_pad($value['intStockLevel'], 6)."|".str_pad($value['dcmCost'], 15)."|"."\n------------------------------------------------------------------------------------------------------------\n";
            }
        }

        echo "\n---------------------------\n";
        echo "|Import Result : ".$mode."     |\n";
        echo "---------------------------\n";
        echo "|Processed Items:  |".str_pad($total_processed_items, 6)."|\n";
        echo "---------------------------\n";
        echo "|Successful Items: |".str_pad($total_successful_items, 6)."|\n";
        echo "---------------------------\n";
        echo "|Skipped Items:    |".str_pad($total_skipped_items, 6)."|\n";
        echo "---------------------------\n";

        echo "\n@app-message: Program Terminiated.";
    }
}
