<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DepartmentSuggester
{
    // Jika salah satu kata ini muncul, sistem TIDAK menyarankan departemen,
    // hanya mengarahkan ke bantuan darurat.
    const EMERGENCY_KEYWORDS = [
        'chest pain', 'cannot breathe', "can't breathe", 'shortness of breath', 'unconscious',
        'fainted', 'seizure', 'stroke', 'heavy bleeding', 'severe bleeding', 'poison', 'overdose', 'suicide',
        'nyeri dada', 'sesak napas', 'sesak nafas', 'pingsan', 'tidak sadar', 'kejang',
        'pendarahan hebat', 'perdarahan hebat', 'keracunan', 'bunuh diri',
    ];

    // General Medicine punya kata kunci yang umum (demam, batuk, check-up),
    // jadi nilainya dikurangi agar departemen yang lebih spesifik menang.
    const GENERAL_WEIGHT = 0.5;

    public function suggest(string $message): array
    {
        $text = mb_strtolower($message);

        // 1. Cek darurat dulu
        foreach (self::EMERGENCY_KEYWORDS as $keyword) {
            if ($this->contains($text, $keyword)) {
                return [
                    'emergency'       => true,
                    'department_id'   => null,
                    'department_name' => null,
                    'hospital_name'   => null,
                    'matched'         => [],
                    'confidence'      => null,
                    'reason'          => 'This may be an emergency. Please call 112 or go to the nearest hospital emergency room (IGD) now. Do not wait for an appointment.',
                ];
            }
        }

        // 2. Cocokkan dengan kata kunci tiap departemen
        $departments = DB::table('departments')
            ->join('hospitals', 'departments.hospital_id', '=', 'hospitals.id')
            ->where('hospitals.is_active', true)
            ->orderBy('departments.id')
            ->select('departments.id', 'departments.name', 'departments.keywords', 'hospitals.name as hospital_name')
            ->get();

        $best = null;
        $bestMatched = [];
        $bestScore = 0;

        foreach ($departments as $department) {
            $matched = [];
            foreach (explode(',', (string) $department->keywords) as $keyword) {
                $keyword = trim(mb_strtolower($keyword));
                if ($keyword !== '' && $this->contains($text, $keyword)) {
                    $matched[] = $keyword;
                }
            }
            $weight = $department->name === 'General Medicine' ? self::GENERAL_WEIGHT : 1;
            $score = count($matched) * $weight;
            if ($score > $bestScore) {
                $best = $department;
                $bestMatched = $matched;
                $bestScore = $score;
            }
        }

        // 3. Tidak ada yang cocok -> General Medicine
        if (!$best) {
            $fallback = $departments->firstWhere('name', 'General Medicine') ?? $departments->first();
            return $this->result($fallback, [], 'No clear match. General Medicine is a safe place to start.', 'low');
        }

        return $this->result(
            $best,
            $bestMatched,
            'Matched: ' . implode(', ', $bestMatched),
            $bestScore >= 2 ? 'high' : ($bestScore >= 1 ? 'medium' : 'low')
        );
    }

    private function result($department, array $matched, string $reason, string $confidence): array
    {
        return [
            'emergency'       => false,
            'department_id'   => $department?->id,
            'department_name' => $department?->name,
            'hospital_name'   => $department?->hospital_name,
            'matched'         => $matched,
            'confidence'      => $confidence,
            'reason'          => $reason,
        ];
    }

    // Cocok sebagai kata utuh (bukan potongan di tengah kata)
    private function contains(string $text, string $keyword): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{N}])' . preg_quote($keyword, '/') . '(?![\p{L}\p{N}])/u', $text);
    }
}