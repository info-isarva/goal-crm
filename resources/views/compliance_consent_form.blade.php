@extends('layouts.guest')

@section('content')
<link rel="stylesheet" href="/css/login-custom.css?v=2">
<style>
    .compliance-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(30,60,114,0.08);
        display: flex;
        flex-direction: row;
        overflow: hidden;
        max-width: 900px;
        margin: 48px auto;
        min-height: 480px;
    }
    .compliance-left {
        background: linear-gradient(160deg, #1d4ed8 0%, #172554 100%);
        color: #fff;
        width: 50%;
        padding: 48px 32px 32px 32px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        text-align: center;
    }
    .compliance-left::before {
                content: '';
    position: absolute;
    top: -120px;
    left: -120px;
    width: 350px;
    height: 350px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 50%;
    z-index: 0;
    }
    .compliance-left::after {
            content: '';
            position: absolute;
            bottom: -150px;
            right: -100px;
            width: 350px;
            height: 350px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            z-index: 0;
    }
    .compliance-left h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .compliance-left .feature-list {
        margin-top: 32px;
    }
    .compliance-left .feature-card {
        display: flex;
        align-items: left;
        margin-bottom: 18px;
        font-size: 1.08rem;
    }
    .compliance-left .feature-icon {
        font-size: 1.3rem;
        margin-right: 12px;
    }
    .compliance-right {
        flex: 1;
        padding: 40px 48px 32px 48px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .compliance-logo {
        max-width: 160px;
        margin-bottom: 12px;
    }
    .compliance-title {
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 12px;
        text-align: center;
    }
    .compliance-desc {
        color: #222;
        font-size: 1.05rem;
        margin-bottom: 18px;
        text-align: left;
    }
    .compliance-agree-box {
        background: #f7fafc;
        border: 1px solid #e3e8ee;
        border-radius: 8px;
        padding: 18px 20px 14px 20px;
        margin-bottom: 18px;
        font-size: 1rem;
        color: #222;
        width: 100%;
    }
    .compliance-agree-box strong {
        font-weight: 700;
    }
    .compliance-agree-box ul {
        margin: 0 0 0 18px;
        padding: 0;
    }
    .compliance-agree-box li {
        margin-bottom: 6px;
    }
    .compliance-btn {
        background: #2563eb;
        color: #fff;
        font-weight: 600;
        font-size: 1.1rem;
        border: none;
        border-radius: 6px;
        padding: 12px 0;
        width: 100%;
        margin-top: 10px;
        margin-bottom: 18px;
        transition: background 0.2s;
    }
    .compliance-btn:hover {
        background: #1741a0;
    }
    @media (max-width: 900px) {
        .compliance-card { flex-direction: column; max-width: 98vw; }
        .compliance-left, .compliance-right { width: 100%; border-radius: 18px 18px 0 0; min-width: unset; }
        .compliance-right { border-radius: 0 0 18px 18px; }
    }
</style>
<div class="login-bg-gradient" style="min-height:100vh;">
    <div class="compliance-card">
        <div class="compliance-left">
            <div style="text-align: left;">
                <h1>Admin Compliance</h1>
                <span >Ensure security and data integrity.</span>
                <div class="feature-list" style="margin-top:32px;">
                    <div class="feature-card"><span class="feature-icon"><i class="fa fa-shield-alt"></i></span> Mandatory Monthly Review</div>
                    <div class="feature-card"><span class="feature-icon"><i class="fa fa-user-shield"></i></span> Privileged Access Audit</div>
                </div>
                <!-- <div style="font-size:0.95rem; color:#e0e7ef; margin-top:32px;">© 2026 isarvait.com</div> -->
            </div>
            
        </div>
        <div class="compliance-right">
            @php
                $company = \App\Models\Company::current();
            @endphp
            <img src="{{ $company->logo_url }}" alt="{{ $company->name ?? 'Company Logo' }}" class="compliance-logo" style="max-height: 60px; object-fit: contain;">
            <div class="compliance-title">Compliance Consent</div>
            <div class="compliance-desc">
                As an Admin/Super Admin, you are required to review and accept this mandatory compliance form upon your first login and subsequently once every month.
            </div>
            <div class="compliance-agree-box">
                <strong>I hereby acknowledge and agree:</strong>
                <ul>
                    <li>To strictly adhere to organization data handling policies.</li>
                    <li>That my administrative actions are logged and audited.</li>
                    <li>To maintain the confidentiality of sensitive information.</li>
                </ul>
            </div>
            <div style="color:#444; font-size:1rem; margin-bottom:18px; text-align:left; width:100%;">
                By clicking "I Agree", I confirm my understanding and acceptance of these responsibilities.
            </div>
            <form method="POST" action="{{ route('compliance.consent.submit') }}" style="width:100%;">
                @csrf
                <input type="hidden" name="consent" value="1">
                <button type="submit" class="compliance-btn">I Agree & Continue</button>
            </form>
            <form method="POST" action="{{ route('logout') }}" style="width:100%; margin-top:10px;">
                @csrf
                <button type="submit" class="compliance-btn" style="background:#e3342f;">Logout</button>
            </form>
        </div>
    </div>
</div>
@endsection
