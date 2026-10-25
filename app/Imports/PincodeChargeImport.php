<?php
	
	namespace App\Imports;
	use App\Models\PincodeService;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Collection;
	use Maatwebsite\Excel\Concerns\ToCollection;
	use Maatwebsite\Excel\Concerns\WithStartRow;
	use Maatwebsite\Excel\Concerns\WithChunkReading;
	use Maatwebsite\Excel\Concerns\WithMultipleSheets;
	class PincodeChargeImport implements ToCollection,WithStartRow, WithChunkReading
	{
		/**
			* @param array $row
			*
			* @return \Illuminate\Database\Eloquent\Model|null
		*/
		public function collection(Collection $rows)
		{
			$data = [];
			foreach($rows as $row)
			{
				if(!PincodeService::where('origin_pincode',$row[0])->where('des_pincode',$row[5])->exists())
				{
					$data = [
						'origin_pincode'=>$row[0],
						'origin_city'=>$row[1],
						'origin_state'=>$row[2],
						'origin_center'=>$row[3],
						'origin_serviceable'=>$row[4],
						'des_pincode'=>$row[5],
						'des_city'=>$row[6],
						'des_state'=>$row[7],
						'shipping_charge'=>$row[8],
						'created_at'=>date('Y-m-d H:i:s'),
						'updated_at'=>date('Y-m-d H:i:s'),
					];
					
					PincodeService::insert($data);
				}
			} 
		}	
		public function startRow(): int
		{
			return 2;
		}
		public function chunkSize(): int
		{
			return 1000;
		}
	}
