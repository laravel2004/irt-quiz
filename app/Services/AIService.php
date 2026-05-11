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
        $irtScore = $data['irt_score'];
        $maxIrt = $data['max_irt'];
        $categoryStats = $data['category_stats'];

        $categoryLines = "";
        foreach ($categoryStats as $nameCat => $stat) {
            $categoryLines .= "- $nameCat: {$stat['correct']} benar dari {$stat['total']} soal\n";
        }

        return "Sebagai konsultan pendidikan, berikan analisis hasil tryout untuk: $name.
        Sesi: $session. 
        
        Data Performa per Bidang/Pelajaran:
        $categoryLines
        
        Ringkasan Keseluruhan:
        - Total Jawaban Benar: $correct
        - Total Jawaban Salah: $incorrect
        - Total Jawaban Kosong: $blank
        - Skor IRT Akhir: $irtScore (dari skala maksimal $maxIrt)
        
        Instruksi Analisis:
        1. **Kelebihan**: Identifikasi bidang/pelajaran mana yang paling dikuasai. Mengapa skor IRT tersebut menunjukkan potensi tinggi?
        2. **Kekurangan**: Analisis bidang yang masih lemah. Apakah karena banyak salah atau banyak kosong? Hubungkan dengan strategi manajemen waktu.
        3. **Rekomendasi (Langkah Selanjutnya)**: Berikan saran belajar yang taktis dan spesifik per bidang pelajaran untuk menaikkan skor IRT pada sesi berikutnya.
        
        Berikan jawaban Anda dalam format JSON (keys: kelebihan, kekurangan, rekomendasi) dengan narasi yang mendalam dan konsultatif.";
    }
}
