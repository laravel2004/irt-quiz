@extends('layouts.admin')

@section('title', 'Import Quiz')
@section('header_title', 'Import dari Bank Soal')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Pilih Soal dari Bank Soal</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Centang soal yang ingin disalin ke modul Quiz dan tentukan jenjangnya.</p>
        </div>
        <div class="flex-stack-mobile" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('admin.quizzes.index') }}" class="btn-primary" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div style="margin-bottom: 24px; padding: 16px; border-radius: 12px; background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.2); color: #e11d48;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.quizzes.import-view') }}" style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px; width: 100%; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px; flex: 1; min-width: 300px;">
            <div style="position: relative; flex: 1;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" name="search" id="searchInput" class="form-input" placeholder="Cari soal (tekan enter)..." value="{{ $search ?? '' }}" style="width: 100%; padding-left: 44px; margin-bottom: 0;">
            </div>
        </div>

        @if(isset($search) && $search)
            <a href="{{ route('admin.quizzes.import-view') }}" class="btn-primary" style="background: #ef4444; white-space: nowrap; flex-shrink: 0; text-decoration: none;">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>

    <form action="{{ route('admin.quizzes.import-process') }}" method="POST">
        @csrf
        
        <div style="margin-bottom: 24px; padding: 20px; background: #f8fafc; border: 1px solid var(--glass-border); border-radius: 16px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #1e293b;">Pilih Jenjang Tujuan <span style="color: #e11d48;">*</span></label>
            <select name="jenjang" class="form-input" style="max-width: 400px; margin-bottom: 0;" required>
                <option value="" disabled selected>-- Pilih Jenjang --</option>
                <option value="SD">SD</option>
                <option value="SMP">SMP</option>
                <option value="SMA">SMA / SMK</option>
                <option value="Mahasiswa">Mahasiswa</option>
                <option value="Umum">Umum</option>
            </select>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">
                            <input type="checkbox" id="checkAll" style="width: 18px; height: 18px; cursor: pointer;">
                        </th>
                        <th>MATA PELAJARAN</th>
                        <th>TIPE</th>
                        <th>SOAL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questions as $q)
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" name="question_ids[]" value="{{ $q->id }}" class="question-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                        </td>
                        <td>
                            <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);">{{ $q->category->name ?? '-' }}</span>
                        </td>
                        <td>
                            @if($q->type === 'pilihan_ganda') Pilihan Ganda
                            @elseif($q->type === 'benar_salah') Benar / Salah
                            @elseif($q->type === 'multiple_benar_salah') Multiple B/S
                            @else {{ Str::headline($q->type) }} @endif
                        </td>
                        <td style="max-width: 400px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                @if($q->question_image)
                                    <img src="{{ asset('storage/' . $q->question_image) }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; flex-shrink: 0;">
                                @endif
                                <div class="math-render-cell" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">{{ str_replace('&nbsp;', ' ', strip_tags($q->question_text)) }}</div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            Bank soal kosong.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>{{ $questions->links() }}</div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Import Soal Terpilih
            </button>
        </div>
    </form>
</div>

<style>
    /* Premium Pagination Styling */
    .pagination {
        display: flex !important;
        gap: 8px !important;
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .page-item {
        margin: 0 !important;
    }
    .page-item .page-link {
        width: 40px !important;
        height: 40px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #f8fafc !important;
        border: 1px solid var(--glass-border) !important;
        border-radius: 12px !important;
        color: var(--text-secondary) !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
        padding: 0 !important;
        font-size: 0.9rem !important;
    }
    .page-item.active .page-link {
        background: var(--accent) !important;
        border-color: var(--accent) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
    }
    .page-item.disabled .page-link {
        opacity: 0.3 !important;
        cursor: not-allowed !important;
        background: transparent !important;
    }
    .page-item .page-link:hover:not(.disabled):not(.active) {
        background: #eff6ff !important;
        transform: translateY(-2px) !important;
        color: white !important;
    }
    /* Hide the 'Showing X to Y' part if it's messy */
    nav div:first-child {
        display: none !important;
    }
    nav div:last-child {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }
</style>

<script>
    document.getElementById('checkAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.question-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
@endsection
