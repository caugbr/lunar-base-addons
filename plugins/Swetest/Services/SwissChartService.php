<?php

namespace Plugins\Swetest\Services;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

class SwissChartService
{
    private string $swetestPath;
    private string $ephePath;

    private const SIGNS = [
        'Aries', 'Taurus', 'Gemini', 'Cancer',
        'Leo', 'Virgo', 'Libra', 'Scorpio',
        'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces',
    ];

    private const PLANET_CODES = [
        'sun'        => '0',
        'moon'       => '1',
        'mercury'    => '2',
        'venus'      => '3',
        'mars'       => '4',
        'jupiter'    => '5',
        'saturn'     => '6',
        'uranus'     => '7',
        'neptune'    => '8',
        'pluto'      => '9',
        'north_node' => 'm',
        'true_node'  => 't',
        'chiron'     => 'D',
        'lilith'     => 'A',
    ];

    private const HOUSE_SYSTEMS = [
        'placidus'      => 'p',
        'koch'          => 'k',
        'equal'         => 'e',
        'whole_sign'    => 'w',
        'campanus'      => 'c',
        'regiomontanus' => 'r',
        'porphyry'      => 'o',
        'topocentric'   => 't',
    ];

    public function __construct(?string $swetestPath = null, ?string $ephePath = null)
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $configuredBin = function_exists('config') ? trim((string) config('pluginSettings.Swetest.swetestPath', '')) : '';
        $configuredEphe = function_exists('config') ? trim((string) config('pluginSettings.Swetest.ephePath', '')) : '';

        $defaultBin = dirname(__DIR__) . ($isWindows
            ? '/swetest/windows/swetest64.exe'
            : '/swetest/linux/swetest');

        $defaultEphe = dirname(__DIR__) . '/ephe';

