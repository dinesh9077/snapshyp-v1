<?php
	
	namespace App\Http\Controllers;
	
	use Illuminate\Http\Request;
	use App\Models\Ticket;
	use App\Models\User;
	use App\Models\TicketRemark;
	use App\Models\Notification;
	use Auth, DB, Str;
	use App\Mail\TicketMail;
	use Mail;
	class TicketController extends Controller
	{
		public function __construct()
		{
			$this->middleware('auth');
		}
		
		public function index(Request $request)
		{ 
			$status = $request->get('status');
			return view('ticket.index', compact('status'));
		}
		
		public function ticketListAjax(Request $request)
		{
			$draw   = $request->get('draw');
			$start  = $request->get("start", 0);
			$limit  = $request->get("length", 10); // default 10
			$order  = $request->input("columns.".$request->input("order.0.column").".data", "id");
			$dir    = $request->input("order.0.dir", "asc");
			$ticket_id = $request->post('ticket_id');
			$searchData = $request->input('search');
			$search = is_array($searchData) ? ($searchData['value'] ?? '') : ($searchData ?? '');
			
			$role = Auth::user()->role;
			$id   = Auth::user()->id;
 
			$query = Ticket::query();

			if (!empty($ticket_id)) {
				$query->where('id', $ticket_id);
			}

			if ($request->filled('status')) {
				$query->where('status', $request->status);
			}

			if ($role !== "admin") {
				$query->where('user_id', $id);
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

			$totalData     = Ticket::count();   
			$totalFiltered = $query->count();  
 
			if ($order === 'customer_name') {
				$order = 'id';
			}

			$values = $query->orderBy($order, $dir)
				->offset($start)
				->limit($limit)
				->get();
 
			$data = [];
			$i = $start + 1;
			foreach ($values as $value) {
				$data[] = [
					'id'            => $i++,
					'ticket_no'     => $value->ticket_no,
					'awb_number'    => $value->awb_number,
					'contact_name'  => $value->contact_name,
					'contact_phone' => $value->contact_phone,
					'status'        => $value->status === 'Close'
						? '<span class="badge badge-success">Close</span>'
						: '<span class="badge badge-danger">Open</span>',
					'created_at'    => $value->created_at->format('d M Y'),
					'action'        => '<a href="'.url('ticket/view', $value->id).'" class="btn  eyess-clss action-icon" data-toggle="tooltip" title="Ticket View"> <i class="mdi mdi-eye"></i> </a>',
				];
			}
 
			return response()->json([
				"draw" => intval($draw),
				"iTotalRecords" => $totalData,
				"iTotalDisplayRecords" => $totalFiltered,
				"aaData" => $data,
			]);
		}

		
		public function ticketAdd()
		{
			return view('ticket.add');
		}
		
		public function ticketStore(Request $request)
		{
			$user = Auth::user(); 
			
			if (Ticket::where('awb_number', $request->awb_number)->where('status', 'Open')->exists()) {
				return response()->json([
					'status' => 'error',
					'msg'    => 'The AWB number you provided already has an open ticket.',
				]);
			}
			DB::beginTransaction();
			
			try { 
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
				return response()->json([
					'status' => 'success',
					'msg'    => 'The ticket has been successfully generated.',
				]);

			} catch (\Exception $e) {
				DB::rollback();
				return response()->json([
					'status' => 'error',
					'msg'    => 'Something went wrong while creating the ticket.'.$e->getMessage(),
				]);
			}
		}
		 
		public function ticketView($id)
		{
			$ticket = Ticket::with(['remarks' => function ($q) {
				$q->orderBy('id');
			}])->findOrFail($id);

			$role = auth()->user()->role;

			return view('ticket.remark-form', [
				'id'      => $id,
				'ticket'  => $ticket,
				'remarks' => $ticket->remarks,
				'role'    => $role,
			]);
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
				return redirect()->back()->with('success', 'Remark submitted successfully.');
			} catch (\Exception $e) {
				DB::rollback();
				return redirect()->back()->with('error', 'Failed to submit remark.'. $e->getMessage()); 
			}
		}
		
		public function ticketDelete($id)
		{ 
			try
			{ 
				$user = Ticket::whereId($id)->delete(); 
				return back()->with('success','The ticket has been successfully deleted.'); 
			}
			catch(\Exception $e)
			{ 
				return back()->with('error','Something went wrong.'); 
			}  
		}
		
		public function ticketList(Request $request)
		{
			if (auth()->user()->role === 'user') {
				abort(403, 'Permission denied');
			} 
			if ($request->filled('task_id') && $request->filled('notify_id')) {
				Notification::where('id', $request->notify_id)->update(['read_at' => 1]);
			}

			$ticket_id = $request->get('task_id');
			$status = $request->get('status');

			return view('ticket.list', compact('ticket_id', 'status'));
		}

		public function ticketAllListAjax(Request $request)
		{
			$draw = intval($request->get('draw', 1));
			$start = intval($request->get('start', 0));
			$limit = intval($request->get('length', 10));

			$columns = $request->get('columns', []);
			$orderArr = $request->get('order', []);
			$searchData = $request->input('search');
			$searchValue = is_array($searchData) ? ($searchData['value'] ?? '') : ($searchData ?? '');
			$ticket_id = $request->post('ticket_id');

			// Determine column for sorting
			$orderColumn = 'id';
			$orderDir = 'asc';
			if (!empty($orderArr)) {
				$columnIndex = $orderArr[0]['column'] ?? 0;
				$orderDir = $orderArr[0]['dir'] ?? 'asc';
				$orderColumn = $columns[$columnIndex]['data'] ?? 'id';

				// Whitelist columns to prevent SQL errors
				$allowedColumns = ['id','ticket_no','awb_number','contact_name','contact_phone','status','created_at'];
				if (!in_array($orderColumn, $allowedColumns)) {
					$orderColumn = 'id';
				}
			}

			// Base query
			$query = Ticket::query();
			if (!empty($ticket_id)) {
				$query->where('id', $ticket_id);
			}

			if ($request->filled('status')) {
				$query->where('status', $request->status);
			}

			$totalData = $query->count();

			// Search filter
			if (!empty($searchValue)) {
				$query->where(function ($q) use ($searchValue) {
					$q->where('contact_name', 'LIKE', "%{$searchValue}%")
					  ->orWhere('contact_phone', 'LIKE', "%{$searchValue}%")
					  ->orWhere('awb_number', 'LIKE', "%{$searchValue}%")
					  ->orWhere('ticket_no', 'LIKE', "%{$searchValue}%")
					  ->orWhere('status', 'LIKE', "%{$searchValue}%")
					  ->orWhereDate('created_at', 'LIKE', "%{$searchValue}%");
				});
			}

			$totalFiltered = $query->count();

			// Pagination and ordering
			$tickets = $query->offset($start)
							 ->limit($limit)
							 ->orderBy($orderColumn, $orderDir)
							 ->get();

			// Prepare data for DataTables
			$data = [];
			foreach ($tickets as $i => $ticket) {
				$id = $start + $i + 1;
				$statusLabel = ($ticket->status === 'Close') ? 
					'<span class="badge badge-success">Close</span>' : 
					'<span class="badge badge-danger">Open</span>';

				$action = '<a href="'.url('ticket/view',$ticket->id).'"><i class="mdi mdi-eye class-pencil"></i></a>'; 
				if ($ticket->status != 'Close') {
					$action .= ' <a href="'.url('ticket/close',$ticket->id).'" onclick="closeTicket(this,event);"><i class="mdi mdi-close class-pencil"></i></a>';
				}
				 
				if(config('permission.ticket_request.delete')){
					$action .= ' <a href="'.url('ticket/delete',$ticket->id).'" onclick="deleteRecord(this,event);"><i class="mdi mdi-trash-can-outline class-pencil"></i></a>';
				}
				$data[] = [
					'id' => $id,
					'ticket_no' => $ticket->ticket_no,
					'awb_number' => $ticket->awb_number,
					'contact_name' => $ticket->contact_name,
					'contact_phone' => $ticket->contact_phone,
					'status' => $statusLabel,
					'created_at' => $ticket->created_at ? $ticket->created_at->format('d M Y') : '',
					'action' => $action
				];
			}

			return response()->json([
				'draw' => $draw,
				'iTotalRecords' => $totalData,
				'iTotalDisplayRecords' => $totalFiltered,
				'aaData' => $data
			]);
		}

		
		public function revert_ticket($id)
		{
			if(auth()->user()->role == "staff" && !auth()->user()->hasPermissionTo(16))
			{
				return abort(403, 'Permission Denied'); 
			}
			$tickets = Ticket::where("id",$id)->first();
			
			return view('ticket.revert',compact('tickets'));
		}
		
		public function ticketupdate($id,Request $request)	
		{	
			try
			{ 
				$data = $request->except('_token');
				Ticket::whereId($id)->update($data); 
				return response()->json(['status'=>'success','msg'=>'The ticket has been successfully reverted.']);
				
			}
			catch(\Exception $e)
			{ 
				return response()->json(['status'=>'error','msg'=>$e->getMessage()]);
			}
			
		}
		public function ticketClose($id)
		{ 
			if(auth()->user()->role == "staff" && !auth()->user()->hasPermissionTo(16))
			{
				return abort(403, 'Permission Denied'); 
			}
			try
			{ 
				
				$ticket = Ticket::whereId($id)->first();
				
				$user = User::whereId($ticket->user_id)->first();
				Ticket::whereId($id)->update(['status'=>'Close']); 
				
				$now = now();
				
				Notification::insert([
                    'user_id'    => $ticket->user_id,
                    'task_id'    => $ticket->id,
                    'type'       => 'Ticket Remark',
                    'role'       => 'user',
                    'text'       => auth()->user()->name . ' has closed the ticket (Ticket No: ' . $ticket->ticket_no . ').',
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
                
				$details = [
    				'name' => $ticket->contact_name,
    				'ticket_no' => $ticket->ticket_no,
    				'awb_number' => $ticket->awb_number
				];
				 
 
				try {
                    \Mail::to($user->email)->send(new \App\Mail\TicketCloseMail($details));
                } catch (\Exception $e) { 
                }

				return back()->with('success','The ticket has been successfully close.'); 
			}
			catch(\Exception $e)
			{ 
				return back()->with('error','Something went wrong.'.$e->getMessage()); 
			}  
		}
		
	}
