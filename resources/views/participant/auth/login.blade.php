@extends('layouts.app')

@section('title', 'Login Peserta')

@section('content')
<div class="auth-container animate-fade-in">
    <div class="glass" style="padding: 40px; width: 100%; max-width: 450px;">
        <div style="text-align: center; margin-bottom: 32px;">
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; margin-bottom: 8px;">Login Peserta</h1>
            <p style="color: var(--text-secondary);">Silakan login untuk mengakses ujian Anda.</p>
        </div>

        @if(session('error'))
            <div class="alert alert-error" style="margin-bottom: 24px; padding: 12px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 8px; font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('participant.login.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="email" name="email" id="email" class="form-input" style="padding-left: 44px;" placeholder="email@contoh.com" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 32px;">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="password" name="password" id="password" class="form-input" style="padding-left: 44px;" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; height: 50px; font-size: 1rem; border-radius: 12px;">
                Masuk ke Dashboard
            </button>
        </form>

        <div style="margin-top: 24px; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
            Belum memiliki akun? <br>
            <span style="color: var(--text-secondary); font-size: 0.8rem;">Hubungi administrator atau daftar melalui link sesi.</span>
        </div>
    </div>
</div>

<style>
    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: radial-gradient(circle at top right, rgba(var(--accent-rgb), 0.15), transparent 40%),
                    radial-gradient(circle at bottom left, rgba(var(--accent-rgb), 0.1), transparent 40%);
    }
</style>
@endsection
