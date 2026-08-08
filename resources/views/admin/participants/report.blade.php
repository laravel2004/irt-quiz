@extends('layouts.admin')

@section('title', 'Raport: ' . $reportCard->user->name)
@section('header_title', 'Raport Peserta')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">
                Raport: {{ $reportCard->user->name }}
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Digenerate pada {{ $reportCard->updated_at->format('d M Y, H:i') }} WIB
            </p>
        </div>
        <a href="{{ route('admin.participants.index') }}" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary); text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    @php $reportData = $reportCard->report_data; @endphp

    @if(!$reportData || count($reportData) === 0)
        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
            Tidak ada data jawaban untuk ditampilkan.
        </div>
    @else
        @foreach($reportData as $category)
        <div style="margin-bottom: 32px; border: 1px solid var(--glass-border); border-radius: 16px; overflow: hidden;">
            <!-- Header Mata Pelajaran -->
            <div style="background: #f1f5f9; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="font-family: 'Outfit', sans-serif; margin: 0;">
                    {{ $category['category_name'] }}
                </h4>
                <div style="display: flex; gap: 16px; font-size: 0.85rem;">
                    <span><strong>Total:</strong> {{ $category['total_soal'] }} soal</span>
                    <span style="color: #10b981;"><strong>Benar:</strong> {{ $category['total_benar'] }}</span>
                    <span style="color: #ef4444;"><strong>Salah:</strong> {{ $category['total_salah'] }}</span>
                </div>
            </div>

            <!-- Detail Sub Mata Pelajaran -->
            <div style="padding: 0;">
                <table class="data-table" style="margin: 0; border: none;">
                    <thead>
                        <tr>
                            <th>Sub Mata Pelajaran</th>
                            <th style="text-align: center; width: 100px;">Total Soal</th>
                            <th style="text-align: center; width: 100px;">Benar</th>
                            <th style="text-align: center; width: 100px;">Salah</th>
                            <th style="text-align: center; width: 120px;">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category['sub_categories'] as $sub)
                        @php
                            $persen = $sub['total_soal'] > 0 ? round(($sub['total_benar'] / $sub['total_soal']) * 100) : 0;
                            $barColor = $persen >= 70 ? '#10b981' : ($persen >= 40 ? '#eab308' : '#ef4444');
                        @endphp
                        <tr>
                            <td>{{ $sub['sub_category_name'] }}</td>
                            <td style="text-align: center;">{{ $sub['total_soal'] }}</td>
                            <td style="text-align: center; color: #10b981; font-weight: 600;">{{ $sub['total_benar'] }}</td>
                            <td style="text-align: center; color: #ef4444; font-weight: 600;">{{ $sub['total_salah'] }}</td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                        <div style="width: {{ $persen }}%; height: 100%; background: {{ $barColor }}; border-radius: 4px;"></div>
                                    </div>
                                    <span style="font-weight: 600; font-size: 0.85rem; color: {{ $barColor }};">{{ $persen }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    @endif
</div>
@endsection
