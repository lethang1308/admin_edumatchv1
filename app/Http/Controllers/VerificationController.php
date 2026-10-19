<?php

namespace App\Http\Controllers;

use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])], 'type' => ['nullable', Rule::in(['teacher', 'center'])], 'q' => ['nullable', 'string', 'max:100']]);
        $query = VerificationRequest::with(['teacher.teacherProfile', 'center', 'submittedBy', 'reviewer'])->withCount('verificationDocuments');
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['type'])) {
            $query->whereNotNull($data['type'] === 'teacher' ? 'teacher_user_id' : 'center_id');
        }
        if (! empty($data['q'])) {
            $term = '%'.$data['q'].'%';
            $query->where(fn ($q) => $q->whereHas('submittedBy', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term))
                ->orWhereHas('center', fn ($c) => $c->where('name', 'like', $term)->orWhere('hotline', 'like', $term)->orWhere('contact_email', 'like', $term)));
        }
        $records = $query->latest('submitted_at')->latest('id')->paginate(15)->withQueryString();
        $pending = VerificationRequest::where('status', 'pending');
        $counts = ['all' => $pending->clone()->count(), 'teacher' => $pending->clone()->whereNotNull('teacher_user_id')->count(), 'center' => $pending->clone()->whereNotNull('center_id')->count()];

        return view('admin.verifications.index', compact('records', 'counts'));
    }

    public function teachers(Request $request)
    {
        $request->merge(['type' => 'teacher']);

        return $this->index($request);
    }

    public function centers(Request $request)
    {
        $request->merge(['type' => 'center']);

        return $this->index($request);
    }

    public function show(VerificationRequest $verification)
    {
        $verification->load(['teacher.teacherProfile', 'center', 'submittedBy', 'verificationDocuments', 'reviewer']);

        return view('admin.verifications.show', compact('verification'));
    }

    public function document(VerificationDocument $document)
    {
        return app(\App\Http\Controllers\Api\VerificationController::class)->fileResponse($document);
    }

    public function approve(Request $request, VerificationRequest $verification, VerificationService $service)
    {
        $service->review($verification, $request->user(), true);

        return back()->with('success', 'Đã phê duyệt hồ sơ và cấp huy hiệu xác minh.');
    }

    public function reject(Request $request, VerificationRequest $verification, VerificationService $service)
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $service->review($verification, $request->user(), false, $data['rejection_reason']);

        return back()->with('success', 'Đã từ chối hồ sơ và gửi lý do cho người nộp.');
    }
}
