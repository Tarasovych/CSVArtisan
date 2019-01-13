<?php

namespace Tarasovych\CSVArtisan\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class CSVImport
 * @property string CSVPattern
 * @property array validMIMEs
 * @property array files
 * @property string selectedCSVFile
 * @property Model model
 * @property array CSVHeaders
 * @property array CSVArray
 * @property int chunkSize
 * @property Collection collection
 *
 * @package Tarasovych\CSVArtisan\Commands
 */
final class CSVImport extends Command
{
    private $CSVPattern = '*.csv';
    private $validMIMEs = ['application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv'];

    private $files = [];

    private $selectedCSVFile;

    private $model;

    private $CSVHeaders = [];
    private $CSVArray = [];

    private $collection;
    private $chunkSize = 100;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for import CSV file into Model-related table';

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
        $this->findFiles();
        $this->selectCSV();
        $this->findModel();
        $this->prepareCSV();
        $this->verifyModel();
        $this->prepareCollection();
        $this->import();
    }

    /**
     * @return void
     */
    private function findFiles()
    {
        $csvFiles = glob($this->CSVPattern);
        foreach ($csvFiles as $file) {
            if ($this->validateMIME($file)) {
                $this->files[] = [
                    'name' => $file,
                    'time' => date('d-m-Y H:i:s', filemtime($file))
                ];
            }
        }
    }

    /**
     * @param $file
     * @return bool
     */
    private function validateMIME($file)
    {
        if (in_array(mime_content_type($file), $this->validMIMEs))
            return true;
    }

    /**
     * @return void
     */
    private function selectCSV()
    {
        if ($this->files) {
            $this->table(['Name', 'Last modified time'], $this->files);

            $fileNames = [];
            foreach ($this->files as $file) {
                $fileNames[] = $file['name'];
            }
            $this->selectedCSVFile = $this->choice('Choose file', $fileNames);
        } else {
            $this->info('No csv files were found.');
            exit;
        }
    }

    /**
     * @return void
     */
    private function findModel()
    {
        $model = $this->ask('Enter model namespace');
        if (class_exists($model) && new $model() instanceof Model) {
            $this->model = new $model();
        } else {
            $this->error("{$model} not found");
            exit;
        }
    }

    /**
     * @return void
     */
    private function prepareCSV()
    {
        $i = 0;
        if (($handle = fopen($this->selectedCSVFile, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                if (empty($this->CSVHeaders)) {
                    $this->CSVHeaders = $row;
                    continue;
                }
                foreach ($row as $key => $value) {
                    $this->CSVArray[$i][$this->CSVHeaders[$key]] = $value;
                }
                $i++;
            }
            fclose($handle);
        } else {
            $this->error('CSV reading error.');
            exit;
        }
    }

    /**
     * @return void
     */
    private function verifyModel()
    {
        $fillable = $this->model->getFillable();
        if ($fillable) {
            foreach ($this->CSVHeaders as $header) {
                if (!in_array($header, $fillable)) {
                    $this->error('CSV fields are different from model fillable fields');
                    exit;
                }
            }
            $this->confirm('Model and CSV are ok. Fields to be imported: ' . implode(', ', $this->CSVHeaders) . '. Proceed?');
        }
    }

    /**
     * @return void
     */
    private function prepareCollection()
    {
        $this->collection = collect($this->CSVArray);
        if (count($this->collection) > $this->chunkSize) {
            $this->collection = $this->collection->chunk($this->chunkSize)->toArray();
        }
    }

    /**
     * @return void
     */
    private function import()
    {
        try {
            foreach ($this->collection as $item) {
                $result = $this->model::insert($item);
            }
            if ($result)
                $this->info(count($this->CSVArray) . ' records have been successfully inserted in the ' . get_class($this->model));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit;
        }
    }
}