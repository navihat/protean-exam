<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@example.com')->value('id');

        foreach ($this->exams() as $data) {
            if (Exam::withTrashed()->where('name', $data['name'])->exists()) {
                continue;
            }

            DB::transaction(function () use ($data, $adminId) {
                $exam = new Exam([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'duration_minutes' => $data['duration_minutes'],
                    'is_published' => $data['is_published'],
                ]);
                $exam->created_by = $adminId;
                $exam->save();

                foreach ($data['questions'] as $qIndex => [$content, $options]) {
                    $question = $exam->questions()->create([
                        'content' => $content,
                        'position' => $qIndex + 1,
                    ]);

                    // Options are written as [text, isCorrect].
                    $question->options()->createMany(
                        collect($options)->values()->map(fn (array $option, int $i) => [
                            'content' => $option[0],
                            'is_correct' => $option[1],
                            'position' => $i + 1,
                        ])->all()
                    );
                }
            });
        }
    }

    /**
     * @return list<array{name: string, description: string, duration_minutes: int, is_published: bool, questions: list<array{string, list<array{string, bool}>}>}>
     */
    private function exams(): array
    {
        return [
            [
                'name' => 'PHP cơ bản',
                'description' => 'Kiến thức nền tảng về cú pháp và kiểu dữ liệu PHP.',
                'duration_minutes' => 10,
                'is_published' => true,
                'questions' => [
                    ['Hàm nào dùng để đếm số phần tử của một mảng?', [['count()', true], ['strlen()', false], ['length()', false], ['size()', false]]],
                    ['Toán tử nào so sánh cả giá trị và kiểu dữ liệu?', [['===', true], ['==', false], ['=', false], ['<=>', false]]],
                    ['Tên biến trong PHP bắt đầu bằng ký tự nào?', [['$', true], ['@', false], ['#', false], ['&', false]]],
                    ['Từ khoá nào khai báo hằng số bên trong class?', [['const', true], ['define', false], ['static', false], ['final', false]]],
                    ['Những kiểu nào là kiểu vô hướng (scalar)? (chọn nhiều)', [['int', true], ['string', true], ['array', false], ['object', false]]],
                ],
            ],
            [
                'name' => 'Laravel căn bản',
                'description' => 'Artisan, Eloquent, validation và middleware.',
                'duration_minutes' => 15,
                'is_published' => true,
                'questions' => [
                    ['Lệnh nào tạo một migration mới?', [['php artisan make:migration', true], ['php artisan migration:new', false], ['php artisan db:migration', false], ['composer make:migration', false]]],
                    ['Phương thức nào eager load quan hệ để tránh N+1?', [['with()', true], ['join()', false], ['lazy()', false], ['select()', false]]],
                    ['Nên đặt rule validate của request ở đâu?', [['FormRequest', true], ['Model', false], ['Migration', false], ['Blade view', false]]],
                    ['Những cách nào dùng để làm việc với cache? (chọn nhiều)', [['Cache::remember()', true], ['cache()', true], ['Session::flash()', false], ['Route::redirect()', false]]],
                    ['Middleware `auth` dùng để làm gì?', [['Yêu cầu người dùng đã đăng nhập', true], ['Mã hoá cookie', false], ['Giới hạn tần suất request', false], ['Nén response', false]]],
                ],
            ],
            [
                'name' => 'Tiếng Anh – Ngữ pháp A2',
                'description' => 'Thì động từ, giới từ và so sánh hơn.',
                'duration_minutes' => 10,
                'is_published' => true,
                'questions' => [
                    ['She ___ to school every day.', [['goes', true], ['go', false], ['going', false], ['gone', false]]],
                    ['I have lived here ___ 2020.', [['since', true], ['for', false], ['from', false], ['at', false]]],
                    ['Which words are adjectives? (choose all that apply)', [['happy', true], ['quickly', false], ['beautiful', true], ['run', false]]],
                    ['They ___ watching TV when I arrived.', [['were', true], ['was', false], ['are', false], ['is', false]]],
                    ['This book is ___ than that one.', [['more interesting', true], ['interestinger', false], ['most interesting', false], ['more interestinger', false]]],
                ],
            ],
            [
                'name' => 'Đề nháp (chưa publish)',
                'description' => 'Đề chưa publish, student không nhìn thấy.',
                'duration_minutes' => 5,
                'is_published' => false,
                'questions' => [
                    ['1 + 1 = ?', [['2', true], ['3', false]]],
                ],
            ],
        ];
    }
}
