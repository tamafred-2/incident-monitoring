<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'surname')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('surname', 100)->nullable();
                $table->string('first_name', 100)->nullable();
                $table->string('middle_name', 100)->nullable();
                $table->string('extension', 20)->nullable();
            });
        }

        $users = DB::table('users')
            ->select('user_id', 'full_name', 'surname', 'first_name', 'middle_name', 'extension')
            ->get();

        foreach ($users as $user) {
            $parsed = $this->parseFullName((string) $user->full_name);

            $firstName = $user->first_name ?: $parsed['first_name'];
            $middleName = $user->middle_name ?: $parsed['middle_name'];
            $surname = $user->surname ?: $parsed['surname'];
            $extension = $user->extension ?: $parsed['extension'];

            DB::table('users')
                ->where('user_id', $user->user_id)
                ->update([
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'surname' => $surname,
                    'extension' => $extension,
                    'full_name' => $this->formatFullName($firstName, $middleName, $surname, $extension),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'surname')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['surname', 'first_name', 'middle_name', 'extension']);
            });
        }
    }

    private function parseFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if ($parts === []) {
            return [
                'first_name' => null,
                'middle_name' => null,
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
        $middleName = count($parts) > 0 ? implode(' ', $parts) : null;

        return [
            'first_name' => $firstName ?: null,
            'middle_name' => $middleName ?: null,
            'surname' => $surname ?: null,
            'extension' => $extension ?: null,
        ];
    }

    private function formatFullName(?string $firstName, ?string $middleName, ?string $surname, ?string $extension): string
    {
        $segments = array_filter([
            $this->cleanNamePart($firstName),
            $this->cleanNamePart($middleName),
            $this->cleanNamePart($surname),
            $this->cleanNamePart($extension),
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        return implode(' ', $segments);
    }

    private function cleanNamePart(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
};
