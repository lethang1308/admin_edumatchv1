<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class Phase2MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Toán', 'Tiếng Anh', 'Vật lý', 'Hóa học', 'Ngữ văn', 'Sinh học', 'Lịch sử', 'Địa lý', 'Tin học', 'Tiếng Trung', 'Tiếng Nhật', 'Âm nhạc', 'Mỹ thuật'] as $index => $name) {
            if (! Subject::where('slug', Str::slug($name))->orWhere('name', $name)->exists()) {
                Subject::create(['slug' => Str::slug($name), 'name' => $name, 'sort_order' => $index, 'is_active' => true]);
            }
        }
        foreach (range(1, 12) as $grade) {
            EducationLevel::firstOrCreate(['code' => 'grade-'.$grade], ['name' => 'Lớp '.$grade, 'sort_order' => $grade, 'is_active' => true]);
        }
        foreach (['mam-non' => 'Mầm non', 'luyen-thi-thpt' => 'Luyện thi Đại học / THPT', 'dai-hoc' => 'Đại học', 'nguoi-di-lam' => 'Người đi làm'] as $code => $name) {
            EducationLevel::firstOrCreate(['code' => $code], ['name' => $name, 'sort_order' => $code === 'mam-non' ? 0 : 20, 'is_active' => true]);
        }
    }
}
