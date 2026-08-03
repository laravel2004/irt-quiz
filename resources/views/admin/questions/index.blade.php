@extends('layouts.admin')

@section('title', 'Bank Soal')
@section('header_title', 'Manajemen Bank Soal')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Bank Soal</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola pertanyaan ujian untuk berbagai kategori.</p>
        </div>
        <div class="flex-stack-mobile" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            <form method="GET" action="{{ route('admin.questions.index') }}" style="display: flex; gap: 12px; align-items: center; margin: 0; flex-wrap: wrap;">
                <select name="category_id" class="form-input" onchange="this.form.submit()" style="width: 220px; margin-bottom: 0; color: #000;">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) ($filters['category_id'] ?? '') === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <div style="position: relative; width: 300px;">
                    <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="text" name="search" id="searchInput" class="form-input" placeholder="Cari soal (tekan enter)..." value="{{ $filters['search'] ?? '' }}" style="padding-left: 44px; margin-bottom: 0;">
                </div>

                @if(($filters['category_id'] ?? null) || ($filters['search'] ?? null))
                    <a href="{{ route('admin.questions.index') }}" class="form-input" style="width: auto; margin-bottom: 0; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 0 16px; white-space: nowrap;">
                        Reset
                    </a>
                @endif
            </form>

            <button class="btn-primary" onclick="openQuestionModal('create')">
                <i class="fas fa-plus"></i> Tambah Soal
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>MATA PELAJARAN</th>
                    <th>TIPE</th>
                    <th>KODE SOAL</th>
                    <th>SOAL</th>
                    <th style="width: 150px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questions as $question)
                <tr>
                    <td>#{{ $question->id }}</td>
                    <td>
                        <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);">{{ $question->category->name }}</span>
                        @if($question->subCategory)
                            <span class="badge" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; margin-top: 4px;">{{ $question->subCategory->name }}</span>
                        @endif
                    </td>
                    <td>
                        @if($question->type === 'pilihan_ganda') Pilihan Ganda
                        @elseif($question->type === 'benar_salah') Benar / Salah
                        @elseif($question->type === 'multiple_benar_salah') Multiple B/S
                        @else Multiple Choice @endif
                    </td>
                    <td>
                        <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">{{ $question->kode_soal ?? '-' }}</span>
                    </td>
                    <td style="max-width: 400px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            @if($question->question_image)
                                <img src="{{ asset('storage/' . $question->question_image) }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; flex-shrink: 0;">
                            @endif
                            <div class="math-render-cell" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">{{ str_replace('&nbsp;', ' ', strip_tags($question->question_text)) }}</div>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <button class="btn-icon" onclick="previewQuestion({{ $question->id }})" title="Preview">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon" onclick="editQuestion({{ $question->id }})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon delete" onclick="deleteQuestion({{ $question->id }})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        {{ ($filters['category_id'] ?? null) ? 'Tidak ada soal untuk mata pelajaran yang dipilih.' : 'Belum ada soal.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $questions->appends(array_filter($filters))->links() }}
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
</style>

<!-- Question Modal -->
@include('admin.questions._modal_form')

@endsection

@push('scripts')
@include('admin.questions._modal_scripts')
@endpush
