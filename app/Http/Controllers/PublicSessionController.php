<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicSessionController extends Controller
{
    use ResponseTrait;

    public function registration($code)
    {
        $session = ExamSession::where('code', $code)->where('is_active', true)->firstOrFail();
        return view('public.session_registration', compact('session'));
    }

    public function register(Request $request, $code): JsonResponse
    {
        $session = ExamSession::where('code', $code)->where('is_active', true)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'whatsapp' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        $user = \App\Models\User::updateOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'whatsapp' => $request->whatsapp,
                'address' => $request->address,
                'role' => 'basic',
                'password' => $request->password,
            ]
        );

        $existing = ExamSessionParticipant::where('exam_session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Sudah terdaftar.',
                'participant' => $existing,
                'access_code' => $existing->access_code,
                'join_url' => route('participant.dashboard'),
            ]);
        }

        $accessCode = $this->generateUniqueCode();

        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => $user->id,
            'name' => $request->name,
            'whatsapp' => $request->whatsapp,
            'address' => $request->address,
            'access_code' => $accessCode
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil.',
            'participant' => $participant,
            'access_code' => $participant->access_code,
            'join_url' => route('participant.dashboard'),
        ]);
    }


    public function registerPremium(Request $request, $code): JsonResponse
    {
        $session = ExamSession::where('code', $code)->where('is_active', true)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'whatsapp' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        $user = \App\Models\User::updateOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'whatsapp' => $request->whatsapp,
                'address' => $request->address,
                'role' => 'basic',
                'password' => $request->password,
            ]
        );

        $existing = ExamSessionParticipant::where('exam_session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->update(['privilege' => 'premium']);

            return response()->json([
                'message' => 'Sudah terdaftar sebagai peserta premium.',
                'participant' => $existing,
                'access_code' => $existing->access_code,
                'join_url' => route('participant.dashboard'),
            ]);
        }

        $accessCode = $this->generateUniqueCode();

        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => $user->id,
            'name' => $request->name,
            'whatsapp' => $request->whatsapp,
            'address' => $request->address,
            'privilege' => 'premium',
            'access_code' => $accessCode,
        ]);

        return response()->json([
            'message' => 'Pendaftaran premium berhasil.',
            'participant' => $participant,
            'access_code' => $participant->access_code,
            'join_url' => route('participant.dashboard'),
        ]);
    }

    public function success($code)
    {
        $session = ExamSession::where('code', $code)->firstOrFail();
        $participant = session('participant');
        
        if (!$participant) return redirect()->route('public.session.registration', $code);

        return view('public.registration_success', compact('session', 'participant'));
    }

    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (ExamSessionParticipant::where('access_code', $code)->exists());

        return $code;
    }
}
