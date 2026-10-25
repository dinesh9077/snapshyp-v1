@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Zone Setting')
@section('header_title', 'Zone Setting')

@section('content')
    <style>
        .zone-shell {
            background: #ffffff;
            border: 1px solid #d9e3ef;
            border-radius: 22px;
            box-shadow: 0 10px 26px rgba(14, 34, 66, 0.08);
            overflow: hidden;
        }

        .zone-live-head {
            background: #ffffff;
            color: #1e2f48;
            text-align: left;
            padding: 18px 20px 12px;
            border-bottom: 1px solid #e6edf7;
            margin: 0;
        }

        .zone-live-head h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: black;
        }

        .zone-live-head p {
            margin: 6px 0 0;
            font-size: 14px;
            color: #5f7394;
            font-weight: 500;
            line-height: 1.45;
        }

        .zone-tools {
            padding: 14px 20px;
            border-bottom: 1px solid #e6edf7;
            background: #f8fbff;
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .zone-matrix-nav {
            border-bottom: 1px solid #dee2e6;
            flex-wrap: nowrap;
        }

        .zone-matrix-nav .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 18px;
            font-weight: 600;
            background: transparent;
            border-radius: 10px 10px 0 0;
        }

        .zone-matrix-nav .nav-link:hover {
            color: var(--new-color-prime);
            border-bottom: 3px solid var(--new-color-prime);
        }

        .zone-matrix-nav .nav-link.active {
            color: #fff;
            border-bottom: 3px solid var(--new-color-prime);
            background: var(--new-color-prime);
        }

        .zone-company {
            min-width: 260px;
            max-width: 340px;
        }

        .zone-company label {
            color: #627995;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .zone-grid-wrap {
            padding: 16px 20px 20px;
            background: #f8fafc;
            overflow-x: auto;
        }

        .zone-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 960px;
            border: 1px solid #dbe5f1;
            border-radius: 18px;
            overflow: hidden;
        }

        .zone-grid thead th {
            background: #d0d9e6;
            color: #35537f;
            font-size: 12px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 800;
            padding: 14px 16px;
            border-bottom: 1px solid #cad6e6;
        }

        .zone-grid tbody td,
        .zone-grid tfoot td {
            padding: 14px 16px;
            border-bottom: 1px solid #edf2f8;
            color: #1e2f48;
            vertical-align: middle;
            background: #fff;
        }

        .zone-grid tbody tr:last-child td {
            border-bottom: 1px solid #edf2f8;
        }

        .zone-grid tfoot td {
            background: #eaf0f7;
        }

        .zone-code-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            padding: 3px 10px;
            border: 1px solid #c9d8ee;
            border-radius: 6px;
            color: #143f80;
            font-weight: 800;
            background: #eaf1ff;
            font-size: 14px;
            line-height: 1.1;
        }

        .zone-name {
            font-weight: 700;
            color: #163a7c;
        }

        .zone-pincode-badge {
            display: inline-flex;
            min-width: 30px;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 10px;
            background: #fff2e8;
            color: #f26c2d;
            font-weight: 700;
        }

        .zone-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            color: #7f93b1;
            border: 1px solid #d6e0ef;
            margin-right: 8px;
            text-decoration: none !important;
            background: #f5f8fd;
        }

        .zone-action:hover {
            color: #173b7a;
            border-color: #173b7a;
            background: #eaf1ff;
        }

        .zone-action-save {
            color: #17a34a;
            border-color: #b8e8c9;
            background: #effbf4;
        }

        .zone-action-cancel {
            color: #e11d48;
            border-color: #f8c8d4;
            background: #fff1f4;
        }

        .zone-empty {
            text-align: center;
            color: #6b809f;
            padding: 24px 16px !important;
            font-weight: 600;
        }

        .zone-grid input {
            width: 100% !important;
        }

        .zone-add-btn {
            min-width: 120px;
            border-radius: 8px;
            border: none;
            background: #7d8faa;
            color: #fff;
            font-weight: 700;
            height: 40px;
        }

        .zone-add-btn:hover {
            background: #6a7f9d;
        }

        .zone-map-input {
            height: 40px;
            border: 1px solid #cdd9ea;
            border-radius: 8px;
            background: #ffffff;
            color: #143f80;
            font-size: 14px;
            padding: 8px 14px;
        }

        .zone-map-input::placeholder {
            color: #7b8faa;
        }

        @media (max-width: 767px) {

            .zone-live-head,
            .zone-tools,
            .zone-grid-wrap {
                padding-left: 14px;
                padding-right: 14px;
            }

        }
    </style>

    @php
        $preselectedCompanyId = (string) request('shipping_company_id', '');
    @endphp

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="zone-shell">
                    <div class="zone-live-head">
                        <h3>Zone Matrix</h3>
                        <p>Create routing zones and map them to States or Cities. Pincodes matching these mapped locations
                            will automatically appear in the Manage Pincodes tab.</p>
                    </div>

                    <div class="zone-tools">
                        {{-- <ul class="nav nav-tabs zone-matrix-nav mb-0" id="zoneTabs" role="tablist" style="display: none;">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#" role="tab" data-type="b2c">B2C
                                    Zones</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#" role="tab" data-type="b2b">B2B Zones</a>
                            </li>
                        </ul> --}}

                        <div class="zone-company {{ $preselectedCompanyId !== '' ? 'd-none' : '' }}">
                            <label for="filterShippingCompany">Shipping Company</label>
                            <select id="filterShippingCompany" class="form-control">
                                <option value="">Select Shipping Company</option>
                                @foreach ($shippingCompanies as $index => $company)
                                    <option value="{{ $company->id }}" {{ ($preselectedCompanyId === (string) $company->id || ($preselectedCompanyId === '' && $index === 0)) ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" id="fixedShippingCompanyId" value="{{ $preselectedCompanyId }}">
                        <input type="hidden" id="current_rate_type" value="b2c">
                    </div>

                    <div class="zone-grid-wrap">
                        <table class="zone-grid" id="zoneGridTable">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Zone Code</th>
                                    <th style="width: 24%;">Zone Name</th>
                                    <th style="width: 32%;">Mapped States / Cities</th>
                                    <th style="width: 14%;">Mapped Pincodes</th>
                                    <th style="width: 15%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="zoneTableBody">
                                <tr>
                                    <td colspan="5" class="zone-empty">Loading zones...</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>
                                        <input type="text" id="add_zone_code" class="form-control" placeholder="e.g. N3"
                                            maxlength="50">
                                    </td>
                                    <td>
                                        <input type="text" id="add_zone_name" class="form-control"
                                            placeholder="e.g. North 3" maxlength="255">
                                    </td>
                                    <td><input type="text" id="add_zone_values_input" class="form-control zone-map-input"
                                            placeholder="e.g. Punjab, Haryana"></td>
                                    <td class="text-center" id="add_zone_pincode_preview">-</td>
                                    <td>
                                        <button type="button" id="btnAddZoneInline" class="zone-add-btn">+ Add</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        $(function () {
            const csrfToken = "{{ csrf_token() }}";
            const $companyFilter = $('#filterShippingCompany');
            const fixedCompanyId = ($('#fixedShippingCompanyId').val() || '').toString();
            let editingZoneId = null;

            function notify(type, title, text) {
                if (window.Swal) {
                    Swal.fire(title, text || '', type);
                    return;
                }
                alert(text || title);
            }

            function getCurrentRateType() {
                return ($('#current_rate_type').val() || 'b2c').toLowerCase();
            }

            function getShippingCompanyId() {
                if (fixedCompanyId !== '') {
                    return fixedCompanyId;
                }
                return ($companyFilter.val() || '').toString();
            }

            function normalize(value) {
                return (value || '').toString().trim().toLowerCase();
            }

            function htmlEscape(value) {
                return $('<div>').text(value || '').html();
            }

            function parseMappedValues(raw) {
                const seen = {};
                return (raw || '')
                    .split(',')
                    .map(v => (v || '').trim())
                    .filter(v => {
                        if (!v) return false;
                        const k = v.toLowerCase();
                        if (seen[k]) return false;
                        seen[k] = true;
                        return true;
                    });
            }

            function renderRows(rows) {
                const $tbody = $('#zoneTableBody');
                $tbody.empty();

                if (!Array.isArray(rows) || rows.length === 0) {
                    $tbody.append('<tr><td colspan="5" class="zone-empty">No zones found for this shipping company.</td></tr>');
                    return;
                }

                rows.forEach(function (row) {
                    const mappedText = row.mapped_states_cities && row.mapped_states_cities !== '' ? row.mapped_states_cities : '-';
                    const pincodeCount = Number(row.mapped_pincodes || 0);
                    const actionCell = row.can_edit
                        ? `<a href="javascript:void(0);" class="zone-action btn-edit-zone" data-id="${row.id}" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                                                   <a href="javascript:void(0);" class="zone-action btn-delete-zone" data-id="${row.id}" title="Delete"><i class="mdi mdi-trash-can-outline"></i></a>`
                        : '-';

                    const html = `
                                                <tr>
                                                    <td><span class="zone-code-badge">${htmlEscape(row.zone_code || '')}</span></td>
                                                    <td><div class="zone-name">${htmlEscape(row.zone_name || '')}</div></td>
                                                    <td>${htmlEscape(mappedText)}</td>
                                                    <td><span class="zone-pincode-badge">${pincodeCount}</span></td>
                                                    <td>${actionCell}</td>
                                                </tr>
                                            `;

                    $tbody.append(html);
                });
            }

            function renderEditRow(zone) {
                const values = Array.isArray(zone.zone_values) ? zone.zone_values : [];
                const valuesText = values.join(', ');

                return `
                                            <tr id="zone-edit-row" data-id="${zone.id}">
                                                <td><input type="text" id="edit_inline_zone_code" class="form-control" value="${htmlEscape(zone.zone_code || '')}" maxlength="50"></td>
                                                <td><input type="text" id="edit_inline_zone_name" class="form-control" value="${htmlEscape(zone.zone_name || '')}" maxlength="255"></td>
                                                <td><input type="text" id="edit_inline_zone_values" class="form-control zone-map-input" value="${htmlEscape(valuesText)}" placeholder="e.g. Punjab, Haryana"></td>
                                                <td class="text-center">-</td>
                                                <td>
                                                    <a href="javascript:void(0);" class="zone-action zone-action-save btn-save-inline-zone" data-id="${zone.id}" title="Save"><i class="mdi mdi-check"></i></a>
                                                    <a href="javascript:void(0);" class="zone-action zone-action-cancel btn-cancel-inline-zone" title="Cancel"><i class="mdi mdi-close"></i></a>
                                                </td>
                                            </tr>
                                        `;
            }

            function loadZones() {
                const shippingCompanyId = getShippingCompanyId();
                const rateType = getCurrentRateType();

                if (!shippingCompanyId) {
                    renderRows([]);
                    return;
                }

                $('#zoneTableBody').html('<tr><td colspan="5" class="zone-empty">Loading zones...</td></tr>');

                $.ajax({
                    url: "{{ route('rate.zone-setting.ajax') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrfToken,
                        shipping_company_id: shippingCompanyId,
                        rate_type: rateType,
                        plain: 1
                    },
                    success: function (res) {
                        renderRows(res.data || []);
                    },
                    error: function () {
                        $('#zoneTableBody').html('<tr><td colspan="5" class="zone-empty">Failed to load zones.</td></tr>');
                    }
                });
            }

            function resetInlineForm() {
                $('#add_zone_code').val('');
                $('#add_zone_name').val('');
                $('#add_zone_values_input').val('');
                $('#add_zone_pincode_preview').text('-');
            }

            function saveInlineZone() {
                const shippingCompanyId = getShippingCompanyId();
                const rateType = getCurrentRateType();
                const zoneCode = ($('#add_zone_code').val() || '').trim();
                const zoneName = ($('#add_zone_name').val() || '').trim();
                const mappedValues = parseMappedValues($('#add_zone_values_input').val());
                const isSystemZone = ['within city', 'within state'].includes(normalize(zoneName));

                if (!shippingCompanyId) {
                    notify('error', 'Error', 'Please select shipping company first.');
                    return;
                }

                if (!zoneCode || !zoneName) {
                    notify('error', 'Error', 'Zone code and zone name are required.');
                    return;
                }

                if (!isSystemZone && mappedValues.length === 0) {
                    notify('error', 'Error', 'Please enter mapped states/cities separated by comma.');
                    return;
                }

                $('#btnAddZoneInline').prop('disabled', true);
                $.ajax({
                    url: "{{ route('rate.zone-setting.store') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrfToken,
                        shipping_company_id: shippingCompanyId,
                        rate_type: rateType,
                        zone_code: zoneCode,
                        zone_name: zoneName,
                        zone: mappedValues
                    },
                    success: function (res) {
                        if (res.status === 'success') {
                            resetInlineForm();
                            loadZones();
                            notify('success', 'Success', res.msg || 'Zone added successfully.');
                        } else {
                            notify('error', 'Error', res.msg || 'Unable to add zone.');
                        }
                    },
                    error: function (xhr) {
                        let msg = 'Unable to add zone.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            msg = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const firstError = Object.values(xhr.responseJSON.errors)[0];
                            msg = Array.isArray(firstError) ? firstError[0] : 'Validation failed.';
                        }
                        notify('error', 'Error', msg);
                    },
                    complete: function () {
                        $('#btnAddZoneInline').prop('disabled', false);
                    }
                });
            }

            $(document).on('click', '.btn-edit-zone', function () {
                const id = $(this).data('id');
                if (editingZoneId) {
                    notify('error', 'Error', 'Please save or cancel current edit first.');
                    return;
                }
                $.ajax({
                    url: "{{ url('rate/zone-setting/edit') }}/" + id,
                    type: 'GET',
                    dataType: 'json',
                    success: function (res) {
                        if (res.status !== 'success') {
                            notify('error', 'Error', res.msg || 'Unable to fetch zone details.');
                            return;
                        }

                        const zone = res.object || {};
                        let zoneValues = zone.zone;
                        if (typeof zoneValues === 'string') {
                            try {
                                zoneValues = JSON.parse(zoneValues);
                            } catch (error) {
                                zoneValues = zoneValues.split(',').map(item => item.trim()).filter(Boolean);
                            }
                        }
                        if (!Array.isArray(zoneValues)) {
                            zoneValues = [];
                        }

                        zone.zone_values = zoneValues;
                        zone.zone_code = zone.zone_code || '';
                        zone.zone_name = zone.zone_name || zone.heading || '';
                        const $targetRow = $('#zoneTableBody').find(`.btn-edit-zone[data-id="${id}"]`).closest('tr');
                        $targetRow.replaceWith(renderEditRow(zone));
                        editingZoneId = id;
                    },
                    error: function () {
                        notify('error', 'Error', 'Unable to fetch zone details.');
                    }
                });
            });

            $(document).on('click', '.btn-save-inline-zone', function () {
                const id = $(this).data('id');
                const shippingCompanyId = getShippingCompanyId();
                const rateType = getCurrentRateType();
                const zoneCode = ($('#edit_inline_zone_code').val() || '').trim();
                const zoneName = ($('#edit_inline_zone_name').val() || '').trim();
                const mappedValues = parseMappedValues($('#edit_inline_zone_values').val());
                const isSystemZone = ['within city', 'within state'].includes(normalize(zoneName));

                if (!zoneCode || !zoneName) {
                    notify('error', 'Error', 'Zone code and zone name are required.');
                    return;
                }

                if (!isSystemZone && mappedValues.length === 0) {
                    notify('error', 'Error', 'Please enter mapped states/cities separated by comma.');
                    return;
                }

                const $saveBtn = $(this);
                $saveBtn.addClass('disabled');
                $.ajax({
                    url: "{{ url('rate/zone-setting/update') }}/" + id,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrfToken,
                        shipping_company_id: shippingCompanyId,
                        rate_type: rateType,
                        zone_code: zoneCode,
                        zone_name: zoneName,
                        zone: mappedValues
                    },
                    success: function (res) {
                        if (res.status === 'success') {
                            editingZoneId = null;
                            loadZones();
                            notify('success', 'Success', res.msg || 'Zone updated successfully.');
                        } else {
                            notify('error', 'Error', res.msg || 'Unable to update zone.');
                        }
                    },
                    error: function (xhr) {
                        let msg = 'Unable to update zone.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            msg = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const firstError = Object.values(xhr.responseJSON.errors)[0];
                            msg = Array.isArray(firstError) ? firstError[0] : 'Validation failed.';
                        }
                        notify('error', 'Error', msg);
                    },
                    complete: function () {
                        $saveBtn.removeClass('disabled');
                    }
                });
            });

            $(document).on('click', '.btn-cancel-inline-zone', function () {
                editingZoneId = null;
                loadZones();
            });

            function doDeleteRequest(id) {
                const dfd = $.Deferred();

                $.ajax({
                    url: "{{ url('rate/zone-setting/delete') }}/" + id,
                    type: 'DELETE',
                    dataType: 'json',
                    data: {
                        _token: csrfToken
                    }
                }).done(function (res) {
                    dfd.resolve(res);
                }).fail(function () {
                    $.ajax({
                        url: "{{ url('rate/zone-setting/delete') }}/" + id,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            _token: csrfToken,
                            _method: 'DELETE'
                        }
                    }).done(function (res) {
                        dfd.resolve(res);
                    }).fail(function (xhr) {
                        dfd.reject(xhr);
                    });
                });

                return dfd.promise();
            }

            $(document).on('click', '.btn-delete-zone', function () {
                const id = $(this).data('id');
                const isConfirmed = confirm('Are you sure do you want to delete?');
                if (!isConfirmed) {
                    return;
                }

                doDeleteRequest(id).done(function (res) {
                    if (res.status === 'success') {
                        loadZones();
                        alert(res.msg || 'Zone deleted successfully.');
                    } else {
                        alert(res.msg || 'Unable to delete zone.');
                    }
                }).fail(function (xhr) {
                    const msg = xhr?.responseJSON?.msg || 'Unable to delete zone.';
                    alert(msg);
                });
            });

            $('#zoneTabs').on('click', '.nav-link', function (e) {
                e.preventDefault();
                $('#zoneTabs .nav-link').removeClass('active');
                $(this).addClass('active');
                $('#current_rate_type').val($(this).data('type'));
                resetInlineForm();
                loadZones();
            });

            $companyFilter.on('change', function () {
                resetInlineForm();
                loadZones();
            });

            $('#btnAddZoneInline').on('click', saveInlineZone);

            resetInlineForm();
            loadZones();
        });
    </script>
@endpush