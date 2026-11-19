<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    
        {
            $ts = ['created_at' => now(), 'updated_at' => now()];

            $hospitalId = DB::table('hospitals')->insertGetId([
                'name' => 'General Hospital Jakarta',
                'type' => 'general_hospital',
                'address' => 'Jl. Contoh No. 1',
                'city' => 'Jakarta Pusat',
            ] + $ts);

            $departments = [
                ['General Medicine', 'First check-up and common complaints. Not sure where to go? Start here.', 'fever, cough, cold, dizziness, fatigue'],
                ['Dermatology', 'Skin, hair, and nails.', 'itching, rash, acne, fungal infection, skin allergy'],
                ['Dental Clinic', 'Teeth and mouth.', 'toothache, swollen gums, dental check-up'],
                ['Eye Clinic', 'Eyes and vision.', 'red eyes, blurry vision, eye pain'],
            ];

            $ids = [];
            foreach ($departments as [$name, $description, $conditions]) {
                $ids[$name] = DB::table('departments')->insertGetId([
                    'hospital_id' => $hospitalId,
                    'name' => $name,
                    'description' => $description,
                    'common_conditions' => $conditions,
                ] + $ts);
            }

            DB::table('doctors')->insert([
                ['department_id' => $ids['General Medicine'], 'name' => 'Dr. Andi Pratama', 'specialization' => 'General Practitioner'] + $ts,
                ['department_id' => $ids['Dermatology'], 'name' => 'Dr. Siti Rahma', 'specialization' => 'Dermatologist'] + $ts,
                ['department_id' => $ids['Dental Clinic'], 'name' => 'Dr. Budi Santoso', 'specialization' => 'Dentist'] + $ts,
            ]);

            $this->call(DemoDataSeeder::class);
        }
}
