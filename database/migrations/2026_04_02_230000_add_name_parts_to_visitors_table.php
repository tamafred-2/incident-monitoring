<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('visitors', 'surname')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->string('surname', 100)->nullable();
                $table->string('first_name', 100)->nullable();
                $table->string('middle_initials', 20)->nullable();
                $table->string('extension', 20)->nullable();
            });
        }

        $visitors = DB::table('visitors')
            ->select('visitor_id', 'full_name', 'surname', 'first_name', 'middle_initials', 'extension')
            ->get();

        foreach ($visitors as $visitor) {
            $parsed = $this->parseFullName((string) $visitor->full_name);

            $firstName = $visitor->first_name ?: $parsed['first_name'];
            $middleInitials = $visitor->middle_initials ?: $parsed['middle_initials'];
            $surname = $visitor->surname ?: $parsed['surname'];
            $extension = $visitor->extension ?: $parsed['extension'];

            DB::table('visitors')
                ->where('visitor_id', $visitor->visitor_id)
                ->update([
                    'first_name' => $firstName,
                    'middle_initials' => $middleInitials,
                    'surname' => $surname,
                    'extension' => $extension,
                    'full_name' => $this->formatFullName($firstName, $middleInitials, $surname, $extension),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('visitors', 'surname')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->dropColumn(['surname', 'first_name', 'middle_initials', 'extension']);
            });
        }
    }

    private function parseFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if ($parts === []) {
            return [
                'first_name' => null,
                'middle_initials' => null,
                'surname' => null,
                'extension' => null,
            ];
        }

        $extension = null;
        $extensions = ['JR', 'SR', 'II', 'III', 'IV', 'V'];
        $lastPart = strtoupper(rtrim((string) end($parts), '.'));

        if (count($parts) > 1 && in_array($lastPart, $extensions, true)) {
            $extension = array_pop($parts);
        }

        $firstName = array_shift($parts);
        $surname = count($parts) > 0 ? array_pop($parts) : null;
        $middleInitials = count($parts) > 0 ? implode(' ', $parts) : null;

        return [
            'first_name' => $firstName ?: null,
            'middle_initials' => $middleInitials ?: null,
            'surname' => $surname ?: null,
            'extension' => $extension ?: null,
        ];
    }

    private function formatFullName(?string $firstName, ?string $middleInitials, ?string $surname, ?string $extension): string
    {
        $segments = array_filter([
            $this->cleanPart($firstName),
            $this->cleanPart($middleInitials),
            $this->cleanPart($surname),
            $this->cleanPart($extension),
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        return implode(' ', $segments);
    }

    private function cleanPart(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
};
