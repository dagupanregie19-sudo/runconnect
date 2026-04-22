<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LocationController extends Controller
{
    private function normalizeText(?string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
    }

    public function getCities()
    {
        $cities = Cache::rememberForever('refcitymun_with_region_prov_v2', function () {
            $path = database_path('data/refcitymun.csv');
            if (!File::exists($path)) {
                return [];
            }

            $regions = [];
            $regPath = database_path('data/refregion.csv');
            if (File::exists($regPath)) {
                $linesReg = explode("\n", trim(file_get_contents($regPath)));
                for ($i = 1; $i < count($linesReg); $i++) {
                    if (empty(trim($linesReg[$i])))
                        continue;
                    $row = str_getcsv($linesReg[$i]);
                    // id,psgcCode,regDesc,regCode
                    if (count($row) >= 4) {
                        $regions[$this->normalizeText($row[3])] = $this->normalizeText($row[2]);
                    }
                }
            }

            $provinces = [];
            $provPath = database_path('data/refprovince.csv');
            if (File::exists($provPath)) {
                $linesProv = explode("\n", trim(file_get_contents($provPath)));
                for ($i = 1; $i < count($linesProv); $i++) {
                    if (empty(trim($linesProv[$i])))
                        continue;
                    $row = str_getcsv($linesProv[$i]);
                    // id,psgcCode,provDesc,regCode,provCode
                    if (count($row) >= 5) {
                        $provinces[$this->normalizeText($row[4])] = $this->normalizeText($row[2]);
                    }
                }
            }

            $csvData = file_get_contents($path);
            $lines = explode("\n", trim($csvData));
            $data = [];
            // Skip header (id,psgcCode,citymunDesc,regDesc,provCode,citymunCode)
            for ($i = 1; $i < count($lines); $i++) {
                if (empty(trim($lines[$i])))
                    continue;
                $row = str_getcsv($lines[$i]);
                if (count($row) >= 6) {
                    $regCode = $this->normalizeText($row[3]);
                    $provCode = $this->normalizeText($row[4]);

                    // some NCR districts are returned as province - lets just use the province if it exists
                    $provName = isset($provinces[$provCode]) ? $provinces[$provCode] : '';
                    $regName = isset($regions[$regCode]) ? $regions[$regCode] : '';

                    $data[] = [
                        'citymunCode' => $this->normalizeText($row[5]),
                        'citymunDesc' => $this->normalizeText($row[2]),
                        'provDesc' => $this->normalizeText($provName),
                        'regDesc' => $this->normalizeText($regName),
                    ];
                }
            }

            // Sort alphabetically by city
            usort($data, function ($a, $b) {
                return strcmp($a['citymunDesc'], $b['citymunDesc']);
            });

            return $data;
        });

        return response()->json($cities);
    }

    public function getBarangays(Request $request)
    {
        $cityCode = $request->query('city_code');
        if (!$cityCode) {
            return response()->json([]);
        }

        // We cache all barangays to avoid reading the 2MB file every time
        $allBrgys = Cache::rememberForever('refbrgy_v2', function () {
            $path = database_path('data/refbrgy.csv');
            if (!File::exists($path)) {
                return [];
            }

            $csvData = file_get_contents($path);
            $lines = explode("\n", trim($csvData));
            $data = [];
            // Skip header (id,brgyCode,brgyDesc,regCode,provCode,citymunCode)
            for ($i = 1; $i < count($lines); $i++) {
                if (empty(trim($lines[$i])))
                    continue;
                $row = str_getcsv($lines[$i]);
                if (count($row) >= 6) {
                    // Group by citymunCode for fast lookup
                    $city = $this->normalizeText($row[5]);
                    if (!isset($data[$city])) {
                        $data[$city] = [];
                    }
                    $data[$city][] = [
                        'brgyCode' => $this->normalizeText($row[1]),
                        'brgyDesc' => $this->normalizeText($row[2]),
                    ];
                }
            }

            // Sort barangays alphabetically per city
            foreach ($data as $city => &$brgys) {
                usort($brgys, function ($a, $b) {
                    return strcmp($a['brgyDesc'], $b['brgyDesc']);
                });
            }

            return $data;
        });

        return response()->json($allBrgys[$cityCode] ?? []);
    }
}
