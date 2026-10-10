@extends('layouts.admin')

@section('title', 'Modul Quiz')
@section('header_title', 'Manajemen Quiz')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Daftar Soal Quiz</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola pertanyaan ujian untuk fitur Quiz tanpa mata pelajaran.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('admin.quizzes.import-view') }}" class="btn-primary" style="background: var(--accent); white-space: nowrap; text-decoration: none;">
                <i class="fas fa-file-import"></i> Import dari Bank Soal
            </a>
            
            <button class="btn-primary" onclick="openQuestionModal('create', '{{ $jenjang ?? '' }}')" style="background: #10b981; white-space: nowrap; text-decoration: none; border: none; font-family: inherit; font-size: inherit; cursor: pointer;">
                <i class="fas fa-plus"></i> Tambah Manual
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.quizzes.index') }}" style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px; width: 100%; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px; flex: 1; min-width: 300px;">
            <select name="jenjang" class="form-input" onchange="this.form.submit()" style="flex: 1; margin-bottom: 0; color: #000; min-width: 150px;">
                <option value="">Semua Jenjang</option>
                @foreach($jenjangList as $j)
                    <option value="{{ $j }}" {{ (string) ($jenjang ?? '') === (string) $j ? 'selected' : '' }}>
                        {{ $j }}
                    </option>
                @endforeach
            </select>

            <div style="position: relative; flex: 2; min-width: 200px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" name="search" id="searchInput" class="form-input" placeholder="Cari soal (tekan enter)..." value="{{ $search ?? '' }}" style="width: 100%; padding-left: 44px; margin-bottom: 0;">
            </div>
        </div>

        @if(($jenjang ?? null) || ($search ?? null))
            <a href="{{ route('admin.quizzes.index') }}" class="btn-primary" style="background: #ef4444; white-space: nowrap; flex-shrink: 0; text-decoration: none;">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>

    @if(session('success'))
        <div style="margin-bottom: 24px; padding: 16px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #059669;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>JENJANG</th>
                    <th>TIPE</th>
                    <th>SOAL</th>
                    <th style="width: 150px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quizzes as $quiz)
                <tr>
                    <td>#{{ $quiz->id }}</td>
                    <td>
                        <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);">{{ $quiz->jenjang }}</span>
                    </td>
                    <td>
                        @if($quiz->type === 'pilihan_ganda') Pilihan Ganda
                        @elseif($quiz->type === 'benar_salah') Benar / Salah
                        @elseif($quiz->type === 'multiple_benar_salah') Multiple B/S
                        @else {{ Str::headline($quiz->type) }} @endif
                    </td>
                    <td style="max-width: 400px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            @if($quiz->question_image)
                                <img src="{{ asset('storage/' . $quiz->question_image) }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; flex-shrink: 0;">
                            @endif
                            <div class="math-render-cell" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">{{ str_replace('&nbsp;', ' ', strip_tags($quiz->question_text)) }}</div>
                        </div>
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <button type="button" class="btn-icon" onclick="previewQuestion({{ $quiz->id }})" title="Preview" style="border: none; background: transparent; cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn-icon" onclick="editQuestion({{ $quiz->id }})" title="Edit" style="border: none; background: transparent; cursor: pointer;">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.quizzes.destroy', $quiz->id) }}" method="POST" style="margin: 0; display: inline-flex;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn-icon delete" title="Hapus" onclick="if(confirm('Yakin ingin menghapus soal ini?')) this.form.submit();" style="border: none; background: transparent; cursor: pointer;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        Belum ada soal quiz.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $quizzes->links() }}
    </div>
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

@push('head')
@include('admin.quizzes._modal_styles')
@endpush

<!-- Question Modal -->
@include('admin.quizzes._modal_form')

@endsection

@push('scripts')
@include('admin.quizzes._modal_scripts')
@endpush
