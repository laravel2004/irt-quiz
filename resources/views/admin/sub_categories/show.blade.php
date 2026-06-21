@extends('layouts.admin')

@section('title', 'Detail Sub Pelajaran')
@section('header_title', 'Detail Sub Pelajaran: ' . $subCategory->name)

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
        <div>
            <div style="margin-bottom: 12px;">
                <a href="{{ route('admin.sub-categories.index', ['category_id' => $subCategory->category_id]) }}" class="btn-icon" style="text-decoration: none; display: inline-flex; width: auto; padding: 6px 12px; border-radius: 6px; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 8px;">{{ $subCategory->name }}</h3>
            <div style="display: flex; gap: 8px;">
                <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);">Induk: {{ $subCategory->category->name }}</span>
                <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">Total Soal: {{ $subCategory->questions->count() }}</span>
            </div>
        </div>
        <a href="{{ route('admin.questions.index', ['action' => 'create', 'category_id' => $subCategory->category_id, 'sub_category_id' => $subCategory->id]) }}" class="btn-primary" style="text-decoration: none;">
            <i class="fas fa-plus"></i> Buat Bank Soal
        </a>
    </div>

    <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 24px 0;">

    <h4 style="margin-bottom: 16px; font-family: 'Outfit', sans-serif;">Daftar Soal di Sub Pelajaran Ini</h4>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>TIPE SOAL</th>
                    <th>KONTEN SOAL</th>
                    <th style="width: 100px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subCategory->questions as $question)
                <tr>
                    <td>#{{ $question->id }}</td>
                    <td>
                        @if($question->type === 'pilihan_ganda') Pilihan Ganda
                        @elseif($question->type === 'benar_salah') Benar / Salah
                        @elseif($question->type === 'multiple_benar_salah') Multiple B/S
                        @else Multiple Choice @endif
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
                        <button class="btn-icon" onclick="previewQuestion({{ $question->id }})" title="Preview Soal">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        Belum ada soal untuk sub pelajaran ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal-overlay" id="previewModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-eye" style="margin-right:8px; color:var(--accent);"></i> Preview Soal</h3>
            <button class="close-modal" onclick="closePreviewModal()">&times;</button>
        </div>
        <div style="padding: 24px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid var(--glass-border); margin-bottom: 24px;">
            <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
                <span id="previewCategory" class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);"></span>
                <span id="previewSubCategory" class="badge" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; display: none;"></span>
                <span id="previewType" class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"></span>
                <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-check"></i> <span id="previewScoreCorrect"></span> | <i class="fas fa-times"></i> <span id="previewScoreIncorrect"></span></span>
            </div>
            
            <div id="previewImageContainer" style="margin-bottom: 20px; text-align: center; display: none;">
                <img id="previewImage" src="" style="max-height: 250px; border-radius: 8px; border: 1px solid var(--glass-border);">
            </div>
            
            <div id="previewText" style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 24px; font-family: 'Inter', sans-serif;"></div>
            
            <h5 style="color: var(--text-secondary); margin-bottom: 12px; font-weight: 600;">Opsi Jawaban / Pernyataan:</h5>
            <div id="previewOptions" style="display: flex; flex-direction: column; gap: 12px;"></div>
        </div>
        
        <div id="previewExplanationContainer" style="display: none; padding: 24px; background: rgba(139, 92, 246, 0.05); border-radius: 12px; border: 1px dashed rgba(139, 92, 246, 0.3);">
            <h5 style="color: #8b5cf6; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-lightbulb"></i> Pembahasan</h5>
            <div id="previewExplanation" style="line-height: 1.6;"></div>
        </div>
        
        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
            <button class="btn-primary" onclick="closePreviewModal()" style="background: #eff6ff; border: 1px solid var(--glass-border); color: #0f172a;">Tutup Preview</button>
        </div>
    </div>
</div>

<style>
    /* Preview Modal Custom Styling */
    .preview-option-item {
        padding: 12px 16px;
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
        border: 1px solid var(--glass-border);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .preview-option-item.correct {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.3);
    }
