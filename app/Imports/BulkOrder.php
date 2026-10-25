<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToArray;
 
class BulkOrder implements ToArray
{
    public $rows;

    public function array(array $array)
    {
		array_shift($array);
        $this->rows = $array;
    }
}
