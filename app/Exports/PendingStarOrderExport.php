<?php
	
	namespace App\Exports; 
	use App\Models\Order;
	use App\Models\Customer; 
	use Maatwebsite\Excel\Concerns\FromCollection; 
	use Maatwebsite\Excel\Concerns\WithHeadings;
	class PendingStarOrderExport implements FromCollection,WithHeadings
	{
		/**
			* @return \Illuminate\Support\Collection
		*/
		
		public function collection()
		{  
			$orders =  Order::where('shipping_company_id',3)->where('status_courier','pending pickup')->get();
			$values = [];
			foreach($orders as $order)
			{
				$customer = Customer::where('id',$order->customer_id)->first();
				$values[] = ['order_id'=>$order->awb_number,'awb_number'=>'','carrier_name'=>'','customer_email'=>$customer->email,'customer_phone'=>$customer->mobile,'first_name'=>$customer->first_name,'last_name'=>$customer->last_name,'products'=>'','country_code'=>'IN','order_data'=>'','shipment_type'=>''];	 
			}
			return collect($values); 
		}
		
		public function headings(): array
		{
			return ["Order ID", "AWB NO", "Carrier Name", "Customer Email", "Customer Phone", "Customer First Name", "Customer Last Name", "Products", "Country Code", "Order Data", "Shipment Type"];
		}
	}
