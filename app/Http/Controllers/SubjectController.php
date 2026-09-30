<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Subject;
use App\Models\SubjectContent;
use App\Models\User;
use App\Models\Classes;
use App\Models\Schedule;
use Exception;

class SubjectController extends Controller
{
    /**
     * Lấy thông tin Giảng viên đang làm việc trong phiên hiện tại
     */
    protected function getCurrentTeacher(): User
    {
        $teacherId = session('active_teacher_id');
        $teacher = null;

        if ($teacherId) {
            $teacher = User::find($teacherId);
        }

        if (!$teacher) {
            $teacher = User::first() ?? User::create([
                'name' => 'ThS. Hoàng Văn Nhân',
                'email' => 'nhan.hv@university.edu.vn',
                'password' => bcrypt('password123'),
            ]);
            session(['active_teacher_id' => $teacher->id]);
        }

        return $teacher;
    }

    /**
     * Danh sách tất cả các Môn học đã thêm trên hệ thống
     */
    public function index(Request $request)
    {
        $currentTeacher = $this->getCurrentTeacher();
        $allTeachers = User::all();

        $search = $request->input('search');
        $typeFilter = $request->input('type');

        $query = Subject::with(['teacher', 'contents'])
            ->withCount(['contents', 'schedules']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($typeFilter) {
            $query->where('subject_type', $typeFilter);
        }

        $subjects = $query->orderBy('code')->get();

        // Thống kê tổng hợp
        $totalSubjects = Subject::count();
        $integratedCount = Subject::where('subject_type', 'integrated')->count();
        $theoryCount = Subject::where('subject_type', 'theory')->count();
        $practiceCount = Subject::where('subject_type', 'practice')->count();

        return view('subjects.index', compact(
            'subjects',
            'currentTeacher',
            'allTeachers',
            'search',
            'typeFilter',
            'totalSubjects',
            'integratedCount',
            'theoryCount',
            'practiceCount'
        ));
    }

    /**
     * Thêm mới một Môn học
     */
    public function store(Request $request)
    {
        $currentTeacher = $this->getCurrentTeacher();

        $validated = $request->validate([
            'code'           => 'required|string|max:50|unique:subjects,code',
            'name'           => 'required|string|max:255',
            'total_sessions' => 'required|integer|min:1|max:100',
            'subject_type'   => 'required|in:integrated,theory,practice',
        ], [
            'code.unique' => 'Mã môn học này đã tồn tại trên hệ thống.',
            'code.required' => 'Vui lòng nhập mã môn học.',
            'name.required' => 'Vui lòng nhập tên môn học.',
        ]);

        DB::beginTransaction();
        try {
            $subject = Subject::create([
                'user_id'        => $currentTeacher->id,
                'code'           => trim(mb_strtoupper($validated['code'], 'UTF-8')),
                'name'           => trim($validated['name']),
                'total_sessions' => $validated['total_sessions'],
                'subject_type'   => $validated['subject_type'],
            ]);

            // Tự động khởi tạo sẵn các buổi học theo số lượng buổi quy định
            for ($i = 1; $i <= $validated['total_sessions']; $i++) {
                SubjectContent::create([
                    'subject_id'     => $subject->id,
                    'session_number' => $i,
                    'content'        => "Nội dung bài học số {$i}",
                    'theory_time'    => $validated['subject_type'] === 'practice' ? 0 : 2,
                    'practice_time'  => $validated['subject_type'] === 'theory' ? 0 : 2,
                    'test_time'      => 0,
                ]);
            }

            DB::commit();

            return redirect()->route('subjects.show', $subject->id)
                ->with('success', "Đã thêm môn học '{$subject->name}' ({$subject->code}) với {$subject->total_sessions} buổi học thành công!");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Lỗi khi thêm môn học: ' . $e->getMessage());
        }
    }

    /**
     * Xem chi tiết môn học và danh sách nội dung các buổi học
     */
    public function show($id)
    {
        $subject = Subject::with(['teacher', 'contents', 'classes'])->findOrFail($id);
        $currentTeacher = $this->getCurrentTeacher();
        $allTeachers = User::all();

        // Thống kê tổng số tiết
        $totalTheory = $subject->contents->sum('theory_time');
        $totalPractice = $subject->contents->sum('practice_time');
        $totalTest = $subject->contents->sum('test_time');
        $totalHours = $totalTheory + $totalPractice + $totalTest;

        $templateCount = $subject->countUploadedTemplates();

        return view('subjects.show', compact(
            'subject',
            'currentTeacher',
            'allTeachers',
            'totalTheory',
            'totalPractice',
            'totalTest',
            'totalHours',
            'templateCount'
        ));
    }

    /**
     * Cập nhật thông tin cơ bản của Môn học
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'code'           => 'required|string|max:50|unique:subjects,code,' . $subject->id,
            'name'           => 'required|string|max:255',
            'subject_type'   => 'required|in:integrated,theory,practice',
            'total_sessions' => 'nullable|integer|min:1|max:100',
        ]);

        $subject->update([
            'code'         => trim(mb_strtoupper($validated['code'], 'UTF-8')),
            'name'         => trim($validated['name']),
            'subject_type' => $validated['subject_type'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin môn học thành công!',
                'subject' => $subject,
            ]);
        }

        return back()->with('success', "Đã cập nhật thông tin môn học '{$subject->name}' thành công!");
    }

    /**
     * Xoá môn học
     */
    public function destroy(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $schedulesCount = $subject->schedules()->count();
        if ($schedulesCount > 0 && !$request->has('force')) {
            return back()->with('error', "Môn học này hiện đang có {$schedulesCount} buổi dạy đã được xếp lịch cho các lớp. Vui lòng xác nhận xoá bao gồm cả lịch dạy.");
        }

        DB::beginTransaction();
        try {
            // Xoá lịch dạy liên quan nếu có
            if ($schedulesCount > 0) {
                $subject->schedules()->delete();
            }

            // Xoá nội dung các buổi
            $subject->contents()->delete();

            // Xoá thư mục template nếu có
            $templateDir = $subject->getTemplateDirectory();
            if (is_dir($templateDir)) {
                $files = glob($templateDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) @unlink($file);
                }
                @rmdir($templateDir);
            }

            $subjectCode = $subject->code;
            $subject->delete();

            DB::commit();

            return redirect()->route('subjects.index')
                ->with('success', "Đã xoá môn học {$subjectCode} thành công!");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi xoá môn học: ' . $e->getMessage());
        }
    }

