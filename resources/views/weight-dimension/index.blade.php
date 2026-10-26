@extends('layouts.backend.app')
@section('title', 'Weight Dimension')
@section('content')
    @php
        // Same page for the admin (any seller / global) and for a seller (own slabs only)
        $isSellerPage = $isSellerPage ?? false;
        $wdRoute = fn ($name, ...$params) => route(($isSellerPage ? 'seller.weight-dimension.' : 'weight-dimension.') . $name, ...$params);
    @endphp
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="main-create-order">
                            <div class="main-disolay-felx align-items-end" style="margin-top: 0 !important; gap: 12px; flex-wrap: wrap;">
                                {{-- Global slabs apply to every seller; a seller's own slabs win over them --}}
                                <div style="min-width: 240px;" class="{{ $isSellerPage ? 'd-none' : '' }}">
                                    <label for="filter_seller" class="mb-1">Slabs for</label>
                                    <select class="form-control" id="filter_seller">
                                        <option value="">Global (all sellers)</option>
                                        @foreach ($sellers as $seller)
                                            <option value="{{ $seller->id }}" data-default-weight="{{ $seller->default_package_weight + 0 ?: '' }}">
                                                {{ $seller->company_name ?: $seller->name }} (#{{ $seller->id }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="sellerDefaultWeightBox" class="{{ $isSellerPage ? '' : 'd-none' }}" style="min-width: 260px;">
                                    <label for="default_package_weight" class="mb-1"
                                        title="Used when an order (e.g. from Shopify) arrives with no weight; its dimensions come from the slabs below">
                                        Default weight for orders without weight (kg)
                                    </label>
                                    <div class="d-flex" style="gap: 8px;">
                                        <input type="number" step="0.001" min="0" class="form-control" id="default_package_weight"
                                            placeholder="Not set" value="{{ $isSellerPage && ($defaultWeight ?? 0) > 0 ? $defaultWeight + 0 : '' }}">
                                        <button type="button" class="btn-main-1" id="saveDefaultWeight">Save</button>
                                    </div>
                                </div>
                                <div class="main-btn0main-1 ml-auto">
                                    <button class="btn-main-1" id="createNewWeightDimension">
                                        <span class="mdi mdi-plus"></span> Add Weight Dimension
                                    </button>
                                </div>
                            </div>
                            <p class="text-muted small mt-2 mb-0" id="slabScopeNote">
                                @if ($isSellerPage)
                                    Your own package sizes per weight slab, used for your orders (Shopify import, bulk ship,
                                    order form). Once you add any slab, only your slabs are used: a weight above your largest
                                    slab gets no dimensions. With no slabs of your own, the platform's standard sizes apply.
                                @else
                                    Global slabs are used only for sellers who have no slabs of their own.
                                @endif
                            </p>

                            <div class="main-data-teble-1 table-responsive">
                                <table id="weightDimensionTable" class="" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th> SR.No </th>
                                            <th> Slab </th>
                                            <th> Length </th>
                                            <th> Width </th>
                                            <th> Height </th>
                                            <th> Status </th>
                                            <th> Action </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="ajaxModel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modelHeading"></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="weightDimensionForm" name="weightDimensionForm" class="form-horizontal">
                        <input type="hidden" name="id" id="weight_dimension_id">
                        @csrf
                        <div class="form-group">
                            <label for="slab" class="col-sm-12 control-label">Slab</label>
                            <div class="col-sm-12">
                                <input type="number" step="0.01" class="form-control" id="slab" name="slab"
                                    placeholder="Enter Slab" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="length" class="col-sm-12 control-label">Length (cm)</label>
                            <div class="col-sm-12">
                                <input type="number" step="0.01" class="form-control" id="length" name="length"
                                    placeholder="Enter Length" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="width" class="col-sm-12 control-label">Width (cm)</label>
                            <div class="col-sm-12">
                                <input type="number" step="0.01" class="form-control" id="width" name="width"
                                    placeholder="Enter Width" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="height" class="col-sm-12 control-label">Height (cm)</label>
                            <div class="col-sm-12">
                                <input type="number" step="0.01" class="form-control" id="height" name="height"
                                    placeholder="Enter Height" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status" class="col-sm-12 control-label">Status</label>
                            <div class="col-sm-12">
                                <select class="form-control" id="status" name="status">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 text-right">
                            <button type="submit" class="btn btn-apply" id="saveBtn" value="create">Save
                                changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript">
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var table = $('#weightDimensionTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                "language": {
                    'loadingRecords': '&nbsp;',
                    'processing': 'Loading...'
                },
                ajax: {
                    url: "{{ $wdRoute('ajax') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        d.user_id = $('#filter_seller').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex'
                    },
                    {
                        data: 'slab',
                        name: 'slab'
                    },
                    {
                        data: 'length',
                        name: 'length'
                    },
                    {
                        data: 'width',
                        name: 'width'
                    },
                    {
                        data: 'height',
                        name: 'height'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        visible: true
                    },
                ]
            });

            function selectedSellerName() {
                return $('#filter_seller').val() ? $.trim($('#filter_seller option:selected').text()) : '';
            }

            $('#filter_seller').on('change', function() {
                const sellerId = $(this).val();
                $('#sellerDefaultWeightBox').toggleClass('d-none', !sellerId);
                $('#default_package_weight').val($('#filter_seller option:selected').data('default-weight') || '');
                $('#slabScopeNote').text(sellerId
                    ? 'Slabs only for ' + selectedSellerName() + '. If this seller has any slab, only these are used (no match = no dimensions); with none, the global slabs apply.'
                    : 'Global slabs are used only for sellers who have no slabs of their own.');
                table.draw();
            });

            $('#saveDefaultWeight').click(function() {
                const $btn = $(this).prop('disabled', true);
                $.post("{{ $wdRoute('default-weight') }}", {
                    user_id: $('#filter_seller').val(),
                    default_package_weight: $('#default_package_weight').val(),
                }).done(function(res) {
                    toastrMsg(res.status, res.msg);
                    if (res.status === 'success') {
                        $('#filter_seller option:selected').data('default-weight', $('#default_package_weight').val());
                    }
                }).fail(function() {
                    toastrMsg('error', 'Something went wrong!');
                }).always(function() {
                    $btn.prop('disabled', false);
                });
            });

            $('#createNewWeightDimension').click(function() {
                $('#saveBtn').val("create-weight-dimension");
                $('#weight_dimension_id').val('');
                $('#weightDimensionForm').trigger("reset");
                $('#modelHeading').html(@json($isSellerPage) ? "New Weight Dimension" : (selectedSellerName()
                    ? "New Weight Dimension for " + selectedSellerName()
                    : "Create New Global Weight Dimension"));
                $('#ajaxModel').modal('show');
            });

            $('body').on('click', '.editWeightDimension', function() {
                var id = $(this).data('id');
                $.get("{{ $wdRoute('index') }}" + '/' + id + '/edit', function(data) {
                    $('#modelHeading').html("Edit Weight Dimension");
                    $('#saveBtn').val("edit-weight-dimension");
                    $('#ajaxModel').modal('show');
                    $('#weight_dimension_id').val(data.id);
                    $('#slab').val(data.slab);
                    $('#length').val(data.length);
                    $('#width').val(data.width);
                    $('#height').val(data.height);
                    $('#status').val(data.status);
                })
            });

            $('#saveBtn').click(function(e) {
                e.preventDefault();
                $(this).html('Sending..');

                $.ajax({
                    data: $('#weightDimensionForm').serialize() + '&user_id=' + encodeURIComponent($('#filter_seller').val()),
                    url: "{{ $wdRoute('store') }}",
                    type: "POST",
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 'error') {
                            toastrMsg('error', data.msg);
                            $('#saveBtn').html('Save changes');
                        } else {
                            $('#weightDimensionForm').trigger("reset");
                            $('#ajaxModel').modal('hide');
                            toastrMsg('success', data.msg);
                            $('#saveBtn').html('Save changes');
                            table.draw();
                        }
                    },
                    error: function(data) {
                        console.log('Error:', data);
                        $('#saveBtn').html('Save changes');
                        toastrMsg('error', 'Something went wrong!');
                    }
                });
            });

            $('body').on('click', '.deleteWeightDimension', function() {
                var id = $(this).data("id");
                if (confirm("Are You sure want to delete !")) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ $wdRoute('index') }}" + '/' + id,
                        success: function(data) {
                            toastrMsg('success', data.msg);
                            table.draw();
                        },
                        error: function(data) {
                            console.log('Error:', data);
                            toastrMsg('error', 'Something went wrong!');
                        }
                    });
                }
            });
        });
    </script>
@endpush
