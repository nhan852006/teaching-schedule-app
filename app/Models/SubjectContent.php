<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'session_number',
        'content',
        'theory_time',
        'practice_time',
        'test_time',
        'objective_knowledge',
        'objective_skills',
        'objective_autonomy',
        'teaching_equipment',
        'teaching_form',
        'activity_lead_in',
        'activity_main',
        'activity_reinforce',
        'activity_self_study',
        'reference_material',
        'experience_note',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Tiêu đề bài học ngắn gọn
     */
    public function getTitleCleanAttribute(): string
    {
        if (empty($this->content)) {
            return "Bài học số {$this->session_number}";
        }
        $lines = explode("\n", trim($this->content));
        $firstLine = trim($lines[0]);
        return mb_strlen($firstLine) > 80 ? mb_substr($firstLine, 0, 80) . '...' : $firstLine;
    }

    /**
     * Mục tiêu kiến thức (Có sẵn hoặc tự động tạo chuẩn sư phạm)
     */
    public function getEffectiveKnowledgeAttribute(): string
    {
        if (!empty($this->objective_knowledge)) {
            return $this->objective_knowledge;
        }
        return "Trình bày và giải thích được các khái niệm, quy trình và kiến thức trọng tâm của bài học: {$this->title_clean}.";
    }

    /**
     * Mục tiêu kỹ năng
     */
    public function getEffectiveSkillsAttribute(): string
    {
        if (!empty($this->objective_skills)) {
            return $this->objective_skills;
        }
        return "Thực hiện thành thạo các thao tác cài đặt, cấu hình, xử lý và vận hành chính xác các nội dung: {$this->title_clean}.";
    }

    /**
     * Mức độ tự chủ và trách nhiệm
     */
    public function getEffectiveAutonomyAttribute(): string
    {
        if (!empty($this->objective_autonomy)) {
            return $this->objective_autonomy;
        }
        return "Rèn luyện tác phong công nghiệp, tuân thủ an toàn thiết bị, chủ động nghiên cứu và có tinh thần trách nhiệm trong học tập.";
    }

    /**
     * Đồ dùng và phương tiện dạy học
     */
    public function getEffectiveEquipmentAttribute(): string
    {
        if (!empty($this->teaching_equipment)) {
            return $this->teaching_equipment;
        }
        return "Phòng máy tính, máy chiếu (Projector), bảng, bút viết, phần mềm chuyên môn và mạng Internet.";
    }

    /**
     * Hình thức tổ chức dạy học
     */
    public function getEffectiveTeachingFormAttribute(): string
    {
        if (!empty($this->teaching_form)) {
            return $this->teaching_form;
        }
        return "Tập trung toàn lớp để hướng dẫn, kết hợp phân nhóm thực hành và kèm cặp cá nhân.";
    }

    /**
     * Dẫn nhập
     */
    public function getEffectiveLeadInAttribute(): string
    {
        if (!empty($this->activity_lead_in)) {
            return $this->activity_lead_in;
        }
        return "Ổn định lớp, điểm danh; Gợi mở, trao đổi mục tiêu và tạo tâm thế tích cực cho người học.";
    }

    /**
     * Hoạt động chính (Giảng bài mới / Hướng dẫn / Giải quyết vấn đề)
     */
    public function getEffectiveMainAttribute(): string
    {
        if (!empty($this->activity_main)) {
            return $this->activity_main;
        }
        return "Giáo viên hướng dẫn lý thuyết cốt lõi kết hợp thao tác mẫu; Học sinh chú ý quan sát, ghi chép và thực hành rèn luyện theo yêu cầu.";
    }

    /**
     * Củng cố kiến thức / Kết thúc bài
     */
    public function getEffectiveReinforceAttribute(): string
    {
        if (!empty($this->activity_reinforce)) {
            return $this->activity_reinforce;
        }
        return "Tổng kết kiến thức trọng tâm, đánh giá kết quả luyện tập của học sinh, nhắc nhở lỗi thường gặp và giải đáp câu hỏi.";
    }

    /**
     * Hướng dẫn tự học / Tự rèn luyện
     */
    public function getEffectiveSelfStudyAttribute(): string
    {
        if (!empty($this->activity_self_study)) {
            return $this->activity_self_study;
        }
        return "Ôn lại các nội dung đã học, thực hành củng cố kỹ năng và đọc trước nội dung bài học tiếp theo.";
    }

    /**
     * Nguồn tài liệu tham khảo
     */
    public function getEffectiveReferenceAttribute(): string
    {
        if (!empty($this->reference_material)) {
            return $this->reference_material;
        }
        return "Giáo trình môn học, tài liệu bài giảng nội bộ và tài liệu kỹ thuật trực tuyến.";
    }
}
