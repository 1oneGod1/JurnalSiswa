<?php

namespace Database\Seeders;

use App\Services\FirebaseService;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'guru@example.com'],
            [
                'name' => 'Sari Wulandari',
                'password' => Hash::make('password'),
                'role' => 'guru',
                'group_id' => null,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'siswa@example.com'],
            [
                'name' => 'Aisha Rahman',
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'group_id' => 'group_01',
            ],
        );

        $firebase = app(FirebaseService::class);

        if (empty($firebase->all('groups'))) {
            foreach ($this->groups() as $id => $group) {
                $firebase->set('groups', $id, $group);
            }
        }

        if (empty($firebase->all('targets'))) {
            foreach ($this->targets() as $id => $target) {
                $firebase->set('targets', $id, $target);
            }
        }

        if (empty($firebase->all('readiness'))) {
            foreach ($this->readiness() as $id => $readiness) {
                $firebase->set('readiness', $id, $readiness);
            }
        }

        if (empty($firebase->all('journals'))) {
            $firebase->set('journals', 'journal_001', [
                'group_id' => 'group_01',
                'meeting_no' => 6,
                'journal_date' => now()->subDay()->toDateString(),
                'target_checklist' => [
                    'target_001' => true,
                    'target_002' => false,
                ],
                'target_vs_realization' => 'Target memasang sensor air dan mulai testing awal.',
                'progress_today' => 'Kami memasang sensor air pada maket dan menghubungkan buzzer alarm.',
                'data_result' => 'Alarm aktif pada tinggi air 8 cm.',
                'problem' => 'Sensor belum stabil saat air bergerak.',
                'solution_next_step' => 'Testing ulang minimal 5 percobaan dengan wadah lebih stabil.',
                'insight' => 'Data perlu diulang agar lebih valid.',
                'help_request' => true,
                'created_by' => '2',
            ]);
        }
    }

    private function groups(): array
    {
        return [
            'group_01' => [
                'name' => 'Hydroponic Monitor',
                'project_title' => 'IoT plant growth tracker',
                'members' => ['Aisha Rahman', 'Budi Santoso', 'Clara Widjaja', 'Dinda Putri'],
                'advisor' => 'Sari Wulandari',
            ],
            'group_02' => [
                'name' => 'Solar Tracker v2',
                'project_title' => 'Dual-axis PV panel',
                'members' => ['Evan Halim', 'Farida Nasution', 'Gilang Pramudya'],
                'advisor' => 'Sari Wulandari',
            ],
            'group_03' => [
                'name' => 'Water Quality AI',
                'project_title' => 'ML turbidity classifier',
                'members' => ['Hana Lestari', 'Irfan Maulana', 'Jingga Kartika'],
                'advisor' => 'Adrian Nugroho',
            ],
        ];
    }

    private function targets(): array
    {
        return [
            'target_001' => [
                'title' => 'Maket versi awal selesai',
                'meeting_no' => 5,
                'target_date' => now()->subWeek()->toDateString(),
                'description' => 'Bentuk dasar maket sudah bisa memperlihatkan alur sistem.',
                'checklist_items' => ['Struktur utama selesai', 'Area sensor disiapkan', 'Dokumentasi foto diupload'],
                'status' => 'active',
                'created_by' => '1',
            ],
            'target_002' => [
                'title' => 'Arduino terpasang dan sensor diuji',
                'meeting_no' => 6,
                'target_date' => now()->toDateString(),
                'description' => 'Sensor utama terhubung ke Arduino dan ada satu hasil uji awal.',
                'checklist_items' => ['Arduino menyala', 'Sensor membaca nilai', 'Catat minimal 1 data hasil uji'],
                'status' => 'active',
                'created_by' => '1',
            ],
            'target_003' => [
                'title' => 'Dokumentasi evidence lengkap',
                'meeting_no' => 7,
                'target_date' => now()->addWeek()->toDateString(),
                'description' => 'Foto/video progres dan kendala diupload pada jurnal.',
                'checklist_items' => ['Foto maket', 'Video testing', 'Screenshot coding'],
                'status' => 'active',
                'created_by' => '1',
            ],
        ];
    }

    private function readiness(): array
    {
        return [
            'readiness_001' => ['group_id' => 'group_01', 'score' => 62],
            'readiness_002' => ['group_id' => 'group_02', 'score' => 48],
            'readiness_003' => ['group_id' => 'group_03', 'score' => 75],
        ];
    }
}