        $this->swetestPath = $swetestPath ?: ($configuredBin !== '' ? $configuredBin : $defaultBin);
        $this->ephePath = $ephePath ?: ($configuredEphe !== '' ? $configuredEphe : $defaultEphe);
    }

    public static function calc(array $params): array
    {
        $service = new self(
            $params['swetestPath'] ?? null,
            $params['ephePath'] ?? null
        );

        return $service->execute($params);
    }

    public static function calcSolarReturn(array $params): array
    {
        $service = new self(
            $params['swetestPath'] ?? null,
            $params['ephePath'] ?? null
        );

        return $service->solarReturn($params);
    }

    public function checkEnvironment(): array
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        $execAvailable = function_exists('exec') && !in_array('exec', $disabled, true);
        $binExists = file_exists($this->swetestPath);
        $binExecutable = $binExists && ($isWindows || is_executable($this->swetestPath));
        $epheExists = is_dir($this->ephePath);

        return [
            'os'             => PHP_OS,
            'exec_available' => $execAvailable,
            'swetest_path'   => $this->swetestPath,
            'bin_exists'     => $binExists,
            'bin_executable' => $binExecutable,
            'ephe_path'      => $this->ephePath,
            'ephe_exists'    => $epheExists,
            'ready'          => $execAvailable && $binExists && $binExecutable && $epheExists,
        ];
    }

    private function assertEnvironmentReady(): void
    {
        $status = $this->checkEnvironment();

        if (!$status['exec_available']) {
            throw new Exception('A função exec() está desabilitada neste servidor PHP.');
        }

        if (!$status['bin_exists']) {
            throw new Exception("Binário swetest não encontrado em: {$this->swetestPath}");
        }

        if (!$status['bin_executable']) {
            throw new Exception("O binário swetest não tem permissão de execução (chmod +x): {$this->swetestPath}");
        }

        if (!$status['ephe_exists']) {
            throw new Exception("Pasta de efemérides (ephe) não encontrada em: {$this->ephePath}");
        }
    }

    public function execute(array $params): array
    {
        $this->assertEnvironmentReady();

        $date = $params['date'] ?? null;
        $time = $params['time'] ?? '12:00';
        $timezone = $params['timezone'] ?? 'UTC';

        if (!$date) {
            throw new Exception("The 'date' parameter is required (YYYY-MM-DD).");
        }

        $planetsArg = $params['planets'] ?? 'all';
        $planetsFlag = $this->resolvePlanetsFlag($planetsArg);

        $rawHouseSystem = strtolower(trim($params['houseSystem'] ?? 'placidus'));
        $houseSystemCode = self::HOUSE_SYSTEMS[$rawHouseSystem] ?? 'p';

        $dt = new DateTime("{$date} {$time}", new DateTimeZone($timezone));
        $dt->setTimezone(new DateTimeZone('UTC'));
        $utcDate = $dt->format('d.m.Y');
        $utcTime = $dt->format('H:i:s');

        $houseParam = '';
        $hasCoords = isset($params['latitude'], $params['longitude'])
            && $params['latitude'] !== null
            && $params['longitude'] !== null;

        if ($hasCoords) {
            $houseParam = sprintf(
                '-house%F,%F,%s',
                (float) $params['longitude'],
                (float) $params['latitude'],
                $houseSystemCode
            );
        }

        $cmd = sprintf(
            '%s -edir%s -b%s -ut%s %s %s -eswe -fPls -head 2>&1',
            escapeshellarg($this->swetestPath),
            escapeshellarg($this->ephePath),
            $utcDate,
            $utcTime,
            $planetsFlag ? "-p{$planetsFlag}" : '',
            $houseParam
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || empty($output)) {
            throw new Exception('Swetest execution failed: ' . implode("\n", $output));
        }

        $chart = $this->parseOutput($output);

        if ($params['aspects'] ?? true) {
            $includeMinors = (bool) ($params['include_minors'] ?? false);
            $orbGeneral = (float) ($params['orb_general'] ?? 6.0);
            $orbLuminaries = (float) ($params['orb_luminaries'] ?? 10.0);

            $chart['aspects'] = $this->calculateAspects(
                $chart['planets'],
                $chart['angles'],
                $includeMinors,
                $orbGeneral,
                $orbLuminaries
            );
        }

        return $chart;
    }

    public function solarReturn(array $params): array
    {
        $this->assertEnvironmentReady();

        $targetYear = isset($params['target_year']) ? (int) $params['target_year'] : null;
        $birthDate = $params['birth_date'] ?? null;

        if (!$targetYear || !$birthDate) {
            throw new Exception("The 'target_year' and 'birth_date' parameters are required.");
        }

        if (isset($params['target_sun_longitude']) && is_numeric($params['target_sun_longitude'])) {
            $targetSunLon = fmod(fmod((float) $params['target_sun_longitude'], 360.0) + 360.0, 360.0);
        } else {
            $natalSunChart = $this->execute([
                'date'     => $birthDate,
                'time'     => $params['birth_time'] ?? '12:00:00',
                'timezone' => $params['birth_timezone'] ?? $params['timezone'] ?? 'UTC',
                'planets'  => 'sun',
                'aspects'  => false,
            ]);

            if (!isset($natalSunChart['planets']['Sun']['longitude'])) {
                throw new Exception('Unable to determine natal Sun longitude.');
            }

            $targetSunLon = (float) $natalSunChart['planets']['Sun']['longitude'];
        }

        $birthDt = new DateTimeImmutable($birthDate, new DateTimeZone('UTC'));
        $month = (int) $birthDt->format('m');
        $day = (int) $birthDt->format('d');

        if ($month === 2 && $day === 29 && !checkdate(2, 29, $targetYear)) {
            $month = 3;
            $day = 1;
        }

        $guessDt = new DateTimeImmutable(
            sprintf('%04d-%02d-%02d 12:00:00', $targetYear, $month, $day),
            new DateTimeZone('UTC')
        );
        $jd = $this->dateTimeToJulianDay($guessDt);

        $maxIterations = 10;
        $toleranceDeg = 0.000001;
        $iterationsUsed = 0;
        $diff = 0.0;

        for ($i = 0; $i < $maxIterations; $i++) {
            $iterationsUsed++;
            $sunPos = $this->querySunAtJulianDay($jd);
            $sunLon = (float) $sunPos['longitude'];
            $sunSpeed = (float) ($sunPos['daily_speed'] ?? 0.985647);

            if (abs($sunSpeed) < 0.1) {
                $sunSpeed = 0.985647;
            }

            $diff = fmod($sunLon - $targetSunLon + 540.0, 360.0) - 180.0;

            if (abs($diff) <= $toleranceDeg) {
                break;
            }

            $jd -= $diff / $sunSpeed;
        }

        if (abs($diff) > 0.001) {
            throw new Exception("Solar return convergence failed (residual error: {$diff}°).");
        }

        $chart = $this->executeAtJulianDay($jd, $params);

        $returnTimezone = $params['timezone'] ?? 'UTC';
        $utcDt = $this->julianDayToUtcDateTime($jd);
        $localDt = $utcDt->setTimezone(new DateTimeZone($returnTimezone));

        $chart['solar_return'] = [
            'target_year'          => $targetYear,
            'target_sun_longitude' => round($targetSunLon, 6),
            'actual_sun_longitude' => $chart['planets']['Sun']['longitude'] ?? null,
            'jd_ut'                => round($jd, 8),
            'utc_datetime'         => $utcDt->format('Y-m-d H:i:s'),
            'local_datetime'       => $localDt->format('Y-m-d H:i:s'),
            'timezone'             => $returnTimezone,
            'iterations'           => $iterationsUsed,
        ];

        return $chart;
    }

    public function executeAtJulianDay(float $jdUt, array $params): array
    {
        $this->assertEnvironmentReady();

        $planetsArg = $params['planets'] ?? 'all';
        $planetsFlag = $this->resolvePlanetsFlag($planetsArg);

        $rawHouseSystem = strtolower(trim($params['houseSystem'] ?? 'placidus'));
        $houseSystemCode = self::HOUSE_SYSTEMS[$rawHouseSystem] ?? 'p';

        $houseParam = '';
        $hasCoords = isset($params['latitude'], $params['longitude'])
            && $params['latitude'] !== null
            && $params['longitude'] !== null;

        if ($hasCoords) {
            $houseParam = sprintf(
                '-house%F,%F,%s',
                (float) $params['longitude'],
                (float) $params['latitude'],
                $houseSystemCode
            );
        }

        $cmd = sprintf(
            '%s -edir%s -bj%.8F %s %s -eswe -fPls -head 2>&1',
            escapeshellarg($this->swetestPath),
            escapeshellarg($this->ephePath),
            $jdUt,
            $planetsFlag ? "-p{$planetsFlag}" : '',
            $houseParam
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || empty($output)) {
            throw new Exception('Swetest execution failed: ' . implode("\n", $output));
        }

        $chart = $this->parseOutput($output);

        if ($params['aspects'] ?? true) {
            $includeMinors = (bool) ($params['include_minors'] ?? false);
            $orbGeneral = (float) ($params['orb_general'] ?? 6.0);
            $orbLuminaries = (float) ($params['orb_luminaries'] ?? 10.0);

            $chart['aspects'] = $this->calculateAspects(
                $chart['planets'],
                $chart['angles'],
                $includeMinors,
                $orbGeneral,
                $orbLuminaries
            );
        }

        return $chart;
    }

    private function querySunAtJulianDay(float $jdUt): array
    {
        $cmd = sprintf(
            '%s -edir%s -bj%.8F -p0 -eswe -fPls -head 2>&1',
            escapeshellarg($this->swetestPath),
            escapeshellarg($this->ephePath),
            $jdUt
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || empty($output)) {
            throw new Exception('Swetest Sun query failed: ' . implode("\n", $output));
        }

        $parsed = $this->parseOutput($output);

        if (!isset($parsed['planets']['Sun'])) {
            throw new Exception('Sun position not found in swetest output.');
        }

        return $parsed['planets']['Sun'];
    }

    private function dateTimeToJulianDay(DateTimeInterface $dt): float
    {
        $utc = DateTimeImmutable::createFromInterface($dt)->setTimezone(new DateTimeZone('UTC'));

        return 2440587.5 + ((float) $utc->format('U.u') / 86400.0);
    }

    private function julianDayToUtcDateTime(float $jdUt): DateTimeImmutable
    {
        $unixSeconds = (int) round(($jdUt - 2440587.5) * 86400.0);

        return (new DateTimeImmutable('@' . $unixSeconds))->setTimezone(new DateTimeZone('UTC'));
    }

    private function calculateAspects(
        array $planets,
        array $angles = [],
        bool $includeMinors = false,
        float $orbGeneral = 6.0,
        float $orbLuminaries = 10.0
    ): array {
        $aspects = [];

        $aspectsTable = [
            'conjunction' => ['angle' => 0.0, 'name' => 'Conjunction', 'symbol' => '☌', 'category' => 'major'],
            'opposition'  => ['angle' => 180.0, 'name' => 'Opposition', 'symbol' => '☍', 'category' => 'major'],
            'trine'       => ['angle' => 120.0, 'name' => 'Trine', 'symbol' => '△', 'category' => 'major'],
            'square'      => ['angle' => 90.0, 'name' => 'Square', 'symbol' => '□', 'category' => 'major'],
            'sextile'     => ['angle' => 60.0, 'name' => 'Sextile', 'symbol' => '⚹', 'category' => 'major'],
        ];

        if ($includeMinors) {
            $aspectsTable['quincunx'] = ['angle' => 150.0, 'name' => 'Quincunx', 'symbol' => '⚻', 'category' => 'minor'];
            $aspectsTable['semisquare'] = ['angle' => 45.0, 'name' => 'Semisquare', 'symbol' => '∠', 'category' => 'minor'];
            $aspectsTable['sesquiquadrate'] = ['angle' => 135.0, 'name' => 'Sesquiquadrate', 'symbol' => '⚼', 'category' => 'minor'];
            $aspectsTable['semisextile'] = ['angle' => 30.0, 'name' => 'Semisextile', 'symbol' => '⚺', 'category' => 'minor'];
        }

        $validPoints = $planets;
        if (isset($angles['Ascendant'])) {
            $validPoints['Ascendant'] = $angles['Ascendant'];
        }
        if (isset($angles['MC'])) {
            $validPoints['MC'] = $angles['MC'];
        }

        $keys = array_keys($validPoints);
        $count = count($keys);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $name1 = $keys[$i];
                $name2 = $keys[$j];

                $isAngle1 = in_array($name1, ['Ascendant', 'MC'], true);
                $isAngle2 = in_array($name2, ['Ascendant', 'MC'], true);
                if ($isAngle1 && $isAngle2) {
                    continue;
                }

                $p1 = $validPoints[$name1];
                $p2 = $validPoints[$name2];

                $dist = $this->getAngularDistance((float) $p1['longitude'], (float) $p2['longitude']);

                $hasLuminary = in_array($name1, ['Sun', 'Moon'], true) || in_array($name2, ['Sun', 'Moon'], true);
                $baseOrb = $hasLuminary ? $orbLuminaries : $orbGeneral;

                foreach ($aspectsTable as $type => $info) {
                    $maxOrb = ($info['category'] === 'minor') ? min(2.0, $baseOrb * 0.3) : $baseOrb;
                    $diff = abs($dist - $info['angle']);

                    if ($diff <= $maxOrb) {
                        $isApplying = $this->isAspectApplying(
                            (float) $p1['longitude'],
                            (float) ($p1['daily_speed'] ?? 0),
                            (float) $p2['longitude'],
                            (float) ($p2['daily_speed'] ?? 0),
                            $info['angle']
                        );

                        $orbDeg = (int) floor($diff);
                        $orbMin = (int) round(($diff - $orbDeg) * 60);
                        if ($orbMin >= 60) {
                            $orbDeg++;
                            $orbMin = 0;
                        }

                        $aspects[] = [
                            'point1'        => $name1,
                            'point2'        => $name2,
                            'aspect'        => $info['name'],
                            'aspect_type'   => $type,
                            'category'      => $info['category'],
                            'symbol'        => $info['symbol'],
                            'target_angle'  => (int) $info['angle'],
                            'actual_angle'  => round($dist, 4),
                            'orb'           => round($diff, 4),
                            'orb_formatted' => sprintf("%02d°%02d'", $orbDeg, $orbMin),
                            'is_applying'   => $isApplying,
                            'state'         => $isApplying ? 'Applying' : 'Separating',
                        ];
                        break;
                    }
                }
            }
        }

        usort($aspects, fn ($a, $b) => $a['orb'] <=> $b['orb']);

        return $aspects;
    }

    private function getAngularDistance(float $lon1, float $lon2): float
    {
        $diff = abs($lon1 - $lon2);

        return ($diff > 180.0) ? (360.0 - $diff) : $diff;
    }

    private function isAspectApplying(float $lon1, float $speed1, float $lon2, float $speed2, float $targetAngle): bool
    {
        $dt = 0.005;
        $currentDiff = abs($this->getAngularDistance($lon1, $lon2) - $targetAngle);
        $futureDiff = abs($this->getAngularDistance($lon1 + ($speed1 * $dt), $lon2 + ($speed2 * $dt)) - $targetAngle);

        return $futureDiff < $currentDiff;
    }

    private function resolvePlanetsFlag(string|array $planets): string
    {
        if ($planets === 'all') {
            return implode('', array_values(self::PLANET_CODES));
        }

        if (empty($planets)) {
            return '';
        }

        $list = is_array($planets) ? $planets : explode(',', $planets);
        $flags = '';

        foreach ($list as $planetName) {
            $normalized = strtolower(trim(str_replace(['-', ' '], '_', $planetName)));
            if (isset(self::PLANET_CODES[$normalized])) {
                $flags .= self::PLANET_CODES[$normalized];
            }
        }

        return $flags;
    }

    private function parseOutput(array $lines): array
    {
        $planets = [];
        $houses = [];
        $angles = [];

        $allowedBodies = [
            'Sun'         => 'Sun',
            'Moon'        => 'Moon',
            'Mercury'     => 'Mercury',
            'Venus'       => 'Venus',
            'Mars'        => 'Mars',
            'Jupiter'     => 'Jupiter',
            'Saturn'      => 'Saturn',
            'Uranus'      => 'Uranus',
            'Neptune'     => 'Neptune',
            'Pluto'       => 'Pluto',
            'mean Node'   => 'North Node',
            'true Node'   => 'True Node',
            'Chiron'      => 'Chiron',
            'mean Apogee' => 'Lilith',
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);

            if ($parts[0] === 'Ascendant' || $parts[0] === 'MC' || str_starts_with($parts[0], 'Ascendant') || str_starts_with($parts[0], 'MC')) {
                $name = str_replace(':', '', $parts[0]);
                $lon = (float) $parts[1];
                $angles[$name] = $this->formatPosition($lon);
                continue;
            }

            if (in_array($parts[0], ['ARMC', 'Vertex', 'Equat.', 'Polar', 'Co-Asc.'], true)) {
                continue;
            }

            if (($parts[0] === 'house' || $parts[0] === 'cusp') && isset($parts[1]) && is_numeric($parts[1])) {
                $houseNum = (int) $parts[1];
                if ($houseNum >= 1 && $houseNum <= 12 && isset($parts[2])) {
                    $lon = (float) $parts[2];
                    $houses[$houseNum] = $this->formatPosition($lon);
                }
                continue;
            }

            if (count($parts) >= 2) {
                $speed = count($parts) >= 3 ? (float) array_pop($parts) : 0.0;
                $lon = (float) array_pop($parts);
                $rawName = implode(' ', $parts);

                if (isset($allowedBodies[$rawName])) {
                    $planetName = $allowedBodies[$rawName];
                    $planetData = $this->formatPosition($lon);
                    $planetData['is_retrograde'] = ($speed < 0);
                    $planetData['daily_speed'] = $speed;
                    $planets[$planetName] = $planetData;
                }
            }
        }

        if (!isset($angles['Ascendant']) && isset($houses[1])) {
            $angles['Ascendant'] = $houses[1];
        }

        if (!isset($angles['MC']) && isset($houses[10])) {
            $angles['MC'] = $houses[10];
        }

        if (!empty($houses)) {
            foreach ($planets as &$p) {
                $p['house'] = $this->determineHouse($p['longitude'], $houses);
            }
        }

        return [
            'angles'  => $angles,
            'houses'  => $houses,
            'planets' => $planets,
        ];
    }

    private function formatPosition(float $deg): array
    {
        $deg = fmod($deg, 360.0);
        if ($deg < 0) {
            $deg += 360.0;
        }

        $signIndex = (int) floor($deg / 30);
        $signName = self::SIGNS[$signIndex] ?? 'Unknown';

        $signDegreeDecimal = $deg - ($signIndex * 30);
        $d = (int) floor($signDegreeDecimal);
        $mDecimal = ($signDegreeDecimal - $d) * 60;
        $m = (int) floor($mDecimal);
        $s = (int) round(($mDecimal - $m) * 60);

        if ($s >= 60) {
            $s = 0;
            $m++;
        }
        if ($m >= 60) {
            $m = 0;
            $d++;
        }

        return [
            'longitude'   => round($deg, 6),
            'sign'        => $signName,
            'sign_number' => $signIndex + 1,
            'degree'      => $d,
            'minute'      => $m,
            'second'      => $s,
            'formatted'   => sprintf("%02d° %s %02d'%02d\"", $d, $signName, $m, $s),
        ];
    }

    private function determineHouse(float $planetLon, array $houses): int
    {
        for ($i = 1; $i <= 12; $i++) {
            $next = ($i === 12) ? 1 : ($i + 1);
            $cuspCurrent = $houses[$i]['longitude'];
            $cuspNext = $houses[$next]['longitude'];

            if ($cuspCurrent < $cuspNext) {
                if ($planetLon >= $cuspCurrent && $planetLon < $cuspNext) {
                    return $i;
                }
            } else {
                if ($planetLon >= $cuspCurrent || $planetLon < $cuspNext) {
                    return $i;
                }
            }
        }

        return 1;
    }
}
