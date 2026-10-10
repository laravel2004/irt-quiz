@php
    $inactive = 'background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;';
@endphp
<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px;">
    <a href="{{ route('admin.quiz-settings.index') }}" class="btn-primary"
       style="text-decoration:none; white-space:nowrap; {{ request()->routeIs('admin.quiz-settings.*') ? '' : $inactive }}">
        <i class="fas fa-sliders-h"></i> Pengaturan
    </a>
    <a href="{{ route('admin.quiz-logs.index') }}" class="btn-primary"
       style="text-decoration:none; white-space:nowrap; {{ request()->routeIs('admin.quiz-logs.*') ? '' : $inactive }}">
        <i class="fas fa-history"></i> Log Jawaban
    </a>
</div>
