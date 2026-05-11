@extends('layouts.admin')

@section('title', 'Detail Sesi: ' . $session->name)
@section('header_title', 'Detail Sesi Ujian')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('admin.sessions.index') }}" style="color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Sesi
    </a>
</div>

<div class="responsive-grid" style="margin-bottom: 32px;">
    <!-- Main Info -->
    <div class="glass animate-fade-in" style="padding: 32px;">
        <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; gap: 20px;">
            <div>
                <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 8px;">{{ $session->name }}</h2>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <code style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px; color: var(--accent);">{{ $session->code }}</code>
                    <span class="badge {{ $session->is_active ? 'active' : '' }}">
                        {{ $session->is_active ? 'Aktif' : 'Non-Aktif' }}
                    </span>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">Target Soal</div>
                <div style="font-size: 2rem; font-weight: 700; font-family: 'Outfit', sans-serif; margin-bottom: 12px;">{{ $session->total_questions }} <span style="font-size: 1rem; color: var(--text-secondary); font-weight: 400;">butir</span></div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button class="btn-primary" onclick="toggleStatus({{ $session->id }})" style="height: 32px; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 8px; background: {{ $session->is_active ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)' }}; color: {{ $session->is_active ? '#ef4444' : '#10b981' }}; border: 1px solid {{ $session->is_active ? '#ef4444' : '#10b981' }};">
                        <i class="fas {{ $session->is_active ? 'fa-lock' : 'fa-lock-open' }}"></i>
                        {{ $session->is_active ? 'Tutup Sesi' : 'Buka Sesi' }}
                    </button>
                    <a href="{{ route('admin.sessions.preview-questions', $session->id) }}" class="btn-primary" style="height: 32px; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: rgba(var(--accent-rgb), 0.1); color: var(--accent); border: 1px solid var(--accent);">
                        <i class="fas fa-eye"></i> Preview Soal
                    </a>
                </div>
            </div>
        </div>

        <div class="responsive-grid" style="grid-template-columns: repeat(4, 1fr); gap: 24px;">
            <div class="stat-card glass" style="padding: 16px;">
                <div class="label"><i class="fas fa-calendar"></i> Tanggal</div>
                <div style="font-size: 1rem; margin-top: 8px;">{{ $session->start_date }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">s/d {{ $session->end_date }}</div>
            </div>
            <div class="stat-card glass" style="padding: 16px;">
                <div class="label"><i class="fas fa-clock"></i> Waktu</div>
                <div style="font-size: 1rem; margin-top: 8px;">{{ substr($session->start_time, 0, 5) }} - {{ substr($session->end_time, 0, 5) }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">WIB</div>
            </div>
            <div class="stat-card glass" style="padding: 16px;">
                <div class="label"><i class="fas fa-hourglass-half"></i> Durasi</div>
                <div style="font-size: 1rem; margin-top: 8px;">{{ $session->duration }} Menit</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">Pengerjaan</div>
            </div>
            <div class="stat-card glass" style="padding: 16px;">
                <div class="label"><i class="fas fa-trophy"></i> Skor Maksimal</div>
                <div style="font-size: 1rem; margin-top: 8px;">Raw: {{ $session->max_score_raw }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">IRT: {{ $session->max_score_irt }}</div>
            </div>
        </div>
    </div>

    <!-- Category Distribution -->
    <div class="glass animate-fade-in" style="padding: 32px;">
        <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; margin-bottom: 20px;">Distribusi Kategori</h3>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            @foreach($session->sessionCategories as $sc)
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 8px;">
                    <span>{{ data_get($sc->category, 'name') }}</span>
                    <span style="color: var(--accent);">{{ $sc->percentage }}%</span>
                </div>
                <div style="height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden;">
                    <div style="height: 100%; width: {{ $sc->percentage }}%; background: var(--accent);"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Participant Section -->
<div class="glass animate-fade-in" style="padding: 32px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif;">Daftar Peserta</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px;">Total Peserta Terdaftar: {{ $session->participants->count() }} orang</p>
        </div>
        <div class="flex-stack-mobile" style="display: flex; gap: 12px; align-items: center;">
            <div style="position: relative; width: 250px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.8rem;"></i>
                <input type="text" id="participantSearch" class="form-input" placeholder="Cari nama peserta..." style="padding-left: 36px; margin-bottom: 0; font-size: 0.85rem; height: 38px;">
            </div>
            <button class="btn-primary" style="background: transparent; border: 1px solid var(--accent); color: var(--accent); height: 38px; font-size: 0.85rem;" onclick="copyRegistrationLink()">
                <i class="fas fa-link"></i> Salin Link
            </button>
            <button class="btn-primary" onclick="openParticipantModal()" style="height: 38px; font-size: 0.85rem;">
                <i class="fas fa-user-plus"></i> Tambah Peserta
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="participantTable">
            <thead>
                <tr>
                    <th style="width: 60px;">NO</th>
                    <th style="width: 150px;">KODE AKSES</th>
                    <th>NAMA PESERTA</th>
                    <th style="width: 150px;">WHATSAPP</th>
                    <th>ALAMAT</th>
                    <th style="width: 80px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody id="participantTableBody">
                @php $participants = $session->participants; @endphp
                @forelse($participants as $index => $participant)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><code style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 8px; border-radius: 4px; font-weight: 700; letter-spacing: 1px;">{{ data_get($participant, 'access_code') }}</code></td>
                    <td><div style="font-weight: 600;">{{ data_get($participant, 'name') }}</div></td>
                    <td>{{ data_get($participant, 'whatsapp') }}</td>
                    <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ data_get($participant, 'address') ?? '-' }}</td>
                    <td style="text-align: center;">
                        <button class="btn-icon delete" onclick="deleteParticipant({{ $participant->id }})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 60px; color: var(--text-secondary);">
                        <i class="fas fa-users-slash" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.2;"></i>
                        <p>Belum ada peserta terdaftar untuk sesi ini.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
        <div style="font-size: 0.85rem; color: var(--text-secondary);">
            Menampilkan <span id="startIdx">0</span> - <span id="endIdx">0</span> dari <span id="totalParticipantsCount">{{ $session->participants->count() }}</span> peserta
        </div>
        <div style="display: flex; gap: 8px;" id="paginationControls"></div>
    </div>
</div>

<!-- Participant Modal -->
<div class="modal-overlay" id="participantModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Tambah Peserta Baru</h3>
            <button class="close-modal" onclick="closeParticipantModal()">&times;</button>
        </div>
        <form id="participantForm">
            @csrf
            <input type="hidden" name="exam_session_id" value="{{ $session->id }}">
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" class="form-input" placeholder="Masukkan nama peserta" required>
            </div>

            <div class="form-group">
                <label>Nomor WhatsApp</label>
                <input type="text" name="whatsapp" class="form-input" placeholder="Contoh: 08123456789" required>
            </div>

            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" class="form-input" style="height: 80px;" placeholder="Masukkan alamat (opsional)"></textarea>
            </div>

            <div style="background: rgba(59, 130, 246, 0.1); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <p style="font-size: 0.8rem; color: var(--accent); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle"></i> 
                    Kode akses 6 digit akan di-generate otomatis oleh sistem.
                </p>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeParticipantModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Peserta</button>
            </div>
        </form>
    </div>
</div>

@if(!$session->is_active)
<!-- IRT Results Section -->
<div class="glass animate-fade-in" style="padding: 32px; margin-top: 32px; border-top: 4px solid var(--accent);">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif;">Hasil Penilaian IRT</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px;">Peringkat peserta berdasarkan pembobotan tingkat kesulitan soal.</p>
        </div>
        <div class="flex-stack-mobile" style="display: flex; gap: 12px; align-items: center;">
            <div style="position: relative; width: 250px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.8rem;"></i>
                <input type="text" id="irtSearch" class="form-input" placeholder="Cari nama peserta..." style="padding-left: 36px; margin-bottom: 0; font-size: 0.85rem; height: 38px;">
            </div>
            <a href="{{ route('admin.sessions.export', $session->id) }}" class="btn-primary" style="background: transparent; border: 1px solid #10b981; color: #10b981; height: 38px; font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <button class="btn-primary" style="background: var(--accent); height: 38px; font-size: 0.85rem;" onclick="generateIRT()">
                <i class="fas fa-magic"></i> Generate Hasil IRT
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="irtResultsTable">
            <thead>
                <tr>
                    <th style="width: 60px;">RANK</th>
                    <th>PESERTA</th>
                    <th style="text-align: center;">B</th>
                    <th style="text-align: center;">S</th>
                    <th style="text-align: center;">K</th>
                    <th style="text-align: center;">SKOR RAW</th>
                    <th style="text-align: center;">SKOR IRT</th>
                </tr>
            </thead>
            <tbody id="irtResultsTableBody">
                @php $results = $session->results->sortByDesc(fn($r) => [$r->irt_score, $r->total_correct]); @endphp
                @forelse($results as $index => $result)
                <tr>
                    <td>
                        @if($loop->index == 0)
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #fbbf24, #d97706); display: flex; align-items: center; justify-content: center; font-weight: 800; color: #451a03; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); position: relative;">
                                <i class="fas fa-crown" style="position: absolute; top: -10px; font-size: 0.7rem;"></i>
                                1
                            </div>
                        @elseif($loop->index == 1)
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #94a3b8, #475569); display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; box-shadow: 0 4px 10px rgba(71, 85, 105, 0.2);">
                                2
                            </div>
                        @elseif($loop->index == 2)
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #92400e, #451a03); display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; box-shadow: 0 4px 10px rgba(69, 26, 3, 0.2);">
                                3
                            </div>
                        @else
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--text-secondary); font-size: 0.85rem;">
                                {{ $loop->iteration }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ data_get($result->participant, 'name') }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ data_get($result->participant, 'access_code') }}</div>
                    </td>
                    <td style="text-align: center; color: #10b981; font-weight: 600;">{{ $result->total_correct }}</td>
                    <td style="text-align: center; color: #ef4444;">{{ $result->total_incorrect }}</td>
                    <td style="text-align: center; color: var(--text-secondary);">{{ $result->total_blank }}</td>
                    <td style="text-align: center;">{{ number_format($result->score, 1) }}</td>
                    <td style="text-align: center;">
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--accent); font-family: 'Outfit', sans-serif;">
                            {{ number_format($result->irt_score, 0) }}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-secondary);">
                        <i class="fas fa-calculator" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.2;"></i>
                        <p>Hasil belum digenerate. Klik button di atas untuk mulai menghitung skor IRT.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- IRT Pagination Controls -->
    <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
        <div style="font-size: 0.85rem; color: var(--text-secondary);">
            Menampilkan <span id="irtStartIdx">0</span> - <span id="irtEndIdx">0</span> dari <span id="totalIRTCount">{{ $session->results->count() }}</span> hasil
        </div>
        <div style="display: flex; gap: 8px;" id="irtPaginationControls">
            <!-- Buttons rendered by JS -->
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    const pModal = document.getElementById('participantModal');
    const pForm = document.getElementById('participantForm');
    
    // Participants Pagination & Search Logic
    let allParticipants = @json($session->participants);
    let filteredParticipants = [...allParticipants];
    let itemsPerPage = 10;
    let currentPage = 1;

    function renderParticipants() {
        const body = document.getElementById('participantTableBody');
        const searchVal = document.getElementById('participantSearch').value.toLowerCase();
        
        filteredParticipants = allParticipants.filter(p => 
            p.name.toLowerCase().includes(searchVal) || 
            p.access_code.toLowerCase().includes(searchVal)
        );

        const total = filteredParticipants.length;
        const totalPages = Math.ceil(total / itemsPerPage);
        if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;

        const start = (currentPage - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, total);
        const pageItems = filteredParticipants.slice(start, end);

        body.innerHTML = pageItems.length ? pageItems.map((p, i) => `
            <tr>
                <td>${start + i + 1}</td>
                <td><code style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 8px; border-radius: 4px; font-weight: 700; letter-spacing: 1px;">${p.access_code}</code></td>
                <td><div style="font-weight: 600;">${p.name}</div></td>
                <td>${p.whatsapp || '-'}</td>
                <td style="color: var(--text-secondary); font-size: 0.85rem;">${p.address || '-'}</td>
                <td style="text-align: center;">
                    <button class="btn-icon delete" onclick="deleteParticipant(${p.id})" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('') : `
            <tr>
                <td colspan="6" style="text-align: center; padding: 60px; color: var(--text-secondary);">
                    <i class="fas fa-users-slash" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.2;"></i>
                    <p>${searchVal ? 'Tidak ada peserta yang cocok dengan pencarian.' : 'Belum ada peserta terdaftar.'}</p>
                </td>
            </tr>
        `;

        // Update Counter
        document.getElementById('startIdx').innerText = total > 0 ? start + 1 : 0;
        document.getElementById('endIdx').innerText = end;
        document.getElementById('totalParticipantsCount').innerText = total;

        // Render Pagination Buttons
        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        const container = document.getElementById('paginationControls');
        container.innerHTML = '';

        if (totalPages <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.className = `btn-icon ${currentPage === 1 ? 'disabled' : ''}`;
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => { if(currentPage > 1) { currentPage--; renderParticipants(); } };
        container.appendChild(prevBtn);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = `btn-icon ${currentPage === i ? 'active' : ''}`;
            btn.innerText = i;
            btn.style.width = '34px';
            btn.style.height = '34px';
            btn.style.borderRadius = '8px';
            btn.style.background = currentPage === i ? 'var(--accent)' : 'rgba(255,255,255,0.05)';
            btn.style.color = 'white';
            btn.onclick = () => { currentPage = i; renderParticipants(); };
            container.appendChild(btn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = `btn-icon ${currentPage === totalPages ? 'disabled' : ''}`;
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.onclick = () => { if(currentPage < totalPages) { currentPage++; renderParticipants(); } };
        container.appendChild(nextBtn);
    }

    document.getElementById('participantSearch').oninput = () => {
        currentPage = 1;
        renderParticipants();
    };

    // IRT Results Pagination & Search Logic
    let allIRTResults = @json($session->results->load('participant'));
    // Sort results by IRT score desc, then total correct desc
    allIRTResults.sort((a, b) => b.irt_score - a.irt_score || b.total_correct - a.total_correct);
    
    let filteredIRTResults = [...allIRTResults];
    let irtItemsPerPage = 10;
    let irtCurrentPage = 1;

    function renderIRTResults() {
        const body = document.getElementById('irtResultsTableBody');
        if (!body) return; // Only if results section exists

        const searchVal = (document.getElementById('irtSearch')?.value || '').toLowerCase();
        
        filteredIRTResults = allIRTResults.filter(r => 
            (r.participant?.name || '').toLowerCase().includes(searchVal) || 
            (r.participant?.access_code || '').toLowerCase().includes(searchVal)
        );

        const total = filteredIRTResults.length;
        const totalPages = Math.ceil(total / irtItemsPerPage);
        if (irtCurrentPage > totalPages && totalPages > 0) irtCurrentPage = totalPages;

        const start = (irtCurrentPage - 1) * irtItemsPerPage;
        const end = Math.min(start + irtItemsPerPage, total);
        const pageItems = filteredIRTResults.slice(start, end);

        body.innerHTML = pageItems.length ? pageItems.map((r, i) => {
            const rankIdx = allIRTResults.findIndex(x => x.id === r.id);
            let rankHtml = '';
            
            if (rankIdx === 0) {
                rankHtml = `<div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #fbbf24, #d97706); display: flex; align-items: center; justify-content: center; font-weight: 800; color: #451a03; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); position: relative;"><i class="fas fa-crown" style="position: absolute; top: -10px; font-size: 0.7rem;"></i>1</div>`;
            } else if (rankIdx === 1) {
                rankHtml = `<div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #94a3b8, #475569); display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; box-shadow: 0 4px 10px rgba(71, 85, 105, 0.2);">2</div>`;
            } else if (rankIdx === 2) {
                rankHtml = `<div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #92400e, #451a03); display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; box-shadow: 0 4px 10px rgba(69, 26, 3, 0.2);">3</div>`;
            } else {
                rankHtml = `<div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--text-secondary); font-size: 0.85rem;">${rankIdx + 1}</div>`;
            }

            return `
                <tr>
                    <td>${rankHtml}</td>
                    <td>
                        <div style="font-weight: 600;">${r.participant?.name || '-'}</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">${r.participant?.access_code || '-'}</div>
                    </td>
                    <td style="text-align: center; color: #10b981; font-weight: 600;">${r.total_correct}</td>
                    <td style="text-align: center; color: #ef4444;">${r.total_incorrect}</td>
                    <td style="text-align: center; color: var(--text-secondary);">${r.total_blank}</td>
                    <td style="text-align: center;">${parseFloat(r.score).toFixed(1)}</td>
                    <td style="text-align: center;">
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--accent); font-family: 'Outfit', sans-serif;">
                            ${Math.round(r.irt_score)}
                        </div>
                    </td>
                </tr>
            `;
        }).join('') : `
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-secondary);">
                    <i class="fas fa-calculator" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.2;"></i>
                    <p>${searchVal ? 'Tidak ada hasil yang cocok dengan pencarian.' : 'Hasil belum digenerate.'}</p>
                </td>
            </tr>
        `;

        document.getElementById('irtStartIdx').innerText = total > 0 ? start + 1 : 0;
        document.getElementById('irtEndIdx').innerText = end;
        document.getElementById('totalIRTCount').innerText = total;

        renderIRTPagination(totalPages);
    }

    function renderIRTPagination(totalPages) {
        const container = document.getElementById('irtPaginationControls');
        if (!container) return;
        container.innerHTML = '';

        if (totalPages <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.className = `btn-icon ${irtCurrentPage === 1 ? 'disabled' : ''}`;
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = irtCurrentPage === 1;
        prevBtn.onclick = () => { if(irtCurrentPage > 1) { irtCurrentPage--; renderIRTResults(); } };
        container.appendChild(prevBtn);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = `btn-icon ${irtCurrentPage === i ? 'active' : ''}`;
            btn.innerText = i;
            btn.style.width = '34px'; btn.style.height = '34px'; btn.style.borderRadius = '8px';
            btn.style.background = irtCurrentPage === i ? 'var(--accent)' : 'rgba(255,255,255,0.05)';
            btn.style.color = 'white';
            btn.onclick = () => { irtCurrentPage = i; renderIRTResults(); };
            container.appendChild(btn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = `btn-icon ${irtCurrentPage === totalPages ? 'disabled' : ''}`;
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = irtCurrentPage === totalPages;
        nextBtn.onclick = () => { if(irtCurrentPage < totalPages) { irtCurrentPage++; renderIRTResults(); } };
        container.appendChild(nextBtn);
    }

    if (document.getElementById('irtSearch')) {
        document.getElementById('irtSearch').oninput = () => {
            irtCurrentPage = 1;
            renderIRTResults();
        };
    }

    // Initial render
    renderParticipants();
    renderIRTResults();

    function openParticipantModal() {
        pForm.reset();
        pModal.classList.add('active');
    }

    function closeParticipantModal() {
        pModal.classList.remove('active');
    }

    function copyRegistrationLink() {
        const url = "{{ route('public.session.registration', $session->code) }}";
        navigator.clipboard.writeText(url).then(() => {
            showToast('Link registrasi berhasil disalin!');
        });
    }

    pForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch("{{ route('admin.participants.store') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Peserta berhasil ditambahkan');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Gagal menambahkan peserta', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan sistem', 'error');
        });
    });

    function deleteParticipant(id) {
        customConfirm('Hapus peserta ini dari sesi?', function() {
            fetch(`/admin/participants/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Peserta berhasil dihapus');
                    setTimeout(() => location.reload(), 500);
                }
            });
        });
    }

    function toggleStatus(id) {
        fetch(`/admin/sessions/${id}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    function generateIRT() {
        showToast('Sedang menghitung skor IRT...', 'info');
        fetch("{{ route('admin.sessions.generate-irt', $session->id) }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal generate IRT', 'error');
        });
    }
</script>
@endpush
