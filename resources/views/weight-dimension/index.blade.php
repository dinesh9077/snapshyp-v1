@extends('layouts.backend.app')
@section('title', 'Weight Dimension')
@section('content')
    <div class="content-page">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="main-order-page-1">
                    <div class="main-order-001">
                        <div class="main-create-order">
                            <div class="main-disolay-felx" style="margin-top: 0 !important;">
                                <div class="main-btn0main-1">
                                    <button class="btn-main-1" id="createNewWeightDimension">
                                        <span class="mdi mdi-plus"></span> Add Weight Dimension
                                    </button>
                                </div>
                            </div>

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
                    url: "{{ route('weight-dimension.ajax') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
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

            $('#createNewWeightDimension').click(function() {
                $('#saveBtn').val("create-weight-dimension");
                $('#weight_dimension_id').val('');
                $('#weightDimensionForm').trigger("reset");
                $('#modelHeading').html("Create New Weight Dimension");
                $('#ajaxModel').modal('show');
            });

            $('body').on('click', '.editWeightDimension', function() {
                var id = $(this).data('id');
                $.get("{{ route('weight-dimension.index') }}" + '/' + id + '/edit', function(data) {
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
                    data: $('#weightDimensionForm').serialize(),
                    url: "{{ route('weight-dimension.store') }}",
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
                        url: "{{ route('weight-dimension.index') }}" + '/' + id,
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
