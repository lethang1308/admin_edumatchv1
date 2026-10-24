<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitVerificationRequest;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['teacher', 'center'], true), 403);
        $records = VerificationRequest::with('verificationDocuments')->where('submitted_by_user_id', $request->user()->id)
            ->latest('id')->paginate(10);
        $records->getCollection()->transform(fn ($record) => $this->serialize($record));
        $profile = $request->user()->role === 'teacher' ? $request->user()->teacherProfile : $request->user()->center;

        return response()->json(['requests' => $records, 'verification_status' => $profile?->verification_status ?? 'unverified',
            'user' => $request->user()->load(['studentProfile', 'teacherProfile', 'center']),
            'document_types' => config('verification.'.$request->user()->role.'_types'), 'max_file_kb' => config('verification.max_file_kb')]);
    }

    public function store(SubmitVerificationRequest $request, VerificationService $service)
    {
        $record = $service->submit($request->user(), $request->validated()['documents'], $request->validated('center_details'));

        return response()->json(['message' => 'Đã gửi hồ sơ xác minh. Vui lòng chờ quản trị viên xét duyệt.', 'request' => $this->serialize($record),
            'user' => $request->user()->fresh()->load(['studentProfile', 'teacherProfile', 'center'])], 201);
    }

    public function document(Request $request, VerificationDocument $document)
    {
        abort_unless($document->request->submitted_by_user_id === $request->user()->id, 403);

        return $this->fileResponse($document);
    }

    public function fileResponse(VerificationDocument $document)
    {
        $disk = Storage::disk('verification');
        abort_unless($disk->exists($document->file_path), 404);

        return response()->file($disk->path($document->file_path), [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="document-'.$document->id.'.'.($document->mime_type === 'application/pdf' ? 'pdf' : ($document->mime_type === 'image/png' ? 'png' : 'jpg')).'"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox; frame-ancestors 'self'",
        ]);
    }

    private function serialize(VerificationRequest $record): array
    {
        return [
            'id' => $record->id, 'status' => $record->status, 'submitted_at' => $record->submitted_at,
            'reviewed_at' => $record->reviewed_at, 'rejection_reason' => $record->rejection_reason,
            'documents' => $record->verificationDocuments->map(fn ($doc) => [
                'id' => $doc->id, 'document_type' => $doc->document_type, 'document_name' => $doc->document_name,
                'issuing_authority' => $doc->issuing_authority, 'issued_year' => $doc->issued_year,
                'original_name' => $doc->original_name, 'mime_type' => $doc->mime_type, 'status' => $doc->status,
            ])->all(),
        ];
    }
}
