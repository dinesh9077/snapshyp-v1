  <!-- Date By -->
  <div class="filter-group">
      <label class="filter-label">Date By</label>
      <select class="filter-select select2" id="date_by">
          <option value="created_at">Order Date</option>
          <option value="updated_at">Updated Date</option>
          <option value="delivery_date">Delivery Date</option>
      </select>
  </div>

  <div class="filter-group">
      <label class="filter-label">Date Range</label>
      <input type="text" class="filter-input" name="date_range" id="date_range" placeholder="Select Date Range">
  </div>
  <input type="hidden" name="from_date" id="from_date">
  <input type="hidden" name="to_date" id="to_date">

  <!-- Weight Range -->
  <div class="filter-group">
      <label class="filter-label">Weight (Kg)</label>
      <div class="d-flex" style="gap: 8px;">
          <input type="number" step="0.01" min="0" name="min_weight" id="min_weight" class="filter-input"
              placeholder="Min">
          <input type="number" step="0.01" min="0" name="max_weight" id="max_weight" class="filter-input"
              placeholder="Max">
      </div>
  </div>

  <div class="filter-group">
      <label class="filter-label">Search Order by</label>
      <div class="radio-row">
          <label>
              <input type="radio" name="search_by" value="awb_number" checked> Awb Number
          </label>
          <label>
              <input type="radio" name="search_by" value="order_prefix"> Order ID
          </label>
      </div>

      <input type="text" name="search_value" id="search_value" class="filter-input"
          placeholder="Enter AWB Number (comma separated)">
  </div>

  <!-- Invoice No -->
  <div class="filter-group">
      <label class="filter-label">Invoice No</label>
      <input type="text" name="invoice_no" id="invoice_no" class="filter-input"
          placeholder="Enter Invoice No">
  </div>

  @if ($authUser->role != 'user')
      @php
          $users = \App\Models\Order::join('users', 'users.id', '=', 'orders.user_id')
              ->select('users.id', 'users.name')
              ->groupBy('users.id', 'users.name')
              ->orderBy('users.name', 'asc')
              ->get();
      @endphp
      <div class="filter-group">
          <label class="filter-label">Users</label>
          <select name="user_id" id="user_id" class="filter-select select2">
              <option value="">All Users</option>
              @foreach ($users as $user)
                  <option value="{{ $user->id }}">
                      {{ $user->name }}</option>
              @endforeach
          </select>
      </div>
  @endif

  <!-- Courier Status -->
  <div class="filter-group">
      <label class="filter-label">Courier Status</label>
      <select name="status_courier" id="status_courier" class="filter-select select2">
          <option value="">All Status</option>
          @foreach ($statuses as $st)
              <option value="{{ $st }}">
                  {{ ucfirst($st) }}
              </option>
          @endforeach
      </select>
  </div>

  <!-- Courier Name -->
  <div class="filter-group">
      <label class="filter-label">Courier Name</label>
      <select name="courier_name" id="courier_name" class="filter-select select2">
          <option value="">All Couriers</option>
          @foreach ($courier_names as $name)
              <option value="{{ $name }}">
                  {{ $name }}</option>
          @endforeach
      </select>
  </div>

  <!-- Warehouse -->
  <div class="filter-group">
      <label class="filter-label">Warehouse</label>
      <select name="warehouse_id" id="warehouse_id" class="filter-select select2">
          <option value="">Select Warehouse</option>
          @foreach ($warehouses as $warehouse)
              <option value="{{ $warehouse->id }}">
                  {{ $warehouse->warehouse_name }} - ({{ $warehouse->company_name }})</option>
          @endforeach
      </select>
  </div>

  <!-- Payment Type -->
  <div class="filter-group payment-type">
      <label class="filter-label">Payment Type</label>
      <select name="payment_type" id="payment_type" class="filter-select select2">
          <option value="">Select Payment Type</option>
          <option value="prepaid">Prepaid</option>
          <option value="cod">COD</option>
      </select>
  </div>

  <!-- State / City -->
  <div class="filter-group">
      <label class="filter-label">State</label>
      <select name="state" id="state" class="filter-select select2">
          <option value="">All States</option>
          @foreach ($states as $state)
              <option value="{{ $state }}">{{ $state }}</option>
          @endforeach
      </select>
  </div>

  <div class="filter-group">
      <label class="filter-label">City</label>
      <select name="city" id="city" class="filter-select select2">
          <option value="">All Cities</option>
          @foreach ($cities as $city)
              <option value="{{ $city }}">{{ $city }}</option>
          @endforeach
      </select>
  </div>

  <!-- Search by Order -->
  <div class="filter-group">
      <label class="filter-label">Search by Order</label>
      <div class="radio-row">
          <label>
              <input type="radio" name="order_search" value="hsn_number" checked> HSN
          </label>
          <label>
              <input type="radio" name="order_search" value="sku_number"> SKU
          </label>
      </div>

      <input type="text" id="order_search_value" class="filter-input" placeholder="Enter HSN ">
  </div>


  <!-- Search by Customer -->
  <div class="filter-group">
      <label class="filter-label">Search by Customer</label>
      <div class="radio-row">
          <label>
              <input type="radio" name="customer_search" value="first_name" checked> Name
          </label>
          <label>
              <input type="radio" name="customer_search" value="mobile"> Phone
          </label>
          <label>
              <input type="radio" name="customer_search" value="email"> Email
          </label>
      </div>

      <input type="text" id="customer_search_value" class="filter-input" placeholder="Enter customer name">
  </div>

  <script>
      $(document).ready(function() {
          $('.select2').select2();

          // Search Order By
          $('input[name="search_by"]').on('change', function() {
              const map = {
                  awb_number: 'Enter AWB Number (comma separated)',
                  order_prefix: 'Enter Order ID (comma separated)'
              };
              $('#search_value').attr('placeholder', map[this.value]);
          });

          // Search by Order (HSN / SKU)
          $('input[name="order_search"]').on('change', function() {
              const map = {
                  hsn_number: 'Enter HSN',
                  sku_number: 'Enter SKU'
              };
              $('#order_search_value').attr('placeholder', map[this.value]);
          });

          // Search by Customer
          $('input[name="customer_search"]').on('change', function() {
              const map = {
                  first_name: 'Enter customer name',
                  mobile: 'Enter customer phone number',
                  email: 'Enter customer email address'
              };
              $('#customer_search_value').attr('placeholder', map[this.value]);
          });

          $('#date_range').daterangepicker({
              locale: {
                  format: 'DD-MM-YYYY'
              },
              startDate: moment(),
              endDate: moment()
          });

          $('#date_range').on('apply.daterangepicker', function(ev, picker) {
              $('#from_date').val(picker.startDate.format('DD-MM-YYYY'));
              $('#to_date').val(picker.endDate.format('DD-MM-YYYY'));
          });
      });
  </script>
