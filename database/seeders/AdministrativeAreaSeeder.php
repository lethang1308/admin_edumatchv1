<?php

namespace Database\Seeders;

use App\Models\AdministrativeArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdministrativeAreaSeeder extends Seeder
{
    public function run(): void
    {
        $snapshot = json_decode(file_get_contents(database_path('data/vietnam-administrative-areas-v2.json')), true, flags: JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($snapshot) {
            $parents = [];
            foreach ($snapshot['areas'] as $area) {
                if ($area['level'] === 'province') {
                    $record = AdministrativeArea::updateOrCreate(['code' => $area['code']], [
                        'name' => $area['name'], 'level' => 'province', 'parent_id' => null, 'is_active' => true,
                    ]);
                    $parents[$area['code']] = $record->id;
                }
            }
            $rows = [];
            foreach ($snapshot['areas'] as $area) {
                if ($area['level'] === 'ward') {
                    $rows[] = [
                        'code' => $area['code'], 'name' => $area['name'], 'level' => 'ward',
                        'parent_id' => $parents[$area['parent_code']], 'is_active' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
            }
            foreach (array_chunk($rows, 300) as $chunk) {
                AdministrativeArea::upsert($chunk, ['code'], ['name', 'level', 'parent_id', 'is_active', 'updated_at']);
            }
        });
    }
}
