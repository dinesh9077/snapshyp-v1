@extends('layouts.backend.app')
@section('title',config('setting.company_name').' - COD Voucher')
@section('header_title','COD Voucher')
@section('content')
<style>
     .tooltip .tooltiptext {
          text-align: left !important;
          padding: 5px 0 5px 5px !important;
     }

     td p {
          margin-bottom: 0;
     }
</style>
<div class="content-page">
     <div class="content">
          <!-- Start Content-->
          <div class="container-fluid">
               <div class="main-find-weght">
                    <div class="main-order-page-1">
                         <div class="main-order-001">
                              <div class="main-filter-weight">
                                   @if(Auth::user()->role == "admin")
                                   <form method="post" action="{{route('generatevouchers')}}">
                                        @csrf
                                        <div class="row row-re">
                                             <div class="col-lg-2 col-sm-6">
                                                  <div class="main-selet-11">
                                                       <input type="text" class="form-control datepicker" name="date" placeholder="Select Date">
                                                  </div>
                                             </div>
                                             <div class="col-lg-2 col-sm-6">
                                                  <div class="main-selet-11">
                                                       <button type="submit" class="btn-main-1">Genrate</button>
                                                  </div>
                                             </div>
                                        </div>
                                   </form>
                                   @endif
                                   @if(session('success'))
                                   <div class="alert alert-success">
                                        {{ session('message') }}
                                   </div>
                                   @endif
                                   @if(session('error'))
                                   <div class="alert alert-error">
                                        {{ session('message') }}
                                   </div>
                                   @endif
                              </div>
                              <div class="ordr-main-001">
                                   <ul id="tab">
                                        <li class="active">
                                             <div class="main-calander-11">
                                                  <div class="main-data-teble-1 table-responsive">
                                                       <table id="remmitance_datatable" class="dataTable no-footer" style="width:100%">
                                                            <thead>
                                                                 <tr>
                                                                      <th><input type="checkbox" id="checkboxesMain"></th>
                                                                      <!--<th>Order Prefix</th>-->
                                                                      <th> Seller Details </th>
                                                                      <th> Order details </th>
                                                                      <!--<th> Shipping Details </th>-->
                                                                      <th> Amount</th>
                                                                      <th> Total Amount</th>
                                                                      <th> Action</th>
                                                                 </tr>
                                                            </thead>
                                                       </table>
                                                  </div>
                                             </div>
                                        </li>
                                   </ul>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</div>
</div>

@endsection
@push('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
     setTimeout(function() {
          document.getElementById('alert-success').style.display = 'none';
     }, 2000);
     setTimeout(function() {
          document.getElementById('alert-error').style.display = 'none';
     }, 2000);
</script>
<script>
     $(document).ready(function() {
          $('.remittance_data').hide();
          $('ul.tabs-001 li').click(function() {
               var tab_id = $(this).attr('data-tab');
               $('ul.tabs-001 li').removeClass('current');
               $('.tab-content11').removeClass('current');
               $(this).addClass('current');
               $("#" + tab_id).addClass('current');
          })
     })


     var dataTable = $('#remmitance_datatable').DataTable({
          processing: true,
          "language": {
               'loadingRecords': '&nbsp;',
               'processing': 'Loading...'
          },
          serverSide: true,
          bLengthChange: true,
          searching: true,
          bFilter: true,
          bInfo: true,
          iDisplayLength: 25,
          order: [
               [0, 'desc']
          ],
          bAutoWidth: false,
          "ajax": {
               "url": "{{ route('codVoucherajax') }}",
               "dataType": "json",
               "type": "POST",
               "data": function(d) {
                    d._token = "{{csrf_token()}}";
                    d.search = $('input[type="search"]').val();

               }
          },
          "columns": [{
                    "data": "id"
               },
               //   { "data": "order_prefix_list" },
               {
                    "data": "seller_details"
               },
               {
                    "data": "order_details"
               },
               //   { "data": "shipment_details" },
               {
                    "data": "amount"
               },
               {
                    "data": "total_amount"
               },
               {
                    "data": "action"
               },
          ]
     });



     $(document).ready(function() {

          $(document).on('click', '.pay_now', function() {
               var order_id = $(this).data('order-id');
               var user_id = $(this).data('user-id');
               var shipping_company_id = $(this).data('shipping_company_ids');
               var amount = $(this).data('amount');
               console.log(order_id);
               $("#order_id").val(order_id);
               $("#user_id").val(user_id);
               $("#shipping_company_id").val(shipping_company_id);
               $("#amount").val(amount);
               $("#cod_payout_form").show();
          });
     });



     $('#remmitance_datatable').on('draw.dt', function() {
          $('[data-toggle="tooltip"]').tooltip({
               position: {
                    my: "left bottom", // the "anchor point" in the tooltip element
                    at: "left top", // the position of that anchor point relative to selected element
               }
          });
          @if(Auth::user()-> role != "admin")
          dataTable.columns(1).visible(false);
          @endif
     });
</script>
@endpush