<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSessionParticipant;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExamSessionParticipantController extends Controller
{
    use ResponseTrait;

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_session_id' => 'required|exists:exam_sessions,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) return $this->validationResponse($validator->errors());

        $users = \App\Models\User::whereIn('id', $request->user_ids)->get();
        $createdCount = 0;

        foreach ($users as $user) {
            // Check if already registered for this session
            $exists = ExamSessionParticipant::where('exam_session_id', $request->exam_session_id)
                ->where('user_id', $user->id)
                ->exists();
            
            if (!$exists) {
                ExamSessionParticipant::create([
                    'exam_session_id' => $request->exam_session_id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'whatsapp' => $user->phone ?? '-',
                    'address' => $user->address,
                    'access_code' => $this->generateUniqueCode()
                ]);
                $createdCount++;
            }
        }

        return $this->successResponse(null, "$createdCount peserta berhasil ditambahkan", 201);
    }

    public function destroy($id)
    {
        $participant = ExamSessionParticipant::find($id);
        if (!$participant) return $this->errorResponse('Peserta tidak ditemukan', 404);
        
        $participant->delete();
        return $this->successResponse(null, 'Peserta berhasil dihapus');
    }

    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (ExamSessionParticipant::where('access_code', $code)->exists());

        return $code;
    }
}
