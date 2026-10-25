@extends('layouts.backend.app')

@section('title', 'Recharge Successful')

@section('header_title')
    Payment Success
@endsection

@section('content')
    <style>
        .thank-you-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            margin: 50px auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            color: #4caf50;
        }

        .thank-you-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .thank-you-message {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .btn-dashboard {
            background: #FB5C08;
            color: #fff;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-dashboard:hover {
            background: #3984FB;
            color: #fff;
            transform: translateY(-2px);
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="thank-you-card">
                    <div class="success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h1 class="thank-you-title">Thank You!</h1>
                    <p class="thank-you-message">Your wallet recharge was successful.</p>

                    @if(session('transaction_id') || request('transaction_id'))
                        <div class="alert alert-success d-inline-block mb-4 p-2 px-4 rounded-pill">
                            Transaction ID: {{ session('transaction_id') ?? request('transaction_id') }}
                        </div>
                        <br>
                    @endif

                    <a href="{{ route('recharge.wallet') }}" class="btn-dashboard">
                        Go Back to Wallet
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection