<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use Illuminate\Http\JsonResponse;

class PublicExamSessionController extends Controller
{
    public function index(): JsonResponse
    {
        $sessions = ExamSession::query()
            ->with('sessionCategories.category:id,name,slug')
            ->where('is_active', true)
            ->latest('start_date')
            ->get()
            ->map(function (ExamSession $session): array {
                $subjects = $session->sessionCategories
                    ->pluck('category.name')
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'external_id' => (string) $session->id,
                    'name' => $session->name,
                    'code' => $session->code,
                    'slug' => $session->code ? str($session->name.' '.$session->code)->slug() : str($session->name)->slug(),
                    'subject' => $subjects->join(', '),
                    'subjects' => $subjects->all(),
                    'starts_at' => trim($session->start_date.' '.$session->start_time),
                    'ends_at' => trim($session->end_date.' '.$session->end_time),
                    'updated_at' => optional($session->updated_at)?->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $sessions,
        ]);
    }
}
