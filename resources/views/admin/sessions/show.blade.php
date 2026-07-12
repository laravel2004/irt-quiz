@extends('layouts.admin')

@section('title', 'Detail Sesi: ' . $session->name)
@section('header_title', 'Detail Sesi Ujian')

@section('content')

<style>
    .session-detail-light-panel {
        background: #ffffff !important;
        border: 1px solid var(--glass-border) !important;
    }
    .session-detail-soft-panel {
        background: #f8fafc !important;
        border: 1px solid var(--glass-border) !important;
    }
    #participantModal .form-input,
    #newUserModal .form-input,
    #participantModal select.form-input,
    #newUserModal select.form-input,
    #participantModal input,
    #newUserModal input {
        background: #ffffff !important;
        color: #0f172a !important;
        border: 1px solid var(--glass-border) !important;
    }
</style>

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
                    <code style="background: #eff6ff; padding: 4px 8px; border-radius: 4px; color: var(--accent);">{{ $session->code }}</code>
                    <span class="badge {{ $session->is_active ? 'active' : '' }}">
                        {{ $session->is_active ? 'Aktif' : 'Non-Aktif' }}
                    </span>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">Target Soal</div>
                <div style="font-size: 2rem; font-weight: 700; font-family: 'Outfit', sans-serif; margin-bottom: 12px;">{{ $session->sessionCategories->sum('total_questions') }} <span style="font-size: 1rem; color: var(--text-secondary); font-weight: 400;">butir</span></div>
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
                <div class="label"><i class="fas fa-hourglass-half"></i> Total Durasi</div>
                <div style="font-size: 1rem; margin-top: 8px;">{{ $session->sessionCategories->sum('duration') }} Menit</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">Pengerjaan</div>
            </div>
            <div class="stat-card glass" style="padding: 16px;">
                <div class="label"><i class="fas fa-trophy"></i> Total Skor Maksimal</div>
                <div style="font-size: 1rem; margin-top: 8px;">Raw: {{ $session->sessionCategories->sum('max_score_raw') }}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">IRT: {{ $session->sessionCategories->sum('max_score_irt') }}</div>
            </div>
        </div>
    </div>

    <!-- Category Distribution -->
    <div class="glass animate-fade-in" style="padding: 32px;">
        <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; margin-bottom: 20px;">Konfigurasi Mata Pelajaran</h3>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            @foreach($session->sessionCategories as $sc)
            <div class="session-detail-light-panel" style="border-radius: 12px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                    <div>
                        <h4 style="font-size: 1.1rem; color: var(--accent); margin-bottom: 4px;">{{ data_get($sc->category, 'name') }}</h4>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; gap: 16px;">
                            <span><i class="fas fa-hourglass-half"></i> {{ $sc->duration }} Menit</span>
                            <span><i class="fas fa-list-ol"></i> {{ $sc->total_questions }} Soal</span>
                            <span><i class="fas fa-bullseye"></i> Raw: {{ $sc->max_score_raw }} | IRT: {{ $sc->max_score_irt }}</span>
                        </div>
                    </div>
                </div>
                
                @if($sc->subCategories->count() > 0)
                <div class="session-detail-soft-panel" style="border-radius: 8px; padding: 16px;">
                    <h5 style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 12px;">Persentase Sub Mata Pelajaran:</h5>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
                        @foreach($sc->subCategories as $subCat)
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                                <span>{{ data_get($subCat->subCategory, 'name') }}</span>
                                <span style="font-weight: 600; color: #10b981;">{{ $subCat->percentage }}%</span>
                            </div>
                            <div style="height: 4px; background: #eff6ff; border-radius: 2px; overflow: hidden;">
                                <div style="height: 100%; width: {{ $subCat->percentage }}%; background: #10b981;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
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

            <button class="btn-primary" onclick="openNewUserModal()" style="height: 38px; font-size: 0.85rem; background: transparent; border: 1px solid var(--accent); color: var(--accent);">
                <i class="fas fa-plus"></i> Buat Peserta Baru
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
                    <th style="width: 150px;">PRIVILEGE</th>
                    <th>SEKOLAH</th>
                    <th style="width: 80px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody id="participantTableBody">
                @php $participants = $session->participants; @endphp
                @forelse($participants as $index => $participant)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><code style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 8px; border-radius: 4px; font-weight: 700; letter-spacing: 1px;">{{ data_get($participant, 'access_code') }}</code></td>
                    <td>
                        <div style="font-weight: 600;">{{ data_get($participant, 'name') }}</div>
                        @php
                            $roleName = optional($participant->user)->role ?? 'basic';
                            $bgRole = 'rgba(59, 130, 246, 0.1)';
                            $colorRole = '#3b82f6';
                            if($roleName === 'superadmin') { $bgRole = 'rgba(234, 179, 8, 0.1)'; $colorRole = '#eab308'; }
                            elseif($roleName === 'admin_sesi') { $bgRole = 'rgba(139, 92, 246, 0.1)'; $colorRole = '#8b5cf6'; }
                        @endphp
                        <span class="badge" style="font-size: 0.65rem; background: {{ $bgRole }}; color: {{ $colorRole }}; margin-top: 4px;">
                            {{ ucfirst(str_replace('_', ' ', $roleName)) }}
                        </span>
                    </td>
                    <td>{{ data_get($participant, 'whatsapp') }}</td>
                    <td>
                        <select onchange="updateParticipantPrivilege({{ $participant->id }}, this.value)" class="form-input" style="padding: 4px 8px; font-size: 0.8rem; border-radius: 6px; margin-bottom: 0; background-color: {{ $participant->privilege === 'premium' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(59, 130, 246, 0.1)' }}; color: {{ $participant->privilege === 'premium' ? '#eab308' : '#3b82f6' }}; font-weight: 600;">
                            <option value="general" {{ $participant->privilege === 'general' ? 'selected' : '' }} style="background: #ffffff; color: #0f172a;">General</option>
                            <option value="premium" {{ $participant->privilege === 'premium' ? 'selected' : '' }} style="background: #ffffff; color: #0f172a;">Premium</option>
                        </select>
                    </td>
                    <td style="color: var(--text-secondary); font-size: 0.85rem;">{{ data_get($participant, 'address') ?? '-' }}</td>
                    <td style="text-align: center;">
                        <button class="btn-icon delete" onclick="deleteParticipant({{ $participant->id }})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-secondary);">
                        <i class="fas fa-users-slash" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.2;"></i>
                        <p>Belum ada peserta terdaftar untuk sesi ini.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 20px;">
        <div style="font-size: 0.85rem; color: var(--text-secondary);">
            Menampilkan <span id="startIdx">0</span> - <span id="endIdx">0</span> dari <span id="totalParticipantsCount">{{ $session->participants->count() }}</span> peserta
        </div>
        <div style="display: flex; gap: 8px;" id="paginationControls"></div>
    </div>