</style>

@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (window.renderMathInElement) {
            const cells = document.querySelectorAll('.math-render-cell');
            cells.forEach(cell => {
                renderMathInElement(cell, {
                    delimiters: [
                        {left: '$$', right: '$$', display: false},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: false}
                    ],
                    throwOnError: false
                });
            });
        }
    });

    function closePreviewModal() {
        document.getElementById('previewModal').classList.remove('active');
    }

    function previewQuestion(id) {
        fetch(`/admin/questions/${id}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const q = data.data;
            document.getElementById('previewCategory').textContent = q.category?.name || 'Mata Pelajaran';
            
            const previewSub = document.getElementById('previewSubCategory');
            if (q.sub_category) {
                previewSub.textContent = q.sub_category.name;
                previewSub.style.display = 'inline-block';
            } else {
                previewSub.style.display = 'none';
            }
            
            let typeLabel = 'Pilihan Ganda';
            if (q.type === 'benar_salah') typeLabel = 'Benar / Salah';
            else if (q.type === 'multiple_choice') typeLabel = 'Multiple Choice';
            else if (q.type === 'multiple_benar_salah') typeLabel = 'Multiple Benar/Salah';
            document.getElementById('previewType').textContent = typeLabel;
            
            document.getElementById('previewScoreCorrect').textContent = q.score_correct || 1;
            document.getElementById('previewScoreIncorrect').textContent = q.score_incorrect || 0;
            
            if (q.question_image) {
                document.getElementById('previewImage').src = `/storage/${q.question_image}`;
                document.getElementById('previewImageContainer').style.display = 'block';
            } else {
                document.getElementById('previewImageContainer').style.display = 'none';
            }
            
            document.getElementById('previewText').innerHTML = q.question_text || '-';
            
            const optionsContainer = document.getElementById('previewOptions');
            optionsContainer.innerHTML = '';
            
            if (q.type === 'multiple_benar_salah') {
                q.options.forEach((opt, idx) => {
                    const isBenar = q.correct_answer.includes(idx.toString());
                    optionsContainer.innerHTML += `
                        <div class="preview-option-item">
                            <span style="flex: 1;">${opt}</span>
                            <span class="badge" style="background: ${isBenar ? 'rgba(16,185,129,0.2); color:#10b981' : 'rgba(239,68,68,0.2); color:#ef4444'}">Jawaban: ${isBenar ? 'Benar' : 'Salah'}</span>
                        </div>
                    `;
                });
            } else {
                q.options.forEach((opt, idx) => {
                    const isCorrect = q.correct_answer.includes(idx.toString());
                    optionsContainer.innerHTML += `
                        <div class="preview-option-item ${isCorrect ? 'correct' : ''}">
                            <div style="width: 24px; height: 24px; border-radius: 50%; background: ${isCorrect ? '#10b981' : 'rgba(255,255,255,0.1)'}; color: #0f172a; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">
                                ${isCorrect ? '<i class="fas fa-check"></i>' : String.fromCharCode(65 + idx)}
                            </div>
                            <span>${opt}</span>
                        </div>
                    `;
                });
            }
            
            if (q.explanation) {
                document.getElementById('previewExplanation').innerHTML = q.explanation;
                document.getElementById('previewExplanationContainer').style.display = 'block';
            } else {
                document.getElementById('previewExplanationContainer').style.display = 'none';
            }
            
            document.getElementById('previewModal').classList.add('active');
            
            // Render KaTeX for math formulas inside the preview
            setTimeout(() => {
                if (window.renderMathInElement) {
                    const renderOpts = {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$', right: '$', display: false},
                            {left: '\\(', right: '\\)', display: false},
                            {left: '\\[', right: '\\]', display: true}
                        ],
                        throwOnError: false
                    };
                    renderMathInElement(document.getElementById('previewText'), renderOpts);
                    renderMathInElement(document.getElementById('previewExplanation'), renderOpts);
                    renderMathInElement(document.getElementById('previewOptions'), renderOpts);
                }
            }, 100);
        });
    }
</script>
@endpush
