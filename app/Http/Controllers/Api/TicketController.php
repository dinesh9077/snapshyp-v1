<?php
	
	namespace App\Http\Controllers\Api;
	
	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use App\Models\Ticket;
	use App\Models\User;
	use App\Models\TicketRemark;
	use App\Models\Notification;
	use Auth, DB, Str;
	use App\Mail\TicketMail;
	use Mail;
	use App\Traits\ApiResponse;
	 
	class TicketController extends Controller
	{
		use ApiResponse;  
		public function index(Request $request)
		{   
			$draw   = $request->get('draw');
			$start  = $request->input("offset") ?? $request->input("start") ?? 0;
			$limit  = $request->input("limit") ?? $request->input("length") ?? 10;  
			$search = $request->input('search') ?? $request->input('search.value') ?? '';
			
			$user = Auth::user();
			$role = $user->role;
			$userId = $user->id;
 
			$query = Ticket::query();

			if (!empty($request->ticket_id)) {
				$query->where('id', $request->ticket_id);
			}

			if (!$user->isAdminLevel()) {
				$query->where('user_id', $userId);
			}
 
			if ($search) {
				$query->where(function ($q) use ($search) {
					$q->where('contact_name', 'LIKE', "%{$search}%")
					  ->orWhere('contact_phone', 'LIKE', "%{$search}%")
					  ->orWhere('awb_number', 'LIKE', "%{$search}%")
					  ->orWhere('ticket_no', 'LIKE', "%{$search}%")
					  ->orWhere('status', 'LIKE', "%{$search}%")
					  ->orWhereDate('created_at', $search);
				});
			}

			// For DataTables accuracy
			$totalData = Ticket::query();
			if (!$user->isAdminLevel()) {
				$totalData->where('user_id', $userId);
			}
			$totalRecords = $totalData->count();

			$totalFiltered = $query->count();  

			$values = $query->orderByDesc('id')
				->offset($start)
				->limit($limit)
				->get();
			
			$data = [];
			$i = $start + 1;
			foreach ($values as $value) {
				$data[] = [
					'id'            => $i++,
					'ticket_id'     => $value->id,
					'ticket_no'     => $value->ticket_no,
					'awb_number'    => $value->awb_number,
					'contact_name'  => $value->contact_name,
					'contact_phone' => $value->contact_phone,
					'status'        => $value->status,
					'status_badge'  => $value->status === 'Close'
						? '<span class="badge badge-success">Close</span>'
						: '<span class="badge badge-danger">Open</span>',
					'created_at'    => $value->created_at->format('d M Y'),
				];
			}

			return response()->json([
				"draw" => intval($draw),
				"iTotalRecords" => $totalRecords,
				"iTotalDisplayRecords" => $totalFiltered,
				"aaData" => $data,
			]);
		}
  
		public function ticketStore(Request $request)
		{ 
			DB::beginTransaction();
			
			try { 
				$user = Auth::user(); 
			
				if (Ticket::where('awb_number', $request->awb_number)->where('status', 'Open')->exists()) {
					return $this->errorResponse('The AWB number you provided already has an open ticket.');   
				}
			
				$ticket = Ticket::create([
					'user_id'    => $user->id,
					'ticket_no'  => strtoupper(Str::uuid()->toString()), // more unique than uniqid
					'awb_number' => $request->awb_number,
					'status'     => 'Open',
					'contact_name' => $request->contact_name,
					'contact_phone' => $request->contact_phone,
					'text' => $request->text,
				]);
 
				TicketRemark::create([
					'ticket_id' => $ticket->id,
					'user_id'   => $user->id,
					'role'      => $user->role,
					'remark'    => $request->text,
					'images'    => null, // only set when available
				]);
 
				$notifications = [
					[
						'user_id' => null,
						'task_id' => $ticket->id,
						'type'    => 'New Ticket',
						'role'    => 'admin',
						'text'    => "{$request->contact_name} created a new ticket (No: {$ticket->ticket_no})",
						'created_at' => now(),
						'updated_at' => now(),
					],
					[
						'user_id' => $user->id,
						'task_id' => $ticket->id,
						'type'    => 'New Ticket',
						'role'    => 'user',
						'text'    => "Your ticket has been generated (No: {$ticket->ticket_no})",
						'created_at' => now(),
						'updated_at' => now(),
					]
				];
				Notification::insert($notifications);
 
				try {
					Mail::to($user->email)->send(new TicketMail([
						'name'       => $request->contact_name,
						'ticket_no'  => $ticket->ticket_no,
						'awb_number' => $ticket->awb_number,
					]));
				} catch (\Exception $mailException) {
					\Log::warning("Ticket email failed for ticket {$ticket->id}: ".$mailException->getMessage());
				}
				DB::commit();
				return $this->successResponse(['ticket_id' => $ticket->id] , 'The ticket has been successfully generated.'); 

			} catch (\Exception $e) {
				DB::rollback();
				return $this->errorResponse('Something went wrong while creating the ticket.');  
			}
		}
		 
		public function ticketView($id)
		{
			try{ 
				$ticket = Ticket::with(['remarks' => function ($q) {
					$q->with('user:id,name')->orderBy('id');
				}])->findOrFail($id);

				// A seller sees only their own tickets
				if (Auth::user()->role === 'user' && (int) $ticket->user_id !== (int) Auth::id()) {
					return $this->errorResponse('Ticket not found.', 404);
				}

				return $this->successResponse($ticket , 'ticket fetched successfully.');
			} catch (\Exception $e) {
				return $this->errorResponse('Something went wrong.');  
			}
		}
 
		public function remarkStore(Request $request)
		{     
			$request->validate([
				'ticket_id' => 'required|integer',
				'remark' => 'required|string',
				'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
			]);

			DB::beginTransaction();
			try {
				$ticketId = $request->ticket_id;
				$imagePaths = []; 
				if ($request->hasFile('images')) {
					foreach ($request->file('images') as $image) {
						 
						// Generate a unique filename
						$filename = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

						// Store the file in the "weight_descrepency" folder within "storage/app/public"
						$path = $image->storeAs('ticket-remark/'.$ticketId, $filename, 'public');

						// Save the file path in an array
						$imagePaths[] = $path;
					}
				} 
				
				$remark = TicketRemark::create([
					'ticket_id' => $ticketId,
					'role' =>  auth()->user()->role,
					'user_id' => auth()->id(),
					'remark' => $request->remark,
					'images' => $imagePaths,
				]);
				
				$now = now();  
				$ticket = Ticket::find($ticketId);
				
				if(auth()->user()->role == "user")
				{
    				Notification::insert([
    					'task_id'    => $ticketId,
    					'type'       => 'Ticket Remark',
    					'role'       => 'admin',
    					'text'       => $ticket->contact_name . ' has created a new ticket remark (Ticket No: ' . $ticket->ticket_no . ').',
    					'created_at' => $now,
    					'updated_at' => $now
    				]); 
				}
				else {
				     
    				Notification::insert([
    					'user_id'    => $ticket->user_id,
    					'task_id'    => $ticketId,
    					'type'       => 'Ticket Remark',
    					'role'       => 'user',
    					'text'       => auth()->user()->name . ' has created a new ticket remark (Ticket No: ' . $ticket->ticket_no . ').',
    					'created_at' => $now,
    					'updated_at' => $now
    				]);
    				
				}
				DB::commit(); 
				return $this->successResponse([], 'Remark submitted successfully.');    
			} catch (\Exception $e) {
				DB::rollback();
				return $this->errorResponse('Failed to submit remark.');   
			}
		} 
	}
