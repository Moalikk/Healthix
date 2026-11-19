<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $ts = ['created_at' => now(), 'updated_at' => now()];

        $hospitals = [
            'general'  => ['name' => 'Sample General Hospital', 'type' => 'general_hospital', 'address' => 'Jl. Contoh No. 1', 'city' => 'Yogyakarta'],
            'dental'   => ['name' => 'Sample Dental Hospital', 'type' => 'dental_hospital', 'address' => 'Jl. Contoh No. 2', 'city' => 'Sleman'],
            'eye'      => ['name' => 'Sample Eye Hospital', 'type' => 'eye_hospital', 'address' => 'Jl. Contoh No. 3', 'city' => 'Bantul'],
            'maternal' => ['name' => 'Sample Mother & Child Hospital', 'type' => 'maternal_child_hospital', 'address' => 'Jl. Contoh No. 4', 'city' => 'Yogyakarta'],
        ];

        $hospitalIds = [];
        foreach ($hospitals as $key => $hospital) {
            $hospitalIds[$key] = DB::table('hospitals')->insertGetId(
                $hospital + ['image_url' => null, 'is_active' => true] + $ts
            );
        }

        $departments = [
            'general' => [
                'hospital_id'       => $hospitalIds['general'],
                'name'              => 'General Medicine',
                'description'       => 'General check-ups and first consultation for common health complaints.',
                'common_conditions' => 'Fever, cough, cold, headache, fatigue, stomach ache, general check-up',
                'keywords'          => 'fever,cough,cold,flu,headache,tired,fatigue,stomach,nausea,check-up,demam,batuk,pilek,pusing,mual',
            ],
            'skin' => [
                'hospital_id'       => $hospitalIds['general'],
                'name'              => 'Dermatology',
                'description'       => 'Skin, hair and nail problems.',
                'common_conditions' => 'Rash, itching, acne, eczema, skin allergy, fungal infection, hair loss',
                'keywords'          => 'skin,rash,itch,itchy,acne,eczema,allergy,fungus,hair loss,gatal,ruam,jerawat,kulit',
            ],
            'dental' => [
                'hospital_id'       => $hospitalIds['dental'],
                'name'              => 'Dental Clinic',
                'description'       => 'Teeth and gum care.',
                'common_conditions' => 'Toothache, cavities, swollen gums, tooth extraction, dental cleaning',
                'keywords'          => 'tooth,teeth,gum,toothache,cavity,dental,mouth,gigi,sakit gigi,gusi',
            ],
            'eye' => [
                'hospital_id'       => $hospitalIds['eye'],
                'name'              => 'Eye Clinic',
                'description'       => 'Eye examination and treatment.',
                'common_conditions' => 'Blurred vision, red eye, eye pain, dry eyes, glasses check',
                'keywords'          => 'eye,vision,blurry,red eye,glasses,sight,mata,buram,perih,kacamata',
            ],
            'maternal' => [
                'hospital_id'       => $hospitalIds['maternal'],
                'name'              => 'Maternal & Child Health',
                'description'       => 'Care for pregnant women, mothers and young children.',
                'common_conditions' => 'Pregnancy check-up, child immunization, child growth, child fever',
                'keywords'          => 'pregnant,pregnancy,baby,child,infant,immunization,vaccine,hamil,bayi,anak,imunisasi',
            ],
        ];

        $ids = [];
        foreach ($departments as $key => $department) {
            $ids[$key] = DB::table('departments')->insertGetId($department + $ts);
        }

        DB::table('doctors')->insert([
            ['department_id' => $ids['general'],  'name' => 'dr. Rina Wulandari', 'specialization' => 'General Practitioner'] + $ts,
            ['department_id' => $ids['skin'],     'name' => 'dr. Bayu Pratama',   'specialization' => 'Dermatologist'] + $ts,
            ['department_id' => $ids['dental'],   'name' => 'drg. Maya Lestari',  'specialization' => 'Dentist'] + $ts,
            ['department_id' => $ids['eye'],      'name' => 'dr. Hendra Kusuma',  'specialization' => 'Ophthalmologist'] + $ts,
            ['department_id' => $ids['maternal'], 'name' => 'dr. Siti Aminah',    'specialization' => 'Obstetrician'] + $ts,
        ]);

        $this->call(DemoDataSeeder::class);
    }
}