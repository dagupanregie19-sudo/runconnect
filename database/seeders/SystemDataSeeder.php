<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\OrganizerProfile;
use App\Models\RunnerProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds 15 runner accounts (role "user" + runner profiles) and 10 organizer accounts
 * with three Tagbina-based events each (Beginner / Improving / Intermediate).
 *
 * Pricing layout (30 events total):
 * - 5 free: Beginner events for organizers 0–4 (registration_fee = 0).
 * - 10 premium paid: every organizer's Intermediate event (registration_fee = 249).
 * - 15 standard paid: all Improving events + Beginner events for organizers 5–9.
 *
 * Run: php artisan db:seed --class=SystemDataSeeder
 *
 * Credentials pattern: username = ascii-name + 2–3 digit suffix; email = same local part @gmail.com
 */
class SystemDataSeeder extends Seeder
{
    private const RUNNER_PASSWORD = '12345678';

    private const ORGANIZER_PASSWORD = '@Testing123';

    private const RUNNER_NAMES = [
        'Mark H. Baño',
        'Limpuasan, Mark Kenshane',
        'Roseann Joy Lozano',
        'Kervin Dequito',
        'Esrael G. Mesias',
        'Elijah Faith Cabunilas',
        'Adlawan Maki',
        'Marvi Sereño',
        'Naico Vince Rulete',
        'Jonel Relador',
        'Jan Levi Conde',
        'Regine Mae Bacalso',
        'Jomarcben Capunong',
        'John Carl Bilad',
        'Queenie Deve C. Barboza',
    ];

    private const ORGANIZER_NAMES = [
        'Morgado, Jaycelle A.',
        'Nikka Jayne Lozano',
        'King Gerald Glodo',
        'Ryan Angelo Plangganan',
        'Roxan Abrio',
        'Kurt Gitano',
        'Regie G. Dagupan',
        'John Lloyd Rizaldo',
        'March Michael B. Olbida',
        'Mark Lorence Lalisan',
    ];

    /** Tagbina, Surigao del Sur — center for short roadway-style polylines */
    private const TAGBINA_LAT = 8.1167;

    private const TAGBINA_LON = 126.1667;

    public function run(): void
    {
        Storage::disk('public')->makeDirectory('routes');

        $fitnessCycle = ['beginner', 'improving', 'intermediate'];
        $barangays = ['Kahayagan', 'Poblacion', 'San Vicente', 'Santa Fe', 'Maglambing', 'La Suerte', 'Binondo', 'Doña Carmen', 'Malixi', 'Ugoban'];

        foreach (self::RUNNER_NAMES as $i => $displayName) {
            $parsed = $this->parseDisplayName($displayName);
            ['username' => $username, 'email' => $email] = $this->buildNameNumberCredentials($parsed, $i);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'username' => $username,
                    'password' => self::RUNNER_PASSWORD,
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]
            );

            if ($user->role !== 'user') {
                $user->forceFill(['role' => 'user'])->save();
            }

            RunnerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'fitness_level' => $fitnessCycle[$i % 3],
                    'verified_pace' => round(5.5 + ($i % 12) * 0.12, 2),
                    'first_name' => $parsed['first_name'],
                    'last_name' => $parsed['last_name'],
                    'middle_name' => $parsed['middle_name'],
                    'name_extension' => null,
                    'age' => 20 + ($i % 35),
                    'gender' => $i % 2 === 0 ? 'Male' : 'Female',
                    'address' => ($barangays[$i % count($barangays)]).', Tagbina, Surigao del Sur, Philippines',
                    'phone_number' => sprintf('0900%07d', 1000000 + $i),
                    'health_conditions' => [],
                ]
            );
        }

        foreach (self::ORGANIZER_NAMES as $o => $displayName) {
            $parsed = $this->parseDisplayName($displayName);
            ['username' => $username, 'email' => $email] = $this->buildNameNumberCredentials($parsed, $o);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'username' => $username,
                    'password' => self::ORGANIZER_PASSWORD,
                    'role' => 'organizer',
                    'email_verified_at' => now(),
                ]
            );

            if ($user->role !== 'organizer') {
                $user->forceFill(['role' => 'organizer'])->save();
            }

            $orgLabel = trim($parsed['last_name'].' '.$parsed['first_name']);
            OrganizerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'organization_name' => $orgLabel.' — Tagbina Runs',
                    'phone_number' => sprintf('0917%07d', 2000000 + $o),
                    'address' => ($barangays[$o % count($barangays)]).', Tagbina, Surigao del Sur, Philippines',
                    'verification_status' => 'verified',
                ]
            );

            $triples = [
                ['label' => 'Beginner', 'difficulty' => 'Beginner', 'distance' => 3.5],
                ['label' => 'Improving', 'difficulty' => 'Improving', 'distance' => 6.0],
                ['label' => 'Intermediate', 'difficulty' => 'Intermediate', 'distance' => 12.0],
            ];

            foreach ($triples as $idx => $t) {
                $eventName = 'Tagbina '.$t['label'].' Run — '.$username;
                $globalIdx = $o * 3 + $idx;

                $fee = $this->resolveRegistrationFee($o, $t['difficulty']);

                $routePath = $this->storeRouteGeoJson($user->id, $globalIdx, $t['difficulty']);

                $regStart = Carbon::now()->subDays(5)->addDays($globalIdx);
                $regEnd = (clone $regStart)->addWeeks(3);
                $eventDay = (clone $regEnd)->addWeeks(2);

                Event::updateOrCreate(
                    [
                        'organizer_id' => $user->id,
                        'name' => $eventName,
                    ],
                    [
                        'difficulty' => $t['difficulty'],
                        'distance' => $t['distance'],
                        'description' => 'Seeded '.$t['difficulty'].' route along local roads in Tagbina, Surigao del Sur. Community run for training and fitness.',
                        'registration_start' => $regStart->toDateString(),
                        'registration_end' => $regEnd->toDateString(),
                        'slots' => 80 + ($globalIdx % 40),
                        'location' => 'Tagbina, Surigao del Sur, Philippines',
                        'event_date' => $eventDay->toDateString(),
                        'event_time' => '05:30:00',
                        'registration_fee' => $fee,
                        'route_data' => $routePath,
                        'status' => 'upcoming',
                    ]
                );
            }
        }

        $this->command?->info('SystemDataSeeder: 15 runners, 10 organizers, 30 events (5 free, 10 premium paid, 15 standard paid).');
    }

    /**
     * @return array{first_name: string, last_name: string, middle_name: ?string}
     */
    private function parseDisplayName(string $full): array
    {
        $full = trim($full);
        if (str_contains($full, ',')) {
            [$lastPart, $rest] = array_map('trim', explode(',', $full, 2));
            $tokens = preg_split('/\s+/', $rest) ?: [];
            $first = array_shift($tokens) ?? 'Organizer';
            $middle = $tokens ? implode(' ', $tokens) : null;

            return [
                'first_name' => $first,
                'last_name' => $lastPart,
                'middle_name' => $middle,
            ];
        }

        $parts = preg_split('/\s+/', $full) ?: [];
        if (count($parts) === 1) {
            return ['first_name' => $parts[0], 'last_name' => 'Member', 'middle_name' => null];
        }
        $last = array_pop($parts);
        $first = array_shift($parts) ?? $last;

        return [
            'first_name' => $first,
            'last_name' => $last,
            'middle_name' => $parts ? implode(' ', $parts) : null,
        ];
    }

    /**
     * Username and Gmail local part: concatenated ASCII letters from the name + a 2- or 3-digit suffix.
     * Even indices use 2 digits (01–99); odd indices use 3 digits (001–999 style for small n).
     *
     * @return array{username: string, email: string}
     */
    private function buildNameNumberCredentials(array $parsed, int $index): array
    {
        $base = $this->alphanumericNameBase($parsed);
        $suffixLen = ($index % 2 === 0) ? 2 : 3;
        $suffix = str_pad((string) ($index + 1), $suffixLen, '0', STR_PAD_LEFT);
        $username = $base.$suffix;
        $email = $username.'@gmail.com';

        return ['username' => $username, 'email' => $email];
    }

    private function alphanumericNameBase(array $parsed): string
    {
        $merged = Str::ascii(implode('', array_filter([
            $parsed['first_name'],
            $parsed['middle_name'] ?? '',
            $parsed['last_name'],
        ])));
        $merged = strtolower(preg_replace('/[^a-z0-9]/', '', $merged) ?? '');
        if ($merged === '') {
            $merged = 'user';
        }

        return substr($merged, 0, 26);
    }

    private function resolveRegistrationFee(int $organizerIndex, string $difficulty): string
    {
        if ($difficulty === 'Beginner' && $organizerIndex < 5) {
            return '0.00';
        }
        if ($difficulty === 'Intermediate') {
            return '249.00';
        }
        if ($difficulty === 'Beginner') {
            return '99.00';
        }

        return '149.00';
    }

    private function storeRouteGeoJson(int $userId, int $seed, string $difficulty): string
    {
        $coords = $this->buildTagbinaLonLatLine($seed, $difficulty);
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => ['seed' => $seed, 'difficulty' => $difficulty],
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => $coords,
                ],
            ]],
        ];

        $filename = 'routes/system_seed_u'.$userId.'_s'.$seed.'.geojson';
        Storage::disk('public')->put($filename, json_encode($geojson, JSON_UNESCAPED_UNICODE));

        return $filename;
    }

    /**
     * @return list<array{0: float, 1: float}> GeoJSON positions [lon, lat] along a short segment in Tagbina.
     */
    private function buildTagbinaLonLatLine(int $seed, string $difficulty): array
    {
        $lat0 = self::TAGBINA_LAT + (($seed % 7) * 0.0006);
        $lon0 = self::TAGBINA_LON + ((int) ($seed / 7) % 7) * 0.0006;

        $steps = match ($difficulty) {
            'Beginner' => 5,
            'Improving' => 7,
            default => 9,
        };

        $coords = [];
        for ($i = 0; $i < $steps; $i++) {
            $coords[] = [
                round($lon0 + $i * 0.0014, 6),
                round($lat0 + $i * 0.00085, 6),
            ];
        }

        return $coords;
    }
}
