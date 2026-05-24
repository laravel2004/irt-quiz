<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->model = env('OPENAI_MODEL', 'gpt-3.5-turbo');
        $this->baseUrl = env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
    }

    public function generateAnalysis($resultData)
    {
        try {
            $prompt = $this->buildPrompt($resultData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah Konsultan Pendidikan Senior (Senior Educational Consultant) yang ahli dalam psikometri dan strategi ujian. Tugas Anda adalah memberikan analisis strategis, motivatif, dan sangat spesifik berdasarkan data tryout siswa. Gunakan bahasa yang profesional namun tetap mendukung. Output harus dalam format JSON murni dengan key: kelebihan, kekurangan, dan rekomendasi.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.8,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                $content = str_replace(['```json', '```'], '', $content);
                return json_decode(trim($content), true);
            }

            Log::error('OpenAI Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage());
            return null;
        }
    }

    protected function buildPrompt($data)
    {
        $name = $data['participant_name'];
        $session = $data['session_name'];
        $correct = $data['correct'];
        $incorrect = $data['incorrect'];
        $blank = $data['blank'];
        $totalScore = $data['total_score'] ?? 'Belum tersedia';
        $categoryStats = $data['category_stats'];

        $categoryLines = "";
        foreach ($categoryStats as $nameCat => $stat) {
            $categoryLines .= "- $nameCat: {$stat['correct']} benar dari {$stat['total']} soal\n";
        }

        return "Sebagai konsultan pendidikan, berikan analisis hasil ujian untuk: $name.
        Sesi: $session. 
        
        Data Performa per Bidang/Pelajaran:
        $categoryLines
        
        Ringkasan Keseluruhan:
        - Total Jawaban Benar: $correct
        - Total Jawaban Salah: $incorrect
        - Total Jawaban Kosong: $blank
        - Skor Total: $totalScore
        
        Instruksi Analisis:
        1. **Kelebihan**: Identifikasi bidang/pelajaran mana yang paling dikuasai.
        2. **Kekurangan**: Analisis bidang yang masih lemah. Apakah karena banyak salah atau banyak kosong? Hubungkan dengan strategi manajemen waktu.
        3. **Rekomendasi (Langkah Selanjutnya)**: Berikan saran belajar yang taktis dan spesifik per bidang pelajaran.
        
        Berikan jawaban Anda dalam format JSON (keys: kelebihan, kekurangan, rekomendasi) dengan narasi yang mendalam dan konsultatif.";
    }

    public function generateAggregateAnalysis($data)
    {
        try {
            $prompt = $this->buildAggregatePrompt($data);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah AI Konsultan Pendidikan Senior. Tugas Anda menganalisis perkembangan hasil tryout siswa dari beberapa kali percobaan pada sesi yang sama. Output harus berformat JSON murni dengan key: analisis_progres, pola_kekurangan, strategi_lanjutan.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.8,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                $content = str_replace(['```json', '```'], '', $content);
                return json_decode(trim($content), true);
            }

            Log::error('OpenAI Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage());
            return null;
        }
    }

    protected function buildAggregatePrompt($data)
    {
        $name = $data['participant_name'];
        $session = $data['session_name'];
        $attempts = $data['attempts'];

        $attemptsStr = "";
        foreach ($attempts as $a) {
            $attemptsStr .= "Percobaan ke-{$a['attempt_number']}: Benar {$a['total_correct']}, Salah {$a['total_incorrect']}, Kosong {$a['total_blank']}, Skor Mentah {$a['raw_score']}, Skor IRT {$a['irt_score']}\n";
        }

        return "Analisis perkembangan siswa bernama $name pada sesi ujian: $session.
        
        Riwayat Percobaan:
        $attemptsStr
        
        Instruksi Analisis:
        1. **analisis_progres**: Jelaskan bagaimana tren nilai (IRT maupun Raw) dari percobaan awal ke akhir. Apakah ada peningkatan konsisten atau penurunan?
        2. **pola_kekurangan**: Berdasarkan tren jawaban salah/kosong, apa pola kesalahan yang masih menetap dan perlu segera diatasi?
        3. **strategi_lanjutan**: Berikan saran belajar yang komprehensif berdasarkan seluruh riwayat di atas.
        
        Berikan jawaban dalam format JSON (keys: analisis_progres, pola_kekurangan, strategi_lanjutan).";
    }
}