    /**
     * Thêm 1 buổi học mới cho Môn học
     */
    public function storeSession(Request $request, $subjectId): JsonResponse
    {
        $subject = Subject::findOrFail($subjectId);

        $validated = $request->validate([
            'content'       => 'required|string',
            'theory_time'   => 'nullable|numeric|min:0',
            'practice_time' => 'nullable|numeric|min:0',
            'test_time'     => 'nullable|numeric|min:0',
        ]);

        $maxSession = $subject->contents()->max('session_number') ?? 0;
        $nextSession = $maxSession + 1;

        $content = SubjectContent::create([
            'subject_id'     => $subject->id,
            'session_number' => $nextSession,
            'content'        => trim($validated['content']),
            'theory_time'    => $validated['theory_time'] ?? 2,
            'practice_time'  => $validated['practice_time'] ?? 2,
            'test_time'      => $validated['test_time'] ?? 0,
        ]);

        // Cập nhật lại tổng số buổi của môn
        $newTotal = $subject->contents()->count();
        $subject->update(['total_sessions' => $newTotal]);

        return response()->json([
            'success' => true,
            'message' => "Đã thêm Buổi #{$content->session_number} thành công!",
            'data'    => $content,
            'total_sessions' => $newTotal,
        ]);
    }

    /**
     * Cập nhật nội dung & số tiết của một buổi học (Inline Edit / AJAX)
     */
    public function updateSession(Request $request, $subjectId, $contentId): JsonResponse
    {
        $subject = Subject::findOrFail($subjectId);
        $content = SubjectContent::where('subject_id', $subject->id)->findOrFail($contentId);

        $validated = $request->validate([
            'content'             => 'required|string',
            'theory_time'         => 'required|numeric|min:0',
            'practice_time'       => 'required|numeric|min:0',
            'test_time'           => 'nullable|numeric|min:0',
            'objective_knowledge' => 'nullable|string',
            'objective_skills'    => 'nullable|string',
            'objective_autonomy'  => 'nullable|string',
            'teaching_equipment'  => 'nullable|string',
            'teaching_form'       => 'nullable|string',
            'activity_lead_in'    => 'nullable|string',
            'activity_main'       => 'nullable|string',
            'activity_reinforce'  => 'nullable|string',
            'activity_self_study' => 'nullable|string',
            'reference_material'  => 'nullable|string',
            'experience_note'     => 'nullable|string',
        ]);

        $validated['test_time'] = $validated['test_time'] ?? 0;

        $content->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Đã lưu cập nhật Buổi #{$content->session_number} thành công!",
            'data'    => $content,
        ]);
    }

    /**
     * Xoá một buổi học và tự động đánh số lại các buổi tiếp theo
     */
    public function destroySession(Request $request, $subjectId, $contentId): JsonResponse
    {
        $subject = Subject::findOrFail($subjectId);
        $content = SubjectContent::where('subject_id', $subject->id)->findOrFail($contentId);

        $deletedSessionNum = $content->session_number;

        DB::beginTransaction();
        try {
            $content->delete();

            // Đánh số lại các buổi học sau buổi bị xoá
            $remaining = SubjectContent::where('subject_id', $subject->id)
                ->orderBy('session_number')
                ->get();

            $newNumber = 1;
            foreach ($remaining as $item) {
                if ($item->session_number !== $newNumber) {
                    $item->update(['session_number' => $newNumber]);
                }
                $newNumber++;
            }

            // Cập nhật lại total_sessions
            $newTotal = $remaining->count();
            $subject->update(['total_sessions' => $newTotal]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Đã xoá buổi học #{$deletedSessionNum} và tự động sắp xếp lại thứ tự các buổi!",
                'total_sessions' => $newTotal,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xoá buổi học: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật hàng loạt danh sách các buổi học từ bảng
     */
    public function batchUpdateSessions(Request $request, $subjectId)
    {
        $subject = Subject::findOrFail($subjectId);

        $sessions = $request->input('sessions', []);

        DB::beginTransaction();
        try {
            foreach ($sessions as $id => $data) {
                $content = SubjectContent::where('subject_id', $subject->id)->find($id);
                if ($content) {
                    $content->update([
                        'content'       => $data['content'] ?? $content->content,
                        'theory_time'   => isset($data['theory_time']) ? (float)$data['theory_time'] : $content->theory_time,
                        'practice_time' => isset($data['practice_time']) ? (float)$data['practice_time'] : $content->practice_time,
                        'test_time'     => isset($data['test_time']) ? (float)$data['test_time'] : $content->test_time,
                    ]);
                }
            }
            DB::commit();

            return back()->with('success', 'Đã lưu tất cả các thay đổi nội dung buổi học thành công!');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi cập nhật hàng loạt: ' . $e->getMessage());
        }
    }
}
