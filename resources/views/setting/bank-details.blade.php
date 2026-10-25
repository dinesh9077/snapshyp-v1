@extends('layouts.backend.app')
@section('title', config('setting.company_name') . ' - Bank Details')
@section('header_title', 'Bank Details')

@section('content')
<style>
    .bank-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 7px 1px rgba(0, 0, 0, 0.08);
    }

    .bank-card h5 {
        font-size: 18px;
        margin-bottom: 6px;
        color: #111827;
    }

    .bank-card p {
        color: #6b7280;
        margin-bottom: 18px;
    }

    .bank-passbook-link {
        display: inline-block;
        margin-top: 8px;
        font-size: 13px;
    }
</style>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="main-order-page-1">
                <div class="main-order-001">
                    <div class="bank-card">
                        <h5>Bank Account Details</h5>
                        <p>Add the bank account that should be used for withdrawal and payout processing.</p>

                        <form action="{{ route('setting.bank-details.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="from-group my-2">
                                        <label for="bank_name">Bank Name</label>
                                        <input type="text" name="bank_name" id="bank_name" class="form-control"
                                            value="{{ old('bank_name', $userKyc->bank_name) }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="from-group my-2">
                                        <label for="account_holder_name">Account Holder Name</label>
                                        <input type="text" name="account_holder_name" id="account_holder_name" class="form-control"
                                            value="{{ old('account_holder_name', $userKyc->account_holder_name) }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="from-group my-2">
                                        <label for="account_number">Account Number</label>
                                        <input type="text" name="account_number" id="account_number" class="form-control"
                                            value="{{ old('account_number', $userKyc->account_number) }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="from-group my-2">
                                        <label for="ifsc_code">IFSC Code</label>
                                        <input type="text" name="ifsc_code" id="ifsc_code" class="form-control"
                                            value="{{ old('ifsc_code', $userKyc->ifsc_code) }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="from-group my-2">
                                        <label for="bank_passbook">Passbook / Cancelled Cheque</label>
                                        <input type="file" name="bank_passbook" id="bank_passbook" class="form-control">
                                        @if (!empty($userKyc->bank_passbook))
                                            <a class="bank-passbook-link"
                                                href="{{ str_contains($userKyc->bank_passbook, 'storage/') ? asset($userKyc->bank_passbook) : asset('storage/bank/' . $userKyc->bank_passbook) }}"
                                                target="_blank">View uploaded document</a>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-lg-12 text-right mt-3">
                                    <button class="new-submit-btn" type="submit">
                                        <i class="mdi mdi-content-save"></i> Save Bank Details
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
