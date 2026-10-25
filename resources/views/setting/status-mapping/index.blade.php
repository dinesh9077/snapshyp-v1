@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Status Mapping')
@section('header_title', 'Status Mapping')
@section('content')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.8.0/dist/bootstrap-tagsinput.css">

    <style>
        .bootstrap-tagsinput {
            width: 100%;
            min-height: 38px;
            padding: 6px 12px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            background-color: #fff;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .bootstrap-tagsinput .tag {
            background-color: #007bff;
            color: #fff;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 3px;
            font-size: 12px;
        }

        .bootstrap-tagsinput .tag [data-role="remove"] {
            margin-left: 5px;
            cursor: pointer;
        }

        .bootstrap-tagsinput input {
            border: none;
            outline: none;
            background: transparent;
            padding: 0;
            margin: 2px;
            flex: 1;
            min-width: 120px;
        }

        .internal-status-input {
            background-color: #f8f9fa;
            font-weight: 500;
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-12">
                        <div class="main-order-001 mb-3" style="">
                            <div class="card-body p-0">

                                <form id="statusMappingForm" method="post" action="{{ route('status-mapping.store') }}">
                                    <div class="row justify-content-between align-items-start m-0 mb-1">
                                        <div>
                                            <h4 class="header-title">Manage Courier Status Mappings</h4>
                                            <p class="text-muted font-13 mb-1">
                                                @php
                                                    $statuses = \App\Models\Order::query()
                                                        ->distinct()
                                                        ->whereNotNull('status_courier')
                                                        ->pluck('status_courier')
                                                        ->filter()
                                                        ->values(); // reset numeric keys
                                                @endphp

                                                @foreach ($statuses as $status)
                                                    <span class="badge badge-info mb-1">{{ ucfirst($status) }}</span>
                                                @endforeach

                                            </p>
                                        </div>
                                        @if (config('permission.status_mapping.add'))
                                            <div class="text-end">
                                                <button type="submit" class="btn-main-1"
                                                    onclick="saveAllMappings()">
                                                    <i class="mdi mdi-content-save"></i> Save All Mappings
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                    @csrf
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <h6 class="text-muted">Internal Status</h6>
                                        </div>
                                        <div class="col-md-8">
                                            <h6 class="text-muted">Courier Statuses (Tags)</h6>
                                        </div>
                                    </div>

                                    <div id="status-mapping-container">
                                        @foreach ($internalStatuses as $index => $status)
                                            <div class="row mb-3 status-mapping-row" data-index="{{ $index }}">
                                                <div class="col-md-4 mb-3 mb-md-0">
                                                    <input type="text" class="form-control internal-status-input"
                                                        name="internal_status[]" value="{{ $status }}" readonly>
                                                </div>
                                                <div class="col-md-8">
                                                    <input type="text" class="form-control courier-tags-input"
                                                        name="courier_status[]" placeholder="Add courier statuses as tags"
                                                        value="{{ isset($statusMappings[$status]) ? implode(',', $statusMappings[$status]) : '' }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