</div>

<!-- Participant Modal -->
<div class="modal-overlay" id="participantModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 700px; width: 90%;">
        <div class="modal-header">
            <h3>Pilih Peserta dari Database</h3>
            <button class="close-modal" onclick="closeParticipantModal()">&times;</button>
        </div>
        <div style="margin-bottom: 20px;">
            <div style="position: relative; margin-bottom: 16px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" id="userSearchInput" class="form-input" placeholder="Cari nama atau email peserta..." style="padding-left: 44px; margin-bottom: 0;">
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0 8px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" id="selectAllUsers" onchange="toggleSelectAllUsers(this)"> 
                    <span>Pilih Semua yang Tampil</span>
                </label>
                <div id="selectedCount" style="font-size: 0.85rem; color: var(--accent); font-weight: 600;">0 peserta dipilih</div>
            </div>
        </div>

        <form id="participantForm">
            @csrf
            <input type="hidden" name="exam_session_id" value="{{ $session->id }}">
            
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--glass-border); border-radius: 12px; margin-bottom: 24px;">
                <div class="table-responsive">
                <table class="data-table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">PILIH</th>
                            <th>NAMA</th>
                            <th>EMAIL</th>
                            <th style="width: 100px;">ROLE</th>
                        </tr>
                    </thead>
                    <tbody id="userListBody">
                        @php 
                            $existingUserIds = $session->participants->pluck('user_id')->toArray();
                        @endphp
                        @foreach($availableParticipants as $user)
                        @if(!in_array($user->id, $existingUserIds))
                        <tr class="user-row" data-search="{{ strtolower($user->name . ' ' . $user->email) }}">
                            <td style="text-align: center;">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" onchange="updateSelectedCount()">
                            </td>
                            <td><div style="font-weight: 600;">{{ $user->name }}</div></td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @php
                                    $bgRole = 'rgba(59, 130, 246, 0.1)';
                                    $colorRole = '#3b82f6';
                                    if($user->role === 'premium') { $bgRole = 'rgba(234, 179, 8, 0.1)'; $colorRole = '#eab308'; }
                                    elseif($user->role === 'admin_sesi') { $bgRole = 'rgba(139, 92, 246, 0.1)'; $colorRole = '#8b5cf6'; }
                                @endphp
                                <span class="badge" style="font-size: 0.7rem; background: {{ $bgRole }}; color: {{ $colorRole }};">
                                    {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                </span>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-primary" style="background: #ffffff; border: 1px solid var(--glass-border); color: var(--text-primary);" onclick="closeParticipantModal()">Batal</button>
                <button type="submit" class="btn-primary" id="submitBtn" disabled>Tambahkan Terpilih</button>
            </div>
        </form>
    </div>
</div>

<!-- New User Modal -->
<div class="modal-overlay" id="newUserModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 500px; width: 90%;">
        <div class="modal-header">
            <h3>Buat Peserta Baru</h3>
            <button class="close-modal" onclick="closeNewUserModal()">&times;</button>
        </div>
        <form id="newUserForm" onsubmit="submitNewParticipant(event)">
            @csrf
            <input type="hidden" name="exam_session_id" value="{{ $session->id }}">
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" class="form-input" required placeholder="Masukkan nama peserta">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-input" required placeholder="email@contoh.com">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-input" required minlength="6" placeholder="Minimal 6 karakter">
            </div>
            
            <div class="form-group">
                <label>No. WhatsApp</label>
                <input type="text" name="whatsapp" class="form-input" placeholder="Misal: 08123456789">
            </div>
            
            <div class="form-group">
                <label>Sekolah</label>
                <textarea name="address" class="form-input" rows="2" placeholder="Masukkan asal sekolah (opsional)"></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="btn-primary" style="background: #ffffff; border: 1px solid var(--glass-border); color: var(--text-primary);" onclick="closeNewUserModal()">Batal</button>
                <button type="submit" class="btn-primary" id="submitNewUserBtn">Buat & Tambahkan</button>
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

    <!-- Upload Discussion PDF -->
    <div class="session-detail-soft-panel" style="border-style: dashed !important; border-radius: 16px; padding: 24px; margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between; gap: 24px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 48px; height: 48px; background: rgba(239, 68, 68, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.5rem;">
                <i class="fas fa-file-pdf"></i>
            </div>
            <div>
                <h4 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">File Pembahasan (PDF)</h4>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">
                    @if($session->discussion_pdf)
                        <span style="color: #10b981;"><i class="fas fa-check-circle"></i> File terunggah: {{ basename($session->discussion_pdf) }}</span>
                    @else
                        Belum ada file pembahasan yang diunggah untuk sesi ini.
                    @endif
                </p>
            </div>
        </div>
        <form id="uploadDiscussionForm" style="display: flex; gap: 12px; align-items: center;">
            @csrf
            <input type="file" name="discussion_pdf" id="discussion_pdf" accept=".pdf" style="display: none;" onchange="submitDiscussionPdf()">
            <button type="button" class="btn-primary" onclick="document.getElementById('discussion_pdf').click()" style="background: #ffffff; border: 1px solid var(--glass-border); color: var(--text-primary); height: 40px; font-size: 0.85rem;">
                <i class="fas fa-upload"></i> {{ $session->discussion_pdf ? 'Ganti File' : 'Pilih PDF' }}
            </button>
        </form>
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
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #94a3b8, #475569); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0f172a; box-shadow: 0 4px 10px rgba(71, 85, 105, 0.2);">
                                2
                            </div>
                        @elseif($loop->index == 2)
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #92400e, #451a03); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0f172a; box-shadow: 0 4px 10px rgba(69, 26, 3, 0.2);">
                                3
                            </div>
                        @else
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #eff6ff; border: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--text-secondary); font-size: 0.85rem;">
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
    <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 20px;">
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
    let rawParticipants = @json($session->participants->load('user'));
    
    // Filter to keep only the latest participant registration per user
    let latestParticipantsMap = new Map();
    rawParticipants.forEach(p => {
        let userId = p.user_id;
        let key = userId || p.name; // fallback to name
        if (!latestParticipantsMap.has(key) || latestParticipantsMap.get(key).id < p.id) {
            latestParticipantsMap.set(key, p);
        }
    });
    
    let allParticipants = Array.from(latestParticipantsMap.values());
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

        body.innerHTML = pageItems.length ? pageItems.map((p, i) => {
            let badgeBg = 'rgba(59, 130, 246, 0.1)';
            let badgeColor = '#3b82f6';
            let roleName = p.user?.role || 'basic';
            if(roleName === 'superadmin') { badgeBg = 'rgba(234, 179, 8, 0.1)'; badgeColor = '#eab308'; }
            else if(roleName === 'admin_sesi') { badgeBg = 'rgba(139, 92, 246, 0.1)'; badgeColor = '#8b5cf6'; }
            
            return `
            <tr>
                <td>${start + i + 1}</td>
                <td><code style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 8px; border-radius: 4px; font-weight: 700; letter-spacing: 1px;">${p.access_code}</code></td>
                <td>
                    <div style="font-weight: 600;">${p.name}</div>
                    <span class="badge" style="font-size: 0.65rem; background: ${badgeBg}; color: ${badgeColor}; margin-top: 4px;">
                        ${roleName.charAt(0).toUpperCase() + roleName.slice(1).replace('_', ' ')}
                    </span>
                </td>
                <td>${p.whatsapp || '-'}</td>
                <td>
                    <select onchange="updateParticipantPrivilege(${p.id}, this.value)" class="form-input" style="padding: 4px 8px; font-size: 0.8rem; border-radius: 6px; margin-bottom: 0; background-color: ${p.privilege === 'premium' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(59, 130, 246, 0.1)'}; color: ${p.privilege === 'premium' ? '#eab308' : '#3b82f6'}; font-weight: 600;">
                        <option value="general" ${p.privilege === 'general' ? 'selected' : ''} style="background: #ffffff; color: #0f172a;">General</option>
                        <option value="premium" ${p.privilege === 'premium' ? 'selected' : ''} style="background: #ffffff; color: #0f172a;">Premium</option>
                    </select>
                </td>
                <td style="color: var(--text-secondary); font-size: 0.85rem;">${p.address || '-'}</td>
                <td style="text-align: center;">
                    <button class="btn-icon delete" onclick="deleteParticipant(${p.id})" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        }).join('') : `
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-secondary);">
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
            btn.style.background = currentPage === i ? 'var(--accent)' : '#ffffff';
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
    let rawIRTResults = @json($session->results->load('participant'));
    
    // Filter to keep only the latest result per user (based on highest participant id)
    let latestResultsMap = new Map();
    rawIRTResults.forEach(r => {
        if (!r.participant) return;
        let userId = r.participant.user_id;
        let key = userId || r.participant.name; // fallback to name if user_id is somehow null
        
        if (!latestResultsMap.has(key) || latestResultsMap.get(key).participant.id < r.participant.id) {
            latestResultsMap.set(key, r);
        }
    });
    
    let allIRTResults = Array.from(latestResultsMap.values());
    
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
                rankHtml = `<div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #94a3b8, #475569); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0f172a; box-shadow: 0 4px 10px rgba(71, 85, 105, 0.2);">2</div>`;
            } else if (rankIdx === 2) {
                rankHtml = `<div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #92400e, #451a03); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0f172a; box-shadow: 0 4px 10px rgba(69, 26, 3, 0.2);">3</div>`;
            } else {
                rankHtml = `<div style="width: 32px; height: 32px; border-radius: 50%; background: #eff6ff; border: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--text-secondary); font-size: 0.85rem;">${rankIdx + 1}</div>`;
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
            btn.style.background = irtCurrentPage === i ? 'var(--accent)' : '#ffffff';
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

    // User Modal Search & Selection
    document.getElementById('userSearchInput').oninput = function(e) {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#userListBody .user-row');
        rows.forEach(row => {
            const text = row.getAttribute('data-search');
            row.style.display = text.includes(term) ? '' : 'none';
        });
    };

    function toggleSelectAllUsers(checkbox) {
        const rows = document.querySelectorAll('#userListBody .user-row');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cb = row.querySelector('input[type="checkbox"]');
                cb.checked = checkbox.checked;
            }
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll('#userListBody input[name="user_ids[]"]:checked').length;
        document.getElementById('selectedCount').innerText = `${count} peserta dipilih`;
        document.getElementById('submitBtn').disabled = count === 0;
    }

    function openParticipantModal() {
        pForm.reset();
        updateSelectedCount();
        pModal.classList.add('active');
    }

    function closeParticipantModal() {
        pModal.classList.remove('active');
    }



    pForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch("{{ route('admin.session-participants.store') }}", {
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
                showToast(data.message);
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

    // New User Modal Logic
    const nuModal = document.getElementById('newUserModal');
    
    function openNewUserModal() {
        document.getElementById('newUserForm').reset();
        nuModal.classList.add('active');
    }

    function closeNewUserModal() {
        nuModal.classList.remove('active');
    }

    function submitNewParticipant(e) {
        e.preventDefault();
        const form = document.getElementById('newUserForm');
        const formData = new FormData(form);
        const btn = document.getElementById('submitNewUserBtn');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        
        fetch("{{ route('admin.session-participants.store-new') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (data.status === 'success') {
                showToast(data.message);
                
                // Add the new participant directly to allParticipants array
                allParticipants.unshift(data.data);
                filteredParticipants = [...allParticipants];
                currentPage = 1;
                renderParticipants();
                
                // Update total count
                const totalCountEl = document.getElementById('totalParticipantsCount');
                if(totalCountEl) totalCountEl.innerText = allParticipants.length;
                
                closeNewUserModal();
            } else {
                showToast(data.message || 'Gagal membuat peserta baru', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error(err);
            showToast('Terjadi kesalahan sistem', 'error');
        });
    }

    function deleteParticipant(id) {
        customConfirm('Hapus peserta ini dari sesi?', function() {
            fetch(`/admin/session-participants/${id}`, {
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

    function submitDiscussionPdf() {
        const fileInput = document.getElementById('discussion_pdf');
        if (!fileInput.files.length) return;

        const formData = new FormData();
        formData.append('discussion_pdf', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        showToast('Sedang mengunggah file...', 'info');

        fetch("{{ route('admin.sessions.upload-discussion', $session->id) }}", {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Gagal mengunggah file', 'error');
            }
        })
        .catch(err => {
            console.error(err);
        });
    }

    function updateParticipantPrivilege(participantId, privilege) {
        fetch(`/admin/session-participants/${participantId}/privilege`, {
            method: 'PATCH',
            body: JSON.stringify({ privilege: privilege }),
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                // Update select color
                const selectElement = document.querySelector(`select[onchange="updateParticipantPrivilege(${participantId}, this.value)"]`);
                if (privilege === 'premium') {
                    selectElement.style.backgroundColor = 'rgba(234, 179, 8, 0.1)';
                    selectElement.style.color = '#eab308';
                } else {
                    selectElement.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                    selectElement.style.color = '#3b82f6';
                }
            } else {
                showToast(data.message || 'Gagal mengupdate privilege', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan sistem', 'error');
        });
    }
</script>
@endpush
